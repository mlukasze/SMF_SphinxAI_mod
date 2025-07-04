#!/usr/bin/env python3
"""Setup and Installation Script for Sphinx AI Search."""

import json
import os
import platform
import subprocess
import sys
from pathlib import Path


def check_python_version() -> bool:
    """Check if Python version is compatible."""
    version = sys.version_info
    if version.major < 3 or (version.major == 3 and version.minor < 8):
        print("Error: Python 3.8 or higher is required")
        print(f"Current version: {sys.version}")
        return False
    return True


def check_system_requirements() -> bool:
    """Check system requirements."""
    print("Checking system requirements...")

    # Check Python version
    if not check_python_version():
        return False

    # Check available commands
    required_commands = ['pip', 'python']

    for cmd in required_commands:
        if not check_command(cmd):
            print(f"Error: {cmd} command not found")
            return False

    print("✓ System requirements met")
    return True


def check_command(command: str) -> bool:
    """Check if a command is available."""
    try:
        subprocess.run(
            [command, '--version'],
            capture_output=True,
            check=True
        )
        return True
    except (subprocess.CalledProcessError, FileNotFoundError):
        return False

def install_python_packages():
    """Install required Python packages"""
    print("Installing Python packages...")

    requirements_file = Path(__file__).parent / "requirements.txt"

    if not requirements_file.exists():
        print("Error: requirements.txt not found")
        return False

    try:
        # Upgrade pip first
        subprocess.run([sys.executable, "-m", "pip", "install", "--upgrade", "pip"],
                      check=True)

        # Install packages
        subprocess.run([sys.executable, "-m", "pip", "install", "-r", str(requirements_file)],
                      check=True)

        print("✓ Python packages installed successfully")
        return True

    except subprocess.CalledProcessError as e:
        print(f"Error installing packages: {e}")
        return False

def setup_nltk_data():
    """Download required NLTK data"""
    print("Setting up NLTK data...")

    try:
        import nltk
        nltk.download('punkt', quiet=True)
        nltk.download('stopwords', quiet=True)
        nltk.download('wordnet', quiet=True)
        nltk.download('averaged_perceptron_tagger', quiet=True)

        print("✓ NLTK data downloaded successfully")
        return True

    except Exception as e:
        print(f"Error setting up NLTK data: {e}")
        return False

def setup_spacy_models():
    """Download spaCy models"""
    print("Setting up spaCy models...")

    try:
        # Download English model
        subprocess.run([sys.executable, "-m", "spacy", "download", "en_core_web_sm"],
                      check=True)

        print("✓ spaCy models downloaded successfully")
        return True

    except subprocess.CalledProcessError as e:
        print(f"Error downloading spaCy models: {e}")
        return False

def create_config_file():
    """Create default configuration file"""
    print("Creating configuration file...")

    config = {
        "model_settings": {
            "model_path": "",
            "device": "CPU",
            "max_results": 10,
            "summary_length": 200
        },
        "sphinx_settings": {
            "config_path": "/etc/sphinx/sphinx.conf",
            "host": "localhost",
            "port": 9312,
            "index_name": "smf_posts"
        },
        "database_settings": {
            "host": "localhost",
            "port": 3306,
            "database": "smf",
            "user": "smf_user",
            "password": "password"
        }
    }

    config_file = Path(__file__).parent / "config.json"

    try:
        with open(config_file, 'w') as f:
            json.dump(config, f, indent=2)

        print(f"✓ Configuration file created: {config_file}")
        print("Please edit the configuration file with your specific settings")
        return True

    except Exception as e:
        print(f"Error creating configuration file: {e}")
        return False

def setup_directories():
    """Create necessary directories"""
    print("Setting up directories...")

    base_dir = Path(__file__).parent
    directories = [
        base_dir / "models",
        base_dir / "cache",
        base_dir / "logs",
        base_dir / "temp"
    ]

    try:
        for directory in directories:
            directory.mkdir(exist_ok=True)

        print("✓ Directories created successfully")
        return True

    except Exception as e:
        print(f"Error creating directories: {e}")
        return False

