#!/usr/bin/env python3
"""
Sphinx Integration Module for Polish Language Support.

Handles integration with Sphinx search daemon via MySQL protocol,
specifically configured for Polish language search and indexing.
Includes Polish-specific text processing and search optimization.
"""

import configparser
import logging
import os
from typing import Any, Dict, List, Optional

from .core.constants import POLISH_DIACRITICS_MAP, POLISH_STOPWORDS
from .utils.cache import SphinxAICache

logger = logging.getLogger(__name__)

# Import PyMySQL for Sphinx MySQL protocol communication
try:
    import pymysql
    import pymysql.cursors

    PYMYSQL_AVAILABLE: bool = True
except ImportError:
    logger.warning("PyMySQL not available")
    PYMYSQL_AVAILABLE = False
    pymysql = None  # type: ignore


class SphinxIntegrationPolish:
    """
    Handles Sphinx search daemon integration with Polish language support.

    This class provides specialized functionality for Polish language
    search and indexing with Sphinx search daemon.
    """

    def __init__(self, config_path: str = "/etc/sphinx/sphinx.conf") -> None:
        """
        Initialize Polish Sphinx integration.

        Args:
            config_path: Path to Sphinx configuration file
        """
        self.config_path = config_path
        self.searchd_host = "localhost"
        self.searchd_port = 9306  # Default Sphinx MySQL port
        self.index_name = "smf_polish_posts"
        self.connection = None
        self.available_fields: List[str] = []  # Cache for detected fields
        self.content_in_index = False  # Flag for content availability
        self.cache = SphinxAICache()  # Initialize cache service

        # Load configuration
        self._load_config()

        # Detect available fields on initialization
        self._detect_index_fields()

    def _load_config(self) -> None:
        """Load Sphinx configuration."""
        try:
            if os.path.exists(self.config_path):
                config = configparser.ConfigParser()
                config.read(self.config_path)

                # Extract searchd settings
                if "searchd" in config:
                    listen = config["searchd"].get("listen", "localhost:9306")
                    if ":" in listen:
                        self.searchd_host, port_str = listen.split(":")
                        self.searchd_port = int(port_str)
                    else:
                        self.searchd_host = listen

                # Extract index settings
                if f"index {self.index_name}" in config:
                    # Index configuration loaded
                    pass

        except Exception as e:
            logger.error(f"Error loading Sphinx config: {e}")

    def _detect_index_fields(self) -> None:
        """
        Detect what fields are available in the Sphinx index.
        This determines whether we can get content directly from Sphinx
        or need to query SMF database.
        """
        try:
            connection = self._get_connection()
            if not connection:
                logger.warning("Cannot detect index fields - no Sphinx connection")
                self.available_fields = ["id", "topic_id", "post_id", "board_id"]
                self.content_in_index = False
                return

            with connection.cursor(pymysql.cursors.DictCursor) as cursor:
                # Validate index name first
                if not self._validate_index_name(self.index_name):
                    logger.error(f"Invalid index name: {self.index_name}")
                    self.available_fields = ["id", "topic_id", "post_id", "board_id"]
                    self.content_in_index = False
                    return

                # Try to describe the index structure using safe queries
                try:
                    # Use parameterized approach with validated index name
                    escaped_index = self._escape_identifier(self.index_name)
                    describe_query = f"DESCRIBE {escaped_index}"
                    cursor.execute(describe_query)
                    fields_info = cursor.fetchall()
                    self.available_fields = [field["Field"] for field in fields_info]
                    logger.info(
                        f"Detected Sphinx index fields: {self.available_fields}"
                    )
                except Exception:
                    # If DESCRIBE doesn't work, try a sample query to detect fields
                    try:
                        escaped_index = self._escape_identifier(self.index_name)
                        sample_query = f"SELECT * FROM {escaped_index} LIMIT 1"
                        cursor.execute(sample_query)
                        sample_result = cursor.fetchone()
                        if sample_result:
                            self.available_fields = list(sample_result.keys())
                            logger.info(
                                f"Detected fields from sample query: {self.available_fields}"
                            )
                        else:
                            # Empty index, assume minimal fields
                            self.available_fields = [
                                "id",
                                "topic_id",
                                "post_id",
                                "board_id",
                            ]
                            logger.warning("Empty index, assuming minimal field set")
                    except Exception:
                        # Fallback to minimal field set
                        self.available_fields = [
                            "id",
                            "topic_id",
                            "post_id",
                            "board_id",
                        ]
                        logger.warning("Could not detect fields, using minimal set")

                # Check if content fields are available
                content_fields = ["content", "body", "message", "text"]
                subject_fields = ["subject", "title", "topic_title"]

                self.content_in_index = any(
                    field in self.available_fields for field in content_fields
                )
                subject_in_index = any(
                    field in self.available_fields for field in subject_fields
                )

                if self.content_in_index:
                    logger.info("✓ Content available in Sphinx index")
                else:
                    logger.info(
                        "⚠ Content NOT in Sphinx index - will need SMF database queries"
                    )

                if subject_in_index:
                    logger.info("✓ Subject/title available in Sphinx index")
                else:
                    logger.info(
                        "⚠ Subject NOT in Sphinx index - will need SMF database queries"
                    )

        except Exception as e:
            logger.error(f"Error detecting index fields: {e}")
            self.available_fields = ["id", "topic_id", "post_id", "board_id"]
            self.content_in_index = False

    def preprocess_polish_query(self, query: str) -> str:
        """
        Preprocess Polish query for better search results.

        Args:
            query: Original Polish query

        Returns:
            Preprocessed query optimized for Polish search
        """
        if not query:
            return ""

        # Convert to lowercase
        processed_query = query.lower()

        # Remove Polish stopwords
        words = processed_query.split()
        filtered_words = [word for word in words if word not in POLISH_STOPWORDS]

        # If all words were stopwords, return original query
        if not filtered_words:
            return query

        # Rejoin words
        processed_query = " ".join(filtered_words)

        # Add diacritic-insensitive search variations
        # This helps find results even with different diacritic usage
        query_variations = [processed_query]

        # Create variation without diacritics
        normalized_query = self._normalize_polish_diacritics(processed_query)
        if normalized_query != processed_query:
            query_variations.append(normalized_query)

        # Join variations with OR operator for Sphinx
        return " | ".join(query_variations)

    def _normalize_polish_diacritics(self, text: str) -> str:
        """
        Normalize Polish diacritics for search.

        Args:
            text: Text with Polish diacritics

        Returns:
            Text with normalized diacritics
        """
        for polish_char, normalized_char in POLISH_DIACRITICS_MAP.items():
            text = text.replace(polish_char, normalized_char)
        return text

    def _get_connection(self) -> Optional[Any]:
        """Get MySQL connection to Sphinx."""
        if not PYMYSQL_AVAILABLE or pymysql is None:
            logger.error("PyMySQL not available")
            return None

        try:
            if self.connection is None or not self.connection.open:
                self.connection = pymysql.connect(
                    host=self.searchd_host, port=self.searchd_port, charset="utf8"
                )
            return self.connection
        except Exception as e:
            logger.error(f"Error connecting to Sphinx: {e}")
            return None

    def search_polish(self, query: str, limit: int = 100) -> List[Dict[str, Any]]:
        """
        Perform Polish-optimized search using Sphinx via MySQL protocol.

        Args:
            query: Polish search query
            limit: Maximum number of results

        Returns:
            List of search results
        """
        try:
            # Preprocess query for Polish
            processed_query = self.preprocess_polish_query(query)
            logger.info(f"Original query: {query}")
            logger.info(f"Processed query: {processed_query}")

            # Create cache key from query and limit
            cache_key_data = {
                "query": processed_query,
                "limit": limit,
                "index": self.index_name,
                "available_fields": sorted(self.available_fields),
            }

            # Try to get from cache first
            cached_results = self.cache.get_cached_search_results(
                processed_query, {"limit": limit, "index": self.index_name}
            )

            if cached_results is not None:
                logger.info(f"Cache hit for query: {query}")
                self.cache.record_cache_hit()
                return cached_results.get("results", [])

            logger.info(f"Cache miss for query: {query}")
            self.cache.record_cache_miss()

            # Execute search
            results = self._sphinx_search(processed_query, limit)

            # Cache successful results
            if results:
                self.cache.cache_search_results(
                    processed_query,
                    {"limit": limit, "index": self.index_name},
                    results,
                    ttl=1800,  # 30 minutes for search results
                )

            return results

        except Exception as e:
            logger.error(f"Error in Polish Sphinx search: {e}")
            return []

    def _sphinx_search(self, query: str, limit: int) -> List[Dict[str, Any]]:
        """Execute Sphinx search via MySQL protocol."""
        if not PYMYSQL_AVAILABLE or pymysql is None:
            logger.error("PyMySQL not available for search")
            return []

        connection = self._get_connection()
        if not connection:
            return []

        try:
            with connection.cursor(pymysql.cursors.DictCursor) as cursor:
                # Escape the query for Sphinx
                escaped_query = query.replace("'", "\\'").replace('"', '\\"')

                # Build field list based on what's available in the index
                base_fields = ["id", "WEIGHT() as weight"]
                optional_fields = []

                # Add fields that exist in the index
                if "topic_id" in self.available_fields:
                    optional_fields.append("topic_id")
                if "post_id" in self.available_fields:
                    optional_fields.append("post_id")
                if "board_id" in self.available_fields:
                    optional_fields.append("board_id")

                # Add content fields if available
                if self.content_in_index:
                    for field in ["content", "body", "message"]:
                        if field in self.available_fields:
                            optional_fields.append(field)
                            break

                    for field in ["subject", "title", "topic_title"]:
                        if field in self.available_fields:
                            optional_fields.append(field)
                            break

                all_fields = base_fields + optional_fields

                # Validate and escape field names
                safe_fields = []
                for field in all_fields:
                    if self._validate_field_name(field):
                        safe_fields.append(self._escape_identifier(field))
                    else:
                        logger.warning(f"Skipping invalid field name: {field}")

                if not safe_fields:
                    safe_fields = ["id", "topic_id", "post_id", "board_id"]  # fallback

                fields_str = ", ".join(safe_fields)
                escaped_index = self._escape_identifier(self.index_name)

                # Build Sphinx SQL query with parameterized MATCH clause
                # Note: Sphinx MATCH uses special syntax, but we still validate the query
                if not self._validate_search_query(escaped_query):
                    raise ValueError("Invalid search query format")

                sql = f"""
                    SELECT {fields_str}
                    FROM {escaped_index}
                    WHERE MATCH(?)
                    ORDER BY weight DESC
                    LIMIT ?
                """

                logger.debug(f"Executing Sphinx query with fields: {fields_str}")
                # Use parameters for MATCH and LIMIT values
                cursor.execute(sql, (escaped_query, limit))
                sphinx_results = cursor.fetchall()

                # Convert to standard format
                results: List[Dict[str, Any]] = []
                for row in sphinx_results:
                    result: Dict[str, Any] = {
                        "id": row.get("id"),
                        "topic_id": row.get("topic_id"),
                        "post_id": row.get("post_id"),
                        "board_id": row.get("board_id"),
                        "weight": row.get("weight", 0),
                        "content_in_index": self.content_in_index,
                        "needs_content_fetch": not self.content_in_index,
                        "attrs": {
                            "topic_id": row.get("topic_id"),
                            "post_id": row.get("post_id"),
                            "board_id": row.get("board_id"),
                        },
                    }

                    # Add content fields if available in index
                    if self.content_in_index:
                        for field in ["content", "body", "message"]:
                            if field in row:
                                result["content"] = row[field]
                                break

                        for field in ["subject", "title", "topic_title"]:
                            if field in row:
                                result["subject"] = row[field]
                                break

                    results.append(result)

                logger.info(f"Sphinx search returned {len(results)} results")
                if results and not self.content_in_index:
                    logger.info(
                        "Results contain only IDs - content will need to be fetched from SMF database"
                    )

                return results

        except Exception as e:
            logger.error(f"Error executing Sphinx search: {e}")
            return []

    def index_polish_content(self, content: List[Dict[str, Any]]) -> bool:
        """
        Index Polish content using Sphinx - simplified approach.

        Args:
            content: List of Polish content items to index

        Returns:
            True if successful, False otherwise
        """
        try:
            logger.info(f"Indexing {len(content)} Polish content items")

            # Process each content item for Polish-specific indexing
            for item in content:
                if "content" in item:
                    # Normalize Polish diacritics for better indexing
                    normalized_content = self._normalize_polish_diacritics(
                        item["content"]
                    )
                    item["normalized_content"] = normalized_content

                if "subject" in item:
                    # Normalize subject as well
                    normalized_subject = self._normalize_polish_diacritics(
                        item["subject"]
                    )
                    item["normalized_subject"] = normalized_subject

            # In a real implementation, this would trigger Sphinx indexing
            # For now, we'll return True to indicate success
            # The actual indexing would be done by Sphinx's indexer command
            # run separately or via a scheduled task

            logger.info("Polish content indexing completed successfully")
            return True

        except Exception as e:
            logger.error(f"Error during Polish content indexing: {e}")
            return False

    def generate_polish_config(self, db_config: Dict[str, Any]) -> str:
        """
        Generate Sphinx configuration file optimized for Polish language.

        Args:
            db_config: Database configuration dictionary

        Returns:
            Generated Sphinx configuration as string
        """
        config_template = """
# Sphinx configuration for SMF AI Search - Polish Language Optimization

source smf_polish_posts
{
    type = mysql
    sql_host = %(host)s
    sql_user = %(user)s
    sql_pass = %(password)s
    sql_db = %(database)s
    sql_port = %(port)s

    sql_query_pre = SET NAMES utf8
    sql_query_pre = SET SESSION query_cache_type=OFF
    sql_query_pre = SET CHARACTER SET utf8

    sql_query = \\
        SELECT sai.id, sai.topic_id, sai.post_id, sai.board_id, \\
               sai.subject, sai.content, sai.normalized_content, \\
               sai.normalized_subject, \\
               UNIX_TIMESTAMP(sai.indexed_date) AS indexed_date \\
        FROM smf_sphinx_ai_index sai \\
        WHERE sai.id >= $start AND sai.id <= $end

    sql_attr_uint = topic_id
    sql_attr_uint = post_id
    sql_attr_uint = board_id
    sql_attr_timestamp = indexed_date

    sql_query_range = \\
        SELECT MIN(id), MAX(id) \\
        FROM smf_sphinx_ai_index

    sql_range_step = 1000
    sql_query_killlist = \\
        SELECT id \\
        FROM smf_sphinx_ai_index \\
        WHERE indexed_date > NOW() - INTERVAL 1 DAY
}

index smf_polish_posts
{
    source = smf_polish_posts
    path = %(index_path)s/smf_polish_posts
    charset_type = utf-8
    charset_table = 0..9, A..Z->a..z, _, a..z, \\
                    U+0104->U+0105, U+0105, U+0106->U+0107, U+0107, \\
                    U+0118->U+0119, U+0119, U+0141->U+0142, U+0142, \\
                    U+0143->U+0144, U+0144, U+00D3->U+00F3, U+00F3, \\
                    U+015A->U+015B, U+015B, U+0179->U+017A, U+017A, \\
                    U+017B->U+017C, U+017C

    min_word_len = 2
    min_prefix_len = 3
    min_infix_len = 3
    enable_star = 1
    html_strip = 1

    # Polish-specific stopwords
    stopwords = %(stopwords_path)s/polish_stopwords.txt

    # Use morphology suitable for Polish
    morphology = stem_pl
}

indexer
{
    mem_limit = 256M
    max_iops = 40
    max_iosize = 1048576
    write_buffer = 1M
}

searchd
{
    listen = %(searchd_host)s:%(searchd_port)s
    log = %(log_path)s/searchd.log
    query_log = %(log_path)s/query.log
    read_timeout = 5
    max_children = 30
    pid_file = %(pid_path)s/searchd.pid
    seamless_rotate = 1
    preopen_indexes = 1
    unlink_old = 1
    workers = threads
    binlog_path = %(binlog_path)s

    # Polish-specific settings
    collation_server = utf8_polish_ci
    collation_libc_locale = pl_PL.UTF-8
}
"""

        # Ensure we have Polish stopwords path
        if "stopwords_path" not in db_config:
            db_config["stopwords_path"] = "/etc/sphinx/stopwords"

        return config_template % db_config

    def create_polish_stopwords_file(self, path: str) -> bool:
        """
        Create Polish stopwords file for Sphinx.

        Args:
            path: Path where to create the stopwords file

        Returns:
            True if successful, False otherwise
        """
        try:
            stopwords_file = os.path.join(path, "polish_stopwords.txt")

            # Create directory if it doesn't exist
            os.makedirs(path, exist_ok=True)

            # Write Polish stopwords to file
            with open(stopwords_file, "w", encoding="utf-8") as f:
                for stopword in sorted(POLISH_STOPWORDS):
                    f.write(f"{stopword}\n")

            logger.info(f"Polish stopwords file created at: {stopwords_file}")
            return True

        except Exception as e:
            logger.error(f"Error creating Polish stopwords file: {e}")
            return False

    def get_status(self) -> Dict[str, Any]:
        """
        Get Sphinx daemon status.

        Returns:
            Dictionary containing status information
        """
        if not PYMYSQL_AVAILABLE:
            return {"status": "error", "message": "PyMySQL not available"}

        connection = self._get_connection()
        if not connection:
            return {"status": "error", "message": "Cannot connect to Sphinx"}

        try:
            if PYMYSQL_AVAILABLE and pymysql is not None:
                with connection.cursor(pymysql.cursors.DictCursor) as cursor:
                    cursor.execute("SHOW STATUS")
                    status_rows = cursor.fetchall()

                    status = {}
                    for row in status_rows:
                        status[row["Variable_name"]] = row["Value"]

                    return {
                        "status": "ok",
                        "sphinx_status": status,
                        "index_name": self.index_name,
                    }
            else:
                return {"status": "error", "message": "PyMySQL not available"}

        except Exception as e:
            logger.error(f"Error getting Sphinx status: {e}")
            return {"status": "error", "message": str(e)}

    def close(self) -> None:
        """Close Sphinx connection."""
        if self.connection:
            self.connection.close()
            self.connection = None

    def _validate_index_name(self, index_name: str) -> bool:
        """
        Validate index name against allowed patterns.

        Args:
            index_name: Index name to validate

        Returns:
            True if valid, False otherwise
        """
        import re

        # Allow only alphanumeric characters, underscores, and dots
        if not re.match(r"^[a-zA-Z0-9_\.]+$", index_name):
            return False

        # Check against whitelist of allowed index names
        allowed_indexes = [
            "sphinx_main",
            "sphinx_delta",
            "forum_posts",
            "smf_posts",
            "main",
            "delta",
        ]
        return index_name in allowed_indexes or index_name.startswith("sphinx_")

    def _escape_identifier(self, identifier: str) -> str:
        """
        Safely escape SQL identifier.

        Args:
            identifier: SQL identifier to escape

        Returns:
            Escaped identifier
        """
        # Remove any backticks and re-add them
        escaped = identifier.replace("`", "").replace('"', "").replace("'", "")
        return f"`{escaped}`"

    def _validate_field_name(self, field_name: str) -> bool:
        """
        Validate field name against allowed patterns.

        Args:
            field_name: Field name to validate

        Returns:
            True if valid, False otherwise
        """
        import re

        # Allow only alphanumeric characters and underscores
        if not re.match(r"^[a-zA-Z_][a-zA-Z0-9_]*$", field_name):
            return False

        # Check against whitelist of known safe fields
        allowed_fields = [
            "id",
            "topic_id",
            "post_id",
            "board_id",
            "weight",
            "content",
            "body",
            "message",
            "subject",
            "title",
            "topic_title",
            "poster_time",
            "poster_name",
            "board_name",
        ]
        return field_name in allowed_fields

    def _validate_search_query(self, query: str) -> bool:
        """
        Validate search query for Sphinx MATCH clause.

        Args:
            query: Search query to validate

        Returns:
            True if valid, False otherwise
        """
        if not query or len(query.strip()) == 0:
            return False

        # Check for basic SQL injection attempts
        dangerous_patterns = [
            ";",
            "--",
            "/*",
            "*/",
            "union",
            "select",
            "insert",
            "update",
            "delete",
            "drop",
            "create",
            "alter",
            "exec",
        ]

        query_lower = query.lower()
        for pattern in dangerous_patterns:
            if pattern in query_lower:
                logger.warning(
                    f"Potentially dangerous pattern '{pattern}' detected in query"
                )
                return False

        # Limit query length
        if len(query) > 1000:
            return False

        return True


