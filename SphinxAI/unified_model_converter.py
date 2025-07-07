#!/usr/bin/env python3
"""
Unified Model Converter for SMF Sphinx AI Search Plugin.

This script provides a unified interface for converting both embedding models
and LLM models to their respective OpenVINO formats:
- Embedding models -> OpenVINO IR format (for OpenVINOHandler)
- LLM models -> OpenVINO GenAI format (for GenAIHandler)
"""

import argparse
import configparser
import logging
import os
import shutil
import subprocess
import sys
from pathlib import Path
from typing import Any, Dict, Optional

import numpy as np
import torch
from sentence_transformers import SentenceTransformer

logger = logging.getLogger(__name__)


class UnifiedModelConverter:
    """Unified converter for both embedding and LLM models."""

    def __init__(self, output_dir: str = "SphinxAI/models") -> None:
        """
        Initialize unified model converter.

        Args:
            output_dir: Base directory to store converted models
        """
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)

        # Embedding models configuration (for OpenVINO IR)
        self.embedding_models = {
            "multilingual_mpnet": {
                "name": "sentence-transformers/paraphrase-multilingual-mpnet-base-v2",
                "description": "Multilingual sentence embeddings (Polish support)",
                "format": "openvino_ir",
            },
            "polish_roberta": {
                "name": "sdadas/polish-roberta-large-v2",
                "description": "Polish-specific embeddings",
                "format": "openvino_ir",
            },
        }

        # LLM models configuration (for OpenVINO GenAI)
        self.llm_models = {
            "chat": {
                "name": "TinyLlama/TinyLlama-1.1B-Chat-v1.0",
                "format": "int4",
                "description": "Small chat model optimized for Polish forum Q&A",
            },
            "summarization": {
                "name": "microsoft/DialoGPT-medium",
                "format": "int4",
                "description": "Medium model for text summarization",
            },
            "multilingual_chat": {
                "name": "HuggingFaceH4/zephyr-7b-beta",
                "format": "int8",
                "description": "Multilingual chat model (larger, better quality)",
            },
        }

        # Load config and Hugging Face token
        # Priority: config.ini -> HUGGING_FACE_HUB_TOKEN -> HF_TOKEN
        self.config = self._load_config()
        self.hf_token = (
            self.config.get("huggingface", {}).get("token", "")
            or os.environ.get("HUGGING_FACE_HUB_TOKEN", "")
            or os.environ.get("HF_TOKEN", "")
        )

    def _load_config(self) -> Dict[str, Any]:
        """Load configuration from config file."""
        config_path = Path(__file__).parent / "config.ini"
        if not config_path.exists():
            logger.warning(f"Config file not found: {config_path}")
            return {}

        try:
            config = configparser.ConfigParser()
            config.read(config_path)
            return {section: dict(config[section]) for section in config.sections()}
        except Exception as e:
            logger.error(f"Failed to load config: {e}")
            return {}

    def check_dependencies(self) -> bool:
        """Check if required dependencies are available."""
        try:
            # Check embedding dependencies
            import sentence_transformers
            import torch

            logger.info("✅ Embedding conversion dependencies available")

            # Check LLM dependencies
            try:
                import openvino_genai

                logger.info("✅ OpenVINO GenAI available")
            except ImportError:
                logger.warning("⚠️ OpenVINO GenAI not available for LLM conversion")

            # Check optimum-cli
            result = subprocess.run(
                ["optimum-cli", "--help"], capture_output=True, text=True
            )
            if result.returncode == 0:
                logger.info("✅ optimum-cli available")
            else:
                logger.warning("⚠️ optimum-cli not available for GenAI conversion")

            return True

        except ImportError as e:
            logger.error(f"❌ Missing dependencies: {e}")
            return False

    def convert_embedding_model(self, model_key: str, force: bool = False) -> bool:
        """
        Convert embedding model to OpenVINO IR format.

        Args:
            model_key: Key from embedding_models dict
            force: Force conversion even if model exists

        Returns:
            True if successful
        """
        if model_key not in self.embedding_models:
            logger.error(f"Unknown embedding model: {model_key}")
            return False

        model_config = self.embedding_models[model_key]
        model_name = model_config["name"]

        output_path = self.output_dir / "embeddings" / model_key

        # Check if already exists
        if output_path.exists() and not force:
            logger.info(f"Embedding model {model_key} already exists")
            return True

        # Remove existing if force
        if output_path.exists() and force:
            shutil.rmtree(output_path)

        output_path.mkdir(parents=True, exist_ok=True)

        logger.info(f"Converting embedding model: {model_name}")
        logger.info(f"Output: {output_path}")

        try:
            # Download and save SentenceTransformer model
            model_kwargs: Dict[str, Any] = {}
            if self.hf_token and not self.hf_token.startswith("#"):
                model_kwargs["use_auth_token"] = self.hf_token.strip()

            model = SentenceTransformer(model_name, **model_kwargs)

            # Save original model
            original_path = output_path / "sentence_transformer"
            model.save(str(original_path))

            # Convert to OpenVINO IR using optimum
            try:
                from optimum.intel.openvino import OVModelForFeatureExtraction

                ov_model = OVModelForFeatureExtraction.from_pretrained(
                    model_name, export=True, **model_kwargs
                )
                ov_model.save_pretrained(output_path / "openvino_ir")

                logger.info(f"✅ Embedding model {model_key} converted successfully")
                return True

            except ImportError:
                logger.warning(
                    "Optimum Intel not available, saving SentenceTransformer only"
                )
                return True

        except Exception as e:
            logger.error(f"❌ Failed to convert embedding model {model_key}: {e}")
            return False

    def convert_llm_model(
        self, model_key: str, trust_remote_code: bool = True, force: bool = False
    ) -> bool:
        """
        Convert LLM model to OpenVINO GenAI format.

        Args:
            model_key: Key from llm_models dict
            trust_remote_code: Whether to trust remote code
            force: Force conversion even if model exists

        Returns:
            True if successful
        """
        if model_key not in self.llm_models:
            logger.error(f"Unknown LLM model: {model_key}")
            return False

        model_config = self.llm_models[model_key]
        model_name = model_config["name"]
        weight_format = model_config["format"]

        output_path = self.output_dir / "genai" / model_key

        # Check if already exists
        if output_path.exists() and not force:
            logger.info(f"LLM model {model_key} already exists")
            return True

        # Remove existing if force
        if output_path.exists() and force:
            shutil.rmtree(output_path)

        logger.info(f"Converting LLM model: {model_name}")
        logger.info(f"Output: {output_path}")
        logger.info(f"Weight format: {weight_format}")

        try:
            # Build optimum-cli command
            cmd = [
                "optimum-cli",
                "export",
                "openvino",
                "--model",
                model_name,
                "--weight-format",
                weight_format,
                str(output_path),
            ]

            if trust_remote_code:
                cmd.append("--trust-remote-code")

            if self.hf_token and not self.hf_token.startswith("#"):
                cmd.extend(["--token", self.hf_token.strip()])

            logger.info(f"Running: {' '.join(cmd)}")

            # Run conversion
            result = subprocess.run(
                cmd, capture_output=True, text=True, timeout=1800  # 30 minutes timeout
            )

            if result.returncode == 0:
                logger.info(f"✅ LLM model {model_key} converted successfully")
                self._verify_genai_conversion(output_path)
                return True
            else:
                logger.error(f"❌ LLM conversion failed for {model_key}")
                logger.error(f"Error: {result.stderr}")
                return False

        except subprocess.TimeoutExpired:
            logger.error(f"❌ LLM conversion timed out for {model_key}")
            return False
        except Exception as e:
            logger.error(f"❌ Failed to convert LLM model {model_key}: {e}")
            return False

    def _verify_genai_conversion(self, output_path: Path) -> bool:
        """Verify GenAI model conversion."""
        required_files = ["openvino_model.xml", "openvino_model.bin"]

        for file in required_files:
            if not (output_path / file).exists():
                logger.warning(f"Missing file after conversion: {file}")
                return False

        logger.info("✅ GenAI conversion verified")
        return True

    def convert_all_embedding_models(self, force: bool = False) -> Dict[str, bool]:
        """Convert all embedding models."""
        results = {}
        for model_key in self.embedding_models.keys():
            logger.info(f"\n📦 Converting embedding model: {model_key}")
            results[model_key] = self.convert_embedding_model(model_key, force)
        return results

    def convert_all_llm_models(self, force: bool = False) -> Dict[str, bool]:
        """Convert all LLM models."""
        results = {}
        for model_key in self.llm_models.keys():
            logger.info(f"\n📦 Converting LLM model: {model_key}")
            results[model_key] = self.convert_llm_model(model_key, force=force)
        return results

    def list_models(self) -> None:
        """List available models for conversion."""
        print("\n🔤 Available Embedding Models (for OpenVINO IR):")
        for key, config in self.embedding_models.items():
            print(f"  {key}: {config['name']}")
            print(f"    {config['description']}")

        print("\n🤖 Available LLM Models (for OpenVINO GenAI):")
        for key, config in self.llm_models.items():
            print(f"  {key}: {config['name']}")
            print(f"    {config['description']} (format: {config['format']})")

    def cleanup_original_models(self) -> None:
        """Remove original downloaded models to save space."""
        patterns = ["sentence_transformer", "original", "cache"]
        for pattern in patterns:
            for path in self.output_dir.rglob(pattern):
                if path.is_dir():
                    logger.info(f"Removing: {path}")
                    shutil.rmtree(path)