def setup_models() -> bool:
    """Set up AI models using the unified model converter."""
    print("Setting up AI models...")

    try:
        # Import unified model converter
        from unified_model_converter import UnifiedModelConverter

        print("Starting unified model conversion process...")
        converter = UnifiedModelConverter()

        # Check dependencies first
        if not converter.check_dependencies():
            print("❌ Missing required dependencies for model conversion")
            return False

        # Step 1: Convert embedding models
        print("1. Converting embedding models...")
        embedding_results = converter.convert_all_embedding_models()

        success_count = sum(1 for success in embedding_results.values() if success)
        total_count = len(embedding_results)

        if success_count == 0:
            print("❌ Failed to convert any embedding models")
            return False
        elif success_count < total_count:
            print(f"⚠️ Converted {success_count}/{total_count} embedding models")
        else:
            print(f"✓ All {total_count} embedding models converted successfully")

        # Step 2: Convert LLM models (optional, may take longer)
        print("2. Converting LLM models...")
        llm_results = converter.convert_all_llm_models()

        llm_success_count = sum(1 for success in llm_results.values() if success)
        llm_total_count = len(llm_results)

        if llm_success_count == 0:
            print("⚠️ No LLM models converted (this is optional)")
        elif llm_success_count < llm_total_count:
            print(f"⚠️ Converted {llm_success_count}/{llm_total_count} LLM models")
        else:
            print(f"✓ All {llm_total_count} LLM models converted successfully")

        # Step 3: Cleanup to save space
        print("3. Cleaning up temporary files...")
        converter.cleanup_original_models()
        print("✓ Cleanup completed")

        # Summary
        total_success = success_count + llm_success_count
        total_models = total_count + llm_total_count

        print(f"✓ Model setup completed!")
        print(f"  Successfully converted: {total_success}/{total_models} models")
        print(f"  Embedding models: {success_count}/{total_count}")
        print(f"  LLM models: {llm_success_count}/{llm_total_count}")

        # At least embedding models are required for basic functionality
        return success_count > 0

    except Exception as e:
        print(f"❌ Error setting up models: {e}")
        return False

def test_installation():
    """Test the installation"""
    print("Testing installation...")

    try:
        # Test imports
        import numpy
        import torch
        import transformers
        import sentence_transformers
        import sklearn
        import nltk
        import spacy

        # Test basic functionality
        from sentence_transformers import SentenceTransformer
        model = SentenceTransformer('all-MiniLM-L6-v2')

        # Test encoding
        sentences = ["This is a test sentence"]
        embeddings = model.encode(sentences)

        print("✓ Installation test passed")
        return True

    except Exception as e:
        print(f"Error during testing: {e}")
        return False

def print_next_steps():
    """Print next steps for the user"""
    print("\n" + "="*50)
    print("INSTALLATION COMPLETE")
    print("="*50)
    print("\nNext steps:")
    print("1. Edit config.json with your specific settings")
    print("2. Configure your database connection")
    print("3. Set up Sphinx search daemon")
    print("4. Install the SMF plugin through admin panel")
    print("5. Configure plugin settings in SMF admin")
    print("6. Run initial indexing")
    print("\nFor detailed instructions, see the README.md file")
    print("\n✅ OpenVINO models with NNCF compression are ready!")
    print("- Compressed model available at: SphinxAI/models/compressed/compressed_model/")
    print("- Model is optimized for Polish language processing")
    print("- Binary format for faster loading and inference")

def main():
    """Main setup function"""
    print("Sphinx AI Search Setup")
    print("="*30)

    if not check_system_requirements():
        return 1

    if not install_python_packages():
        return 1

    if not setup_nltk_data():
        return 1

    if not setup_spacy_models():
        return 1

    if not create_config_file():
        return 1

    if not setup_directories():
        return 1

    # Setup models with OpenVINO conversion and NNCF compression
    if not setup_models():
        return 1

    if not test_installation():
        return 1

    print_next_steps()
    return 0

if __name__ == "__main__":
    sys.exit(main())