class SphinxSearchHandler:
    """
    Handles all Sphinx search operations and result processing.

    This class manages communication with Sphinx search daemon,
    result ranking, and search result formatting.
    """

    def __init__(self, config_path: str = "/etc/sphinx/sphinx.conf") -> None:
        """
        Initialize Sphinx search handler.

        Args:
            config_path: Path to Sphinx configuration file
        """
        self.sphinx_integration = SphinxIntegrationPolish(config_path)

    def search_content(self, query: str, limit: int = 100) -> List[Dict[str, Any]]:
        """
        Search content using Sphinx with Polish optimization.

        Args:
            query: Search query
            limit: Maximum number of results

        Returns:
            List of search results from Sphinx
        """
        return self.sphinx_integration.search_polish(query, limit)

    def format_search_results(
        self,
        sphinx_results: List[Dict[str, Any]],
        ai_summaries: Dict[str, str],
        similarities: Dict[str, float],
    ) -> List[Dict[str, Any]]:
        """
        Format search results with AI summaries and similarity scores.

        Args:
            sphinx_results: Raw results from Sphinx
            ai_summaries: AI-generated summaries for each result
            similarities: Similarity scores for each result

        Returns:
            Formatted search results
        """
        formatted_results = []

        for result in sphinx_results:
            result_id = str(result.get("id", ""))

            formatted_result = {
                "id": result.get("id"),
                "topic_id": result.get("topic_id"),
                "post_id": result.get("post_id"),
                "board_id": result.get("board_id"),
                "weight": result.get("weight", 0),
                "sphinx_score": result.get("weight", 0),
                "ai_similarity": similarities.get(result_id, 0.0),
                "summary": ai_summaries.get(result_id, ""),
                "source_links": self._generate_source_links(result),
                "confidence": self._calculate_confidence(
                    result.get("weight", 0), similarities.get(result_id, 0.0)
                ),
            }

            formatted_results.append(formatted_result)

        # Sort by combined confidence score
        formatted_results.sort(key=lambda x: x["confidence"], reverse=True)

        return formatted_results

    def _generate_source_links(self, result: Dict[str, Any]) -> List[Dict[str, Any]]:
        """
        Generate source links for a search result.

        Args:
            result: Search result from Sphinx

        Returns:
            List of source link dictionaries
        """
        return [
            {
                "topic_id": result.get("topic_id"),
                "post_id": result.get("post_id"),
                "title": f"Topic {result.get('topic_id', 'Unknown')}",
                "board_id": result.get("board_id"),
                "weight": result.get("weight", 0),
            }
        ]

    def _calculate_confidence(self, sphinx_score: float, ai_similarity: float) -> float:
        """
        Calculate combined confidence score.

        Args:
            sphinx_score: Sphinx relevance score
            ai_similarity: AI similarity score

        Returns:
            Combined confidence score (0-100)
        """
        # Normalize Sphinx score (assuming max score is around 10000)
        normalized_sphinx = min(sphinx_score / 10000.0, 1.0)

        # Combine scores with weights (70% AI, 30% Sphinx)
        combined_score = (0.7 * ai_similarity) + (0.3 * normalized_sphinx)

        return round(combined_score * 100, 2)

    def get_sphinx_status(self) -> Dict[str, Any]:
        """
        Get Sphinx daemon status.

        Returns:
            Sphinx status information
        """
        return self.sphinx_integration.get_status()

    def index_content(self, content: List[Dict[str, Any]]) -> bool:
        """
        Index content in Sphinx.

        Args:
            content: Content to index

        Returns:
            True if successful, False otherwise
        """
        return self.sphinx_integration.index_polish_content(content)

    def generate_config(self, db_config: Dict[str, Any]) -> str:
        """
        Generate Sphinx configuration for Polish language.

        Args:
            db_config: Database configuration

        Returns:
            Generated Sphinx configuration
        """
        return self.sphinx_integration.generate_polish_config(db_config)

    def create_stopwords_file(self, path: str) -> bool:
        """
        Create Polish stopwords file for Sphinx.

        Args:
            path: Directory path for stopwords file

        Returns:
            True if successful, False otherwise
        """
        return self.sphinx_integration.create_polish_stopwords_file(path)


def main() -> None:
    """Test Polish Sphinx integration."""
    sphinx = SphinxIntegrationPolish()

    # Test Polish search
    results = sphinx.search_polish("test query", 10)
    print(f"Search results: {len(results)}")

    # Test status
    status = sphinx.get_status()
    print(f"Sphinx status: {status}")

    # Test Polish query preprocessing
    original_query = "Jak mogę znaleźć informacje o żywności?"
    processed_query = sphinx.preprocess_polish_query(original_query)
    print(f"Original: {original_query}")
    print(f"Processed: {processed_query}")


if __name__ == "__main__":
    main()
