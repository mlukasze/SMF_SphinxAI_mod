#!/usr/bin/env python3
"""
Test runner script for SphinxAI Python tests
"""

import os
import sys
import subprocess
from pathlib import Path

def main():
    """Run the test suite"""
    # Get the test directory
    test_dir = Path(__file__).parent
    project_root = test_dir.parent

    print("SphinxAI Test Runner")
    print("=" * 50)
    print(f"Test directory: {test_dir}")
    print(f"Project root: {project_root}")
    print()

    # Add project root to Python path
    sys.path.insert(0, str(project_root))

    try:
        # Try to import pytest
        import pytest
        print("✓ pytest is available")
    except ImportError:
        print("✗ pytest not found. Installing test dependencies...")

        # Install test dependencies
        requirements_file = test_dir / "requirements_test.txt"
        if requirements_file.exists():
            try:
                subprocess.check_call([
                    sys.executable, "-m", "pip", "install", "-r", str(requirements_file)
                ])
                print("✓ Test dependencies installed")
                import pytest
            except subprocess.CalledProcessError:
                print("✗ Failed to install test dependencies")
                print("Please install manually:")
                print(f"pip install -r {requirements_file}")
                return 1
        else:
            print("✗ requirements_test.txt not found")
            return 1

    # Change to test directory
    os.chdir(test_dir)

    # Run basic import tests first
    print("\nRunning import tests...")
    try:
        # Test basic imports
        sys.path.insert(0, str(project_root / "SphinxAI"))

        from utils.cache import SphinxAICache
        print("✓ Cache module imported successfully")

        from utils.config_manager import ConfigManager
        print("✓ Config manager imported successfully")

    except ImportError as e:
        print(f"✗ Import test failed: {e}")
        print("Running basic tests anyway...")

    # Run pytest with basic configuration
    print("\nRunning pytest...")

    # Basic pytest arguments
    pytest_args = [
        "python",  # Test directory
        "-v",      # Verbose
        "--tb=short",  # Short traceback
        "--maxfail=5", # Stop after 5 failures
        "-x",      # Stop on first failure
        "--disable-warnings",  # Disable warnings for cleaner output
    ]

    # Add coverage if available
    try:
        import coverage
        pytest_args.extend([
            "--cov=../SphinxAI/utils",
            "--cov-report=term-missing",
            "--cov-report=html:../coverage"
        ])
        print("✓ Coverage reporting enabled")
    except ImportError:
        print("! Coverage not available (install pytest-cov for coverage)")

    try:
        # Run tests
        exit_code = pytest.main(pytest_args)

        if exit_code == 0:
            print("\n✓ All tests passed!")
        else:
            print(f"\n✗ Tests failed with exit code {exit_code}")

        return exit_code

    except Exception as e:
        print(f"✗ Error running tests: {e}")
        return 1

if __name__ == "__main__":
    sys.exit(main())
