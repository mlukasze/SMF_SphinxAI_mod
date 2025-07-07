"""
Basic smoke test to verify Python environment and imports
"""

import os
import sys

import pytest


def test_python_version():
    """Test that Python version is adequate"""
    assert sys.version_info >= (3, 10), f"Python 3.10+ required, got {sys.version}"


def test_basic_imports():
    """Test that basic Python packages are available"""
    try:
        import hashlib  # noqa: F401
        import json  # noqa: F401
        import time  # noqa: F401
        import unittest  # noqa: F401

        assert True
    except ImportError as e:
        pytest.fail(f"Basic import failed: {e}")


def test_required_packages():
    """Test that required packages are installed"""
    try:
        # Test basic packages that should be quick to install
        import requests  # noqa: F401
        import yaml  # noqa: F401

        assert True
    except ImportError as e:
        pytest.fail(f"Required package import failed: {e}")


def test_optional_packages():
    """Test that optional packages can be imported if available"""
    optional_packages = {
        "redis": "Redis client for caching",
        "numpy": "NumPy for array operations",
        "sentence_transformers": "Sentence transformers for AI",
        "openvino": "OpenVINO for inference optimization",
    }

    for package, description in optional_packages.items():
        try:
            __import__(package)
            print(f"✅ {package} available - {description}")
        except ImportError:
            print(
                f"ℹ️ {package} not installed - {description} (optional for smoke test)"
            )


def test_project_structure():
    """Test that project structure is accessible"""
    project_root = os.path.join(os.path.dirname(__file__), "..", "..")
    sphinxai_path = os.path.join(project_root, "SphinxAI")

    assert os.path.exists(
        sphinxai_path
    ), f"SphinxAI directory not found at {sphinxai_path}"
    assert os.path.exists(
        os.path.join(sphinxai_path, "__init__.py")
    ), "SphinxAI/__init__.py not found"


if __name__ == "__main__":
    test_python_version()
    test_basic_imports()
    test_required_packages()
    test_project_structure()
    print("All smoke tests passed!")
