@echo off
REM Sphinx AI Search Plugin Installation Script for Windows
REM This script sets up the Python environment and dependencies

echo ========================================
echo Sphinx AI Search Plugin Setup
echo ========================================

REM Check if Python is installed
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.8 or higher from https://python.org
    pause
    exit /b 1
)

REM Check Python version
for /f "tokens=2" %%i in ('python --version') do set PYTHON_VERSION=%%i
echo Found Python version: %PYTHON_VERSION%

REM Check if pip is available
pip --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: pip is not installed
    echo Please install pip or reinstall Python with pip
    pause
    exit /b 1
)

REM Navigate to SphinxAI directory
cd /d "%~dp0SphinxAI"

echo.
echo ========================================
echo Hugging Face Token Setup (Optional)
echo ========================================
echo Some AI models may require a Hugging Face token for access.
echo If you have a Hugging Face account, you can provide your token now.
echo You can also skip this and add it later if needed.
echo.
echo To get a token:
echo 1. Go to https://huggingface.co/settings/tokens
echo 2. Create a new token (read access is sufficient)
echo 3. Copy the token and paste it below
echo.
set /p HF_TOKEN="Enter your Hugging Face token (or press Enter to skip): "

if not "%HF_TOKEN%"=="" (
    echo Setting up Hugging Face token...
    set HUGGING_FACE_HUB_TOKEN=%HF_TOKEN%
    
    REM Save token to config file for persistent use
    echo [huggingface]> config.ini
    echo token = %HF_TOKEN%>> config.ini
    echo ✓ Hugging Face token configured
) else (
    echo Skipping Hugging Face token setup
    echo Note: You can add it later by creating config.ini with:
    echo [huggingface]
    echo token = YOUR_TOKEN_HERE
)

echo.
echo Installing Python dependencies...
echo This may take several minutes...

REM Upgrade pip
python -m pip install --upgrade pip

REM Install requirements
pip install -r requirements.txt
if %errorlevel% neq 0 (
    echo ERROR: Failed to install Python dependencies
    echo Please check your internet connection and try again
    pause
    exit /b 1
)

echo.
echo Setting up NLTK data...
python -c "import nltk; nltk.download('punkt', quiet=True); nltk.download('stopwords', quiet=True); nltk.download('wordnet', quiet=True)"

echo.
echo Setting up spaCy models...
python -m spacy download en_core_web_sm

echo.
echo Creating directories...
if not exist "SphinxAI\models" mkdir "SphinxAI\models"
if not exist "cache" mkdir cache
if not exist "logs" mkdir logs
if not exist "temp" mkdir temp

echo.
echo Testing installation...
python -c "import numpy, torch, transformers, sentence_transformers, sklearn, nltk, spacy; print('All dependencies imported successfully')"
if %errorlevel% neq 0 (
    echo ERROR: Installation test failed
    echo Some dependencies may not be installed correctly
    pause
    exit /b 1
)

echo.
echo ========================================
echo Downloading and converting AI models...
echo This process may take 15-45 minutes depending on internet speed
echo ========================================

echo.
echo Downloading embedding model for multilingual search...
python unified_model_converter.py --embedding-model multilingual_mpnet
if %errorlevel% neq 0 (
    echo WARNING: Embedding model conversion failed
    echo AI similarity features may not work properly
    echo You can retry later with: python unified_model_converter.py --embedding-model multilingual_mpnet
)

echo.
echo Downloading chat model for AI summarization...
python unified_model_converter.py --llm-model chat
if %errorlevel% neq 0 (
    echo WARNING: LLM model conversion failed
    echo AI summarization features may not work properly
    echo You can retry later with: python unified_model_converter.py --llm-model chat
)

echo.
echo Checking model availability...
python -c "
import os
models_dir = 'SphinxAI/models'
embedding_dir = os.path.join(models_dir, 'embeddings')
llm_dir = os.path.join(models_dir, 'llm')

if os.path.exists(embedding_dir) and os.listdir(embedding_dir):
    print('✓ Embedding models available')
else:
    print('⚠ Embedding models not found')

if os.path.exists(llm_dir) and os.listdir(llm_dir):
    print('✓ LLM models available')
else:
    print('⚠ LLM models not found')
"

echo.
echo ========================================
echo Installation completed successfully!
echo ========================================
echo.
echo Next steps:
echo 1. Install the SMF plugin through your admin panel
echo 2. Configure the plugin settings
echo 3. Set up Sphinx search daemon
echo 4. Run initial indexing
echo.
echo For detailed instructions, see README.md
echo.
pause
