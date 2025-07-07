#!/usr/bin/env python3
"""
Main entry point for Sphinx AI Search Plugin.

This module provides CLI interface and orchestration for the Sphinx AI Search system.
It integrates Sphinx search with AI handlers for enhanced search capabilities.
"""

import argparse
import json
import logging
import sys
from pathlib import Path
from typing import Any, Dict, Optional, cast

from SphinxAI.core.constants import PLUGIN_NAME, VERSION, config_manager
from SphinxAI.core.interfaces import SearchHandler
from SphinxAI.core.search_coordinator import SearchCoordinator
from SphinxAI.handlers.genai_handler import GenAIHandler
from SphinxAI.handlers.sphinx_handler import SphinxSearchHandler

# Configure logging
logging.basicConfig(
    level=logging.INFO, format="%(asctime)s - %(name)s - %(levelname)s - %(message)s"
)
logger = logging.getLogger(__name__)


def load_configuration() -> Dict[str, Any]:
    """Load configuration from INI and JSON files

    Returns:
        Combined configuration dictionary
    """
    if not config_manager.config_exists():
        logger.error(
            "No configuration files found. Please ensure config.ini or config.json exists."
        )
        sys.exit(1)

    try:
        config = config_manager.load_config()
        logger.info("Configuration loaded successfully")
        return config
    except Exception as e:
        logger.error("Failed to load configuration: %s", e)
        sys.exit(1)


def setup_handlers(config: Dict[str, Any]) -> Dict[str, Any]:
    """Setup search and AI handlers based on configuration.

    Args:
        config: Configuration dictionary

    Returns:
        Dictionary with initialized handlers
    """
    handlers: Dict[str, Any] = {}

    # Setup Sphinx handler
    sphinx_config = config.get("sphinx", {})
    sphinx_handler = SphinxSearchHandler(
        host=sphinx_config.get("host", "localhost"),
        port=sphinx_config.get("port", 9306),
        index_name=sphinx_config.get("index", "smf_posts"),
    )
    handlers["sphinx"] = sphinx_handler

    # Setup AI handlers based on availability
    ai_config = config.get("ai", {})

    # GenAI handler (replaces the old OpenVINO handler)
    genai_model_path = ai_config.get("genai_model_path") or ai_config.get(
        "openvino_model_path"
    )
    if genai_model_path:
        genai_handler = GenAIHandler(
            model_path=genai_model_path, device=ai_config.get("device", "CPU")
        )
        if genai_handler.is_available():
            handlers["genai"] = genai_handler
            logger.info("GenAI handler initialized")

    return handlers


def load_config(config_path: Optional[Path] = None) -> Dict[str, Any]:
    """Load configuration using the new config manager.

    Args:
        config_path: Path to configuration file (deprecated, kept for compatibility)

    Returns:
        Configuration dictionary
    """
    # Use the new configuration manager
    return load_configuration()


def handle_search(args: argparse.Namespace) -> None:
    """Handle search command.

    Args:
        args: Parsed command line arguments
    """
    try:
        # Load configuration
        config = load_config(Path(args.config) if args.config else None)

        # Setup handlers
        handlers = setup_handlers(config)

        # Initialize search coordinator
        ai_handlers_list = [h for k, h in handlers.items() if k != "sphinx"]
        sphinx_handler = handlers.get("sphinx")
        if sphinx_handler is None:
            logger.error("Sphinx handler not available")
            sys.exit(1)
            
        coordinator = SearchCoordinator(
            sphinx_handler=sphinx_handler,
            ai_handler=ai_handlers_list[0] if ai_handlers_list else None,
            genai_handler=handlers.get("genai"),
        )

        # Perform search
        if args.input_file:
            # Read search data from file (for SMF integration)
            with open(args.input_file, "r", encoding="utf-8") as f:
                search_data = json.load(f)

            query = search_data.get("query", "")
            context = search_data.get("context", {})
        else:
            # Direct query from command line
            query = args.query
            context = {}

        if not query:
            logger.error("No search query provided")
            sys.exit(1)

        # Execute search
        results = coordinator.search(query, context)

        # Output results
        if args.output_file:
            with open(args.output_file, "w", encoding="utf-8") as f:
                json.dump(results, f, ensure_ascii=False, indent=2)
        else:
            print(json.dumps(results, ensure_ascii=False, indent=2))

    except Exception as e:
        logger.error("Search failed: %s", e)
        sys.exit(1)


