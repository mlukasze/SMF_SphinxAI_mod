#!/usr/bin/env python3
"""
Model conversion utilities for Sphinx AI Search.

This module provides utilities for converting models between different formats
(HuggingFace -> OpenVINO -> GenAI) and managing model installations.
"""

import logging
import shutil
from pathlib import Path
from typing import Dict, List, Optional, Tuple

from ..core.constants import (
    MODELS_DIR, ORIGINAL_DIR, OPENVINO_DIR, COMPRESSED_DIR, GENAI_DIR,
    DEFAULT_EMBEDDING_MODEL, DEFAULT_CHAT_MODEL
)

logger = logging.getLogger(__name__)

try:
    from optimum.intel import OVModelForSequenceClassification, OVModelForFeatureExtraction
    from transformers import AutoTokenizer, AutoModel
    OPTIMUM_AVAILABLE = True
except ImportError:
    logger.warning("Optimum Intel not available - model conversion limited")
    OPTIMUM_AVAILABLE = False

try:
    import openvino as ov
    OPENVINO_AVAILABLE = True
except ImportError:
    logger.warning("OpenVINO not available - model conversion limited")
    OPENVINO_AVAILABLE = False

try:
    import openvino_genai as ov_genai
    GENAI_AVAILABLE = True
except ImportError:
    logger.warning("OpenVINO GenAI not available - GenAI conversion limited")
    GENAI_AVAILABLE = False


