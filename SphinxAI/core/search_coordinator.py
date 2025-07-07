#!/usr/bin/env python3
"""
Refactored Search Processor - Clean Architecture Implementation.

This module implements the main search coordinator following SOLID principles
and clean architecture patterns.
"""

import logging
from typing import Any, Dict, List, Optional

from ..core.interfaces import AIHandler, ProcessingResult, SearchHandler, SearchResult

logger = logging.getLogger(__name__)


class SearchCoordinator:
    """
    Main search coordinator following Single Responsibility Principle.

    This class only coordinates between different handlers and doesn't
    contain any business logic itself.
    """

    def __init__(
        self,
        sphinx_handler: SearchHandler,
        ai_handler: Optional[AIHandler] = None,
        genai_handler: Optional[AIHandler] = None,
        max_results: int = 10,
    ):
        """
        Initialize search coordinator with dependency injection.

        Args:
            sphinx_handler: Sphinx search handler
            ai_handler: Traditional AI handler (optional)
            genai_handler: GenAI handler (optional)
            max_results: Maximum results to return
        """
        self.sphinx_handler = sphinx_handler
        self.ai_handler = ai_handler
        self.genai_handler = genai_handler
        self.max_results = max_results

        logger.info("Search coordinator initialized")

    def search(
        self, query: str, search_options: Optional[Dict[str, Any]] = None
    ) -> ProcessingResult:
        """
        Perform comprehensive search with AI enhancements.

        Args:
            query: Search query
            search_options: Additional search options

        Returns:
            Processing result with search data
        """
        if not query or not query.strip():
            return ProcessingResult(
                success=False,
                message="Empty query provided",
                errors=["Query cannot be empty"],
            )

        options = search_options or {}
        search_type = options.get("type", "hybrid")
        use_ai_summary = options.get("use_ai_summary", True)
        use_genai = options.get("use_genai", True)

        try:
            logger.info(f"Processing search: '{query}' (type: {search_type})")

            # Step 1: Get basic search results from Sphinx
            sphinx_results = self._get_sphinx_results(query)
            if not sphinx_results:
                return ProcessingResult(
                    success=True, message="No results found", data=[]
                )

            # Step 2: Enhance with AI if available
            enhanced_results = self._enhance_results(
                query,
                sphinx_results,
                use_ai_summary,
                use_genai and self.genai_handler is not None,
            )

            # Step 3: Generate forum summary
            forum_summary = self._generate_forum_summary(
                query, enhanced_results, use_genai
            )

            # Step 4: Compile response
            response_data = {
                "query": query,
                "search_type": search_type,
                "total_results": len(enhanced_results),
                "results": [
                    result.to_dict() for result in enhanced_results[: self.max_results]
                ],
                "forum_summary": forum_summary,
                "ai_features_used": {
                    "traditional_ai": self.ai_handler is not None,
                    "genai": use_genai and self.genai_handler is not None,
                    "ai_summaries": use_ai_summary,
                },
            }

            logger.info(f"Search completed: {len(enhanced_results)} results")
            return ProcessingResult(
                success=True,
                message=f"Found {len(enhanced_results)} results",
                data=response_data,
            )

        except Exception as e:
            logger.error(f"Search processing error: {e}")
            return ProcessingResult(
                success=False, message="Search processing failed", errors=[str(e)]
            )

    def _get_sphinx_results(self, query: str) -> List[Dict[str, Any]]:
        """Get results from Sphinx handler."""
        try:
            return self.sphinx_handler.search(query, self.max_results * 2)
        except Exception as e:
            logger.error(f"Sphinx search error: {e}")
            return []

    def _enhance_results(
        self,
        query: str,
        sphinx_results: List[Dict[str, Any]],
        use_ai_summary: bool,
        use_genai: bool,
    ) -> List[SearchResult]:
        """Enhance search results with AI summaries."""
        enhanced_results = []

        for result in sphinx_results:
            search_result = SearchResult(
                result.get("id", ""),  # result_id as positional argument
                result.get("title", ""),  # title as positional argument
                result.get("content", ""),  # content as positional argument
                result.get("url", ""),  # url as positional argument
                relevance_score=result.get("weight", 0.0),
                metadata=result,
            )

            # Add AI summary if requested and available
            if use_ai_summary:
                search_result.ai_summary = self._generate_summary(
                    query, search_result.content, search_result.title, use_genai
                )

            enhanced_results.append(search_result)

        return enhanced_results

    def _generate_summary(
        self, query: str, content: str, title: str = "", use_genai: bool = True
    ) -> str:
        """Generate AI summary for content."""
        full_content = f"{title} {content}" if title else content

        try:
            # Try GenAI first if available and requested
            if use_genai and self.genai_handler:
                summary = self.genai_handler.generate_summary(query, full_content)
                if summary:
                    return summary

            # Fallback to traditional AI
            if self.ai_handler:
                return self.ai_handler.generate_summary(query, full_content)

            # Simple fallback
            return self._simple_summary(full_content)

        except Exception as e:
            logger.error(f"Summary generation error: {e}")
            return "Nie udało się wygenerować streszczenia."

    def _generate_forum_summary(
        self, query: str, results: List[SearchResult], use_genai: bool
    ) -> str:
        """Generate overall forum summary."""
        if not results:
            return "Nie znaleziono wyników dla podanego zapytania."

        try:
            # Try GenAI forum summary if available
            if (
                use_genai
                and self.genai_handler
                and hasattr(self.genai_handler, "generate_forum_summary")
            ):
                result_dicts = [result.to_dict() for result in results[:5]]
                summary = self.genai_handler.generate_forum_summary(query, result_dicts)
                if summary:
                    return summary

            # Fallback summary
            return self._simple_forum_summary(query, results)

        except Exception as e:
            logger.error(f"Forum summary generation error: {e}")
            return self._simple_forum_summary(query, results)

    def _simple_summary(self, content: str, max_length: int = 200) -> str:
        """Simple text summarization fallback."""
        if not content:
            return ""

        if len(content) <= max_length:
            return content

        # Find sentence boundaries
        sentences = content.split(".")
        summary = ""
        for sentence in sentences:
            if len(summary + sentence) <= max_length:
                summary += sentence + ". "
            else:
                break

        return summary.strip() or content[:max_length] + "..."

    def _simple_forum_summary(self, query: str, results: List[SearchResult]) -> str:
        """Simple forum summary fallback."""
        count = len(results)
        summary = f"Znaleziono {count} wyników dla zapytania '{query}'. "

        if results:
            first_result = results[0]
            summary += f"Najbardziej pasujący wynik: {first_result.title}."

        return summary

    def get_system_status(self) -> Dict[str, Any]:
        """Get status of all system components."""
        status: Dict[str, Any] = {
            "coordinator": "active",
            "max_results": self.max_results,
            "handlers": {},
        }

        # Sphinx handler status
        try:
            status["handlers"]["sphinx"] = self.sphinx_handler.get_status()
        except Exception as e:
            status["handlers"]["sphinx"] = {"error": str(e)}

        # AI handler status
        if self.ai_handler:
            try:
                status["handlers"]["ai"] = self.ai_handler.get_model_info()
            except Exception as e:
                status["handlers"]["ai"] = {"error": str(e)}
        else:
            status["handlers"]["ai"] = "not_available"

        # GenAI handler status
        if self.genai_handler:
            try:
                status["handlers"]["genai"] = self.genai_handler.get_model_info()
            except Exception as e:
                status["handlers"]["genai"] = {"error": str(e)}
        else:
            status["handlers"]["genai"] = "not_available"

        return status


