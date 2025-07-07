#!/usr/bin/env python3
"""
Sphinx Search Handler - Clean Implementation.

This module handles all Sphinx search operations following Single Responsibility Principle.
"""

import logging
from typing import Any, Dict, List, Optional

logger = logging.getLogger(__name__)

try:
    import pymysql
    import pymysql.cursors

    PYMYSQL_AVAILABLE = True
except ImportError:
    logger.warning("PyMySQL not available")
    PYMYSQL_AVAILABLE = False
    pymysql = None


class SphinxSearchHandler:
    """
    Clean Sphinx search handler implementing SearchHandler interface.

    This class is focused only on Sphinx search operations and follows
    the Single Responsibility Principle.
    """

    def __init__(
        self,
        host: str = "localhost",
        port: int = 9306,
        index_name: str = "forum_posts",
        connection_timeout: int = 10,
    ):
        """
        Initialize Sphinx handler.

        Args:
            host: Sphinx server host
            port: Sphinx server port
            index_name: Index name to search
            connection_timeout: Connection timeout in seconds
        """
        self.host = host
        self.port = port
        self.index_name = index_name
        self.connection_timeout = connection_timeout
        self.connection = None

        if not PYMYSQL_AVAILABLE:
            raise ImportError("PyMySQL is required for Sphinx integration")

        logger.info(f"Sphinx handler initialized: {host}:{port}")

    def search(self, query: str, max_results: int = 10) -> List[Dict[str, Any]]:
        """
        Perform Sphinx search.

        Args:
            query: Search query
            max_results: Maximum results to return

        Returns:
            List of search results
        """
        if not query or not query.strip():
            return []

        try:
            connection = self._get_connection()
            if not connection:
                logger.error("Failed to connect to Sphinx")
                return []

            with connection.cursor() as cursor:
                # Use parameterized query to prevent SQL injection
                sql = f"""
                    SELECT id, weight(), subject, content, topic_id, post_id,
                           board_id, board_name, num_replies, num_views
                    FROM {self.index_name}
                    WHERE MATCH(%s)
                    ORDER BY weight() DESC, id DESC
                    LIMIT %s
                """

                cursor.execute(sql, (query, max_results))
                results = cursor.fetchall()

                return self._format_results(results)

        except Exception as e:
            logger.error(f"Sphinx search error: {e}")
            return []
        finally:
            self._close_connection()

    def get_status(self) -> Dict[str, Any]:
        """Get Sphinx handler status."""
        status = {
            "handler": "sphinx",
            "host": self.host,
            "port": self.port,
            "index": self.index_name,
            "pymysql_available": PYMYSQL_AVAILABLE,
        }

        try:
            connection = self._get_connection()
            if connection:
                status["connection"] = "active"
                # Test query to check index
                with connection.cursor() as cursor:
                    cursor.execute(f"SHOW TABLES LIKE '{self.index_name}'")
                    result = cursor.fetchone()
                    status["index_exists"] = result is not None
            else:
                status["connection"] = "failed"
                status["index_exists"] = False

        except Exception as e:
            status["connection"] = "error"
            status["error"] = str(e)
        finally:
            self._close_connection()

        return status

    def _get_connection(self):
        """Get database connection to Sphinx."""
        try:
            if self.connection and self.connection.open:
                return self.connection

            self.connection = pymysql.connect(
                host=self.host,
                port=self.port,
                charset="utf8mb4",
                cursorclass=pymysql.cursors.DictCursor,
                connect_timeout=self.connection_timeout,
                autocommit=True,
            )

            return self.connection

        except Exception as e:
            logger.error(f"Sphinx connection error: {e}")
            return None

    def _close_connection(self) -> None:
        """Close database connection."""
        if self.connection:
            try:
                self.connection.close()
            except Exception as e:
                logger.warning(f"Error closing Sphinx connection: {e}")
            finally:
                self.connection = None

    def _format_results(
        self, raw_results: List[Dict[str, Any]]
    ) -> List[Dict[str, Any]]:
        """Format raw Sphinx results."""
        formatted_results = []

        for result in raw_results:
            formatted_result = {
                "id": str(result.get("id", "")),
                "title": result.get("subject", ""),
                "content": result.get("content", ""),
                "weight": float(result.get("weight()", 0)),
                "topic_id": result.get("topic_id"),
                "post_id": result.get("post_id"),
                "board_id": result.get("board_id"),
                "board_name": result.get("board_name", "Unknown"),
                "num_replies": int(result.get("num_replies", 0)),
                "num_views": int(result.get("num_views", 0)),
                "url": self._generate_post_url(result),
            }

            formatted_results.append(formatted_result)

        return formatted_results

    def _generate_post_url(self, result: Dict[str, Any]) -> str:
        """Generate URL for forum post."""
        topic_id = result.get("topic_id")
        post_id = result.get("post_id")

        if topic_id and post_id:
            return f"index.php?topic={topic_id}.msg{post_id}#msg{post_id}"
        elif topic_id:
            return f"index.php?topic={topic_id}"
        else:
            return ""


def create_sphinx_handler(
    host: str = "localhost",
    port: int = 9306,
    index_name: str = "forum_posts",
    connection_timeout: int = 10,
) -> SphinxSearchHandler:
    """
    Factory function to create Sphinx handler.

    Args:
        host: Sphinx server host
        port: Sphinx server port
        index_name: Index name
        connection_timeout: Connection timeout

    Returns:
        Configured Sphinx handler
    """
    return SphinxSearchHandler(host, port, index_name, connection_timeout)