def handle_model_install(args: argparse.Namespace) -> None:
    """Handle model installation command.

    Args:
        args: Parsed command line arguments
    """
    print("Model installation has been moved to the unified converter.")
    print("Please use: python unified_model_converter.py --help")
    print("")
    print("Examples:")
    print("  python unified_model_converter.py --all")
    print("  python unified_model_converter.py --embedding-model multilingual_mpnet")
    print("  python unified_model_converter.py --llm-model chat")
    print("  python unified_model_converter.py --list")


def handle_model_list(args: argparse.Namespace) -> None:
    """Handle model listing command.

    Args:
        args: Parsed command line arguments
    """
    print("Model listing has been moved to the unified converter.")
    print("Please use: python unified_model_converter.py --list")


def handle_status(args: argparse.Namespace) -> None:
    """Handle status command.

    Args:
        args: Parsed command line arguments
    """
    try:
        config = load_config(Path(args.config) if args.config else None)
        handlers = setup_handlers(config)

        print(f"=== {PLUGIN_NAME} v{VERSION} - Status ===\n")

        for name, handler in handlers.items():
            if hasattr(handler, "get_status"):
                status = handler.get_status()
                print(f"{name.upper()} Handler:")
                for key, value in status.items():
                    print(f"  {key}: {value}")
                print()

    except Exception as e:
        logger.error("Status check failed: %s", e)
        sys.exit(1)


def create_parser() -> argparse.ArgumentParser:
    """Create command line argument parser.

    Returns:
        Configured argument parser
    """
    parser = argparse.ArgumentParser(
        description=f"{PLUGIN_NAME} v{VERSION} - AI-powered search for SMF forums"
    )

    parser.add_argument("--config", "-c", help="Configuration file path", type=str)

    subparsers = parser.add_subparsers(dest="command", help="Available commands")

    # Search command
    search_parser = subparsers.add_parser("search", help="Perform AI-enhanced search")
    search_parser.add_argument("query", nargs="?", help="Search query")
    search_parser.add_argument(
        "--input-file", "-i", help="Input file with search data (JSON)"
    )
    search_parser.add_argument(
        "--output-file", "-o", help="Output file for results (JSON)"
    )

    # Model management commands
    install_parser = subparsers.add_parser("install-models", help="Install AI models")
    install_parser.add_argument("--model-name", help="Specific model to install")
    install_parser.add_argument("--models-dir", help="Models directory")
    install_parser.add_argument(
        "--convert-openvino",
        action="store_true",
        help="Convert to OpenVINO format after download",
    )

    list_parser = subparsers.add_parser("list-models", help="List available models")
    list_parser.add_argument("--models-dir", help="Models directory")

    # Status command
    status_parser = subparsers.add_parser("status", help="Show system status")

    return parser


def main() -> None:
    """Main entry point."""
    parser = create_parser()
    args = parser.parse_args()

    if not args.command:
        parser.print_help()
        return

    # Route to appropriate handler
    if args.command == "search":
        handle_search(args)
    elif args.command == "install-models":
        handle_model_install(args)
    elif args.command == "list-models":
        handle_model_list(args)
    elif args.command == "status":
        handle_status(args)
    else:
        print(f"Error: Unknown command: {args.command}", file=sys.stderr)
        parser.print_help()
        sys.exit(1)


if __name__ == "__main__":
    main()