class SearchAPIHandler:
    """
    API handler for search requests following Single Responsibility Principle.

    This class handles API requests and delegates to the search coordinator.
    """

    def __init__(self, coordinator: SearchCoordinator):
        """
        Initialize API handler.

        Args:
            coordinator: Search coordinator instance
        """
        self.coordinator = coordinator

    def handle_search_request(self, request_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Handle search API request.

        Args:
            request_data: Request data from API

        Returns:
            API response dictionary
        """
        try:
            # Validate request
            validation_result = self._validate_request(request_data)
            if not validation_result.success:
                return {
                    "success": False,
                    "error": validation_result.message,
                    "errors": validation_result.errors,
                }

            # Extract parameters
            query = request_data.get("query", "").strip()
            search_options = request_data.get("options", {})

            # Perform search
            result = self.coordinator.search(query, search_options)

            # Format response
            return {
                "success": result.success,
                "message": result.message,
                "data": result.data,
                "errors": result.errors,
            }

        except Exception as e:
            logger.error(f"API request handling error: {e}")
            return {
                "success": False,
                "error": "Internal server error",
                "errors": [str(e)],
            }

    def handle_status_request(self) -> Dict[str, Any]:
        """Handle system status request."""
        try:
            status = self.coordinator.get_system_status()
            return {"success": True, "data": status}
        except Exception as e:
            logger.error(f"Status request error: {e}")
            return {
                "success": False,
                "error": "Failed to get system status",
                "errors": [str(e)],
            }

    def _validate_request(self, request_data: Dict[str, Any]) -> ProcessingResult:
        """Validate API request data."""
        if not isinstance(request_data, dict):
            return ProcessingResult(
                success=False,
                message="Invalid request format",
                errors=["Request must be a JSON object"],
            )

        query = request_data.get("query", "")
        if not query or not isinstance(query, str) or not query.strip():
            return ProcessingResult(
                success=False,
                message="Invalid query",
                errors=["Query is required and must be a non-empty string"],
            )

        # Validate options if provided
        options = request_data.get("options", {})
        if options and not isinstance(options, dict):
            return ProcessingResult(
                success=False,
                message="Invalid options format",
                errors=["Options must be a JSON object"],
            )

        return ProcessingResult(success=True, message="Request valid")


def create_search_coordinator(
    sphinx_handler: SearchHandler,
    ai_handler: Optional[AIHandler] = None,
    genai_handler: Optional[AIHandler] = None,
    max_results: int = 10,
) -> SearchCoordinator:
    """
    Factory function to create search coordinator.

    Args:
        sphinx_handler: Sphinx search handler
        ai_handler: Traditional AI handler
        genai_handler: GenAI handler
        max_results: Maximum results

    Returns:
        Configured search coordinator
    """
    return SearchCoordinator(sphinx_handler, ai_handler, genai_handler, max_results)


def create_api_handler(coordinator: SearchCoordinator) -> SearchAPIHandler:
    """
    Factory function to create API handler.

    Args:
        coordinator: Search coordinator

    Returns:
        Configured API handler
    """
    return SearchAPIHandler(coordinator)