class ModelConverter:
    """Handles model conversion between different formats."""
    
    def __init__(self, base_dir: Optional[Path] = None):
        """Initialize model converter.
        
        Args:
            base_dir: Base directory for model storage
        """
        self.base_dir = Path(base_dir) if base_dir else Path.cwd()
        self.models_dir = self.base_dir / MODELS_DIR
        self.original_dir = self.base_dir / ORIGINAL_DIR
        self.openvino_dir = self.base_dir / OPENVINO_DIR
        self.compressed_dir = self.base_dir / COMPRESSED_DIR
        self.genai_dir = self.base_dir / GENAI_DIR
        
        # Create directories if they don't exist
        for directory in [self.models_dir, self.original_dir, self.openvino_dir, 
                         self.compressed_dir, self.genai_dir]:
            directory.mkdir(parents=True, exist_ok=True)
    
    def download_huggingface_model(self, model_name: str, target_dir: Optional[Path] = None) -> Tuple[bool, str]:
        """Download model from HuggingFace Hub.
        
        Args:
            model_name: HuggingFace model identifier
            target_dir: Target directory for download
            
        Returns:
            Tuple of (success, message)
        """
        if not OPTIMUM_AVAILABLE:
            return False, "Optimum Intel not available for model download"
        
        try:
            if target_dir is None:
                target_dir = self.original_dir / model_name.replace('/', '_')
            
            target_dir.mkdir(parents=True, exist_ok=True)
            
            # Download tokenizer
            tokenizer = AutoTokenizer.from_pretrained(model_name)
            tokenizer.save_pretrained(str(target_dir))
            
            # Download model
            model = AutoModel.from_pretrained(model_name)
            model.save_pretrained(str(target_dir))
            
            logger.info(f"Downloaded HuggingFace model: {model_name} to {target_dir}")
            return True, f"Successfully downloaded model to {target_dir}"
        except Exception as e:
            error_msg = f"Failed to download model {model_name}: {e}"
            logger.error(error_msg)
            return False, error_msg
    
    def convert_to_openvino(self, model_path: Path, output_path: Optional[Path] = None, 
                           model_type: str = "embedding") -> Tuple[bool, str]:
        """Convert HuggingFace model to OpenVINO format.
        
        Args:
            model_path: Path to HuggingFace model
            output_path: Output path for OpenVINO model
            model_type: Type of model (embedding, classification, etc.)
            
        Returns:
            Tuple of (success, message)
        """
        if not OPTIMUM_AVAILABLE or not OPENVINO_AVAILABLE:
            return False, "Required libraries not available for OpenVINO conversion"
        
        try:
            if output_path is None:
                model_name = model_path.name
                output_path = self.openvino_dir / model_name
            
            output_path.mkdir(parents=True, exist_ok=True)
            
            # Choose appropriate converter based on model type
            if model_type == "embedding":
                ov_model = OVModelForFeatureExtraction.from_pretrained(
                    str(model_path), 
                    export=True
                )
            else:
                ov_model = OVModelForSequenceClassification.from_pretrained(
                    str(model_path), 
                    export=True
                )
            
            # Save OpenVINO model
            ov_model.save_pretrained(str(output_path))
            
            # Copy tokenizer
            tokenizer_files = list(model_path.glob("tokenizer*"))
            for tokenizer_file in tokenizer_files:
                shutil.copy2(tokenizer_file, output_path / tokenizer_file.name)
            
            logger.info(f"Converted model to OpenVINO: {output_path}")
            return True, f"Successfully converted to OpenVINO format: {output_path}"
        except Exception as e:
            error_msg = f"Failed to convert model to OpenVINO: {e}"
            logger.error(error_msg)
            return False, error_msg
    
    def compress_openvino_model(self, model_path: Path, output_path: Optional[Path] = None,
                               compression_ratio: float = 0.8) -> Tuple[bool, str]:
        """Compress OpenVINO model for better performance.
        
        Args:
            model_path: Path to OpenVINO model
            output_path: Output path for compressed model
            compression_ratio: Compression ratio (0-1)
            
        Returns:
            Tuple of (success, message)
        """
        if not OPENVINO_AVAILABLE:
            return False, "OpenVINO not available for model compression"
        
        try:
            if output_path is None:
                model_name = model_path.name
                output_path = self.compressed_dir / f"{model_name}_compressed"
            
            output_path.mkdir(parents=True, exist_ok=True)
            
            # Load and compress model
            core = ov.Core()
            model = core.read_model(str(model_path / "openvino_model.xml"))
            
            # Apply INT8 quantization for compression
            from openvino.tools import mo
            compressed_model = mo.compress_model(model)
            
            # Save compressed model
            ov.save_model(compressed_model, str(output_path / "openvino_model.xml"))
            
            # Copy other files
            for file_pattern in ["*.bin", "tokenizer*", "config.json"]:
                for file_path in model_path.glob(file_pattern):
                    shutil.copy2(file_path, output_path / file_path.name)
            
            logger.info(f"Compressed OpenVINO model: {output_path}")
            return True, f"Successfully compressed model: {output_path}"
        except Exception as e:
            error_msg = f"Failed to compress model: {e}"
            logger.error(error_msg)
            return False, error_msg
    
    def convert_to_genai(self, model_path: Path, output_path: Optional[Path] = None) -> Tuple[bool, str]:
        """Convert model to OpenVINO GenAI format.
        
        Args:
            model_path: Path to source model (HuggingFace or OpenVINO)
            output_path: Output path for GenAI model
            
        Returns:
            Tuple of (success, message)
        """
        if not GENAI_AVAILABLE:
            return False, "OpenVINO GenAI not available for conversion"
        
        try:
            if output_path is None:
                model_name = model_path.name
                output_path = self.genai_dir / f"{model_name}_genai"
            
            output_path.mkdir(parents=True, exist_ok=True)
            
            # Convert to GenAI format
            # Note: This is a placeholder - actual GenAI conversion API may differ
            ov_genai.convert_model(str(model_path), str(output_path))
            
            logger.info(f"Converted model to GenAI format: {output_path}")
            return True, f"Successfully converted to GenAI format: {output_path}"
        except Exception as e:
            error_msg = f"Failed to convert model to GenAI: {e}"
            logger.error(error_msg)
            return False, error_msg
    
    def get_model_info(self, model_path: Path) -> Dict[str, any]:
        """Get information about a model.
        
        Args:
            model_path: Path to model directory
            
        Returns:
            Dictionary with model information
        """
        info = {
            'path': str(model_path),
            'exists': model_path.exists(),
            'type': 'unknown',
            'files': [],
            'size_mb': 0
        }
        
        if not model_path.exists():
            return info
        
        # Get file list and total size
        total_size = 0
        files = []
        for file_path in model_path.rglob('*'):
            if file_path.is_file():
                size = file_path.stat().st_size
                total_size += size
                files.append({
                    'name': file_path.name,
                    'relative_path': str(file_path.relative_to(model_path)),
                    'size_mb': size / 1024 / 1024
                })
        
        info['files'] = files
        info['size_mb'] = total_size / 1024 / 1024
        
        # Determine model type
        if (model_path / "openvino_model.xml").exists():
            info['type'] = 'openvino'
        elif (model_path / "config.json").exists():
            info['type'] = 'huggingface'
        elif any(f['name'].endswith('.genai') for f in files):
            info['type'] = 'genai'
        
        return info
    
    def list_models(self) -> Dict[str, List[Dict[str, any]]]:
        """List all available models by type.
        
        Returns:
            Dictionary with model lists by type
        """
        model_lists = {
            'original': [],
            'openvino': [],
            'compressed': [],
            'genai': []
        }
        
        # Scan each directory
        for model_type, directory in [
            ('original', self.original_dir),
            ('openvino', self.openvino_dir),
            ('compressed', self.compressed_dir),
            ('genai', self.genai_dir)
        ]:
            if directory.exists():
                for model_dir in directory.iterdir():
                    if model_dir.is_dir():
                        info = self.get_model_info(model_dir)
                        model_lists[model_type].append(info)
        
        return model_lists
    
    def cleanup_model(self, model_path: Path) -> Tuple[bool, str]:
        """Remove a model directory and all its contents.
        
        Args:
            model_path: Path to model directory to remove
            
        Returns:
            Tuple of (success, message)
        """
        try:
            if model_path.exists() and model_path.is_dir():
                shutil.rmtree(model_path)
                logger.info(f"Cleaned up model: {model_path}")
                return True, f"Successfully removed model: {model_path}"
            else:
                return False, f"Model path does not exist: {model_path}"
        except Exception as e:
            error_msg = f"Failed to cleanup model: {e}"
            logger.error(error_msg)
            return False, error_msg


def get_default_models() -> List[str]:
    """Get list of default models to install.
    
    Returns:
        List of default model identifiers
    """
    return [
        DEFAULT_EMBEDDING_MODEL,
        DEFAULT_CHAT_MODEL
    ]


def install_default_models(base_dir: Optional[Path] = None) -> Dict[str, Tuple[bool, str]]:
    """Install default models for the system.
    
    Args:
        base_dir: Base directory for model installation
        
    Returns:
        Dictionary with installation results for each model
    """
    converter = ModelConverter(base_dir)
    results = {}
    
    for model_name in get_default_models():
        logger.info(f"Installing model: {model_name}")
        
        # Download from HuggingFace
        success, message = converter.download_huggingface_model(model_name)
        if not success:
            results[model_name] = (False, f"Download failed: {message}")
            continue
        
        # Convert to OpenVINO
        model_path = converter.original_dir / model_name.replace('/', '_')
        success, message = converter.convert_to_openvino(model_path)
        if not success:
            results[model_name] = (False, f"OpenVINO conversion failed: {message}")
            continue
        
        results[model_name] = (True, "Successfully installed and converted")
    
    return results