def main():
    """Main function for CLI usage."""
    parser = argparse.ArgumentParser(
        description="Unified Model Converter for SMF Sphinx AI"
    )
    parser.add_argument("--output-dir", default="models", help="Output directory")
    parser.add_argument("--embedding-model", help="Convert specific embedding model")
    parser.add_argument("--llm-model", help="Convert specific LLM model")
    parser.add_argument(
        "--all-embeddings", action="store_true", help="Convert all embedding models"
    )
    parser.add_argument(
        "--all-llms", action="store_true", help="Convert all LLM models"
    )
    parser.add_argument("--all", action="store_true", help="Convert all models")
    parser.add_argument("--list", action="store_true", help="List available models")
    parser.add_argument(
        "--force", action="store_true", help="Force conversion even if exists"
    )
    parser.add_argument(
        "--cleanup", action="store_true", help="Cleanup original models"
    )
    parser.add_argument("--verbose", "-v", action="store_true", help="Verbose logging")

    args = parser.parse_args()

    # Setup logging
    level = logging.DEBUG if args.verbose else logging.INFO
    logging.basicConfig(level=level, format="%(levelname)s: %(message)s")

    converter = UnifiedModelConverter(args.output_dir)

    if args.list:
        converter.list_models()
        return

    if not converter.check_dependencies():
        logger.error("Missing required dependencies")
        sys.exit(1)

    success = True

    if args.embedding_model:
        success &= converter.convert_embedding_model(args.embedding_model, args.force)

    if args.llm_model:
        success &= converter.convert_llm_model(args.llm_model, force=args.force)

    if args.all_embeddings or args.all:
        results = converter.convert_all_embedding_models(args.force)
        success &= all(results.values())

    if args.all_llms or args.all:
        results = converter.convert_all_llm_models(args.force)
        success &= all(results.values())

    if args.cleanup:
        converter.cleanup_original_models()

    if not any(
        [
            args.embedding_model,
            args.llm_model,
            args.all_embeddings,
            args.all_llms,
            args.all,
            args.cleanup,
        ]
    ):
        parser.print_help()

    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()
