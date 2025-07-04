<?php
/**
 * Sphinx AI Search - Installation Check Script
 * This script runs after SMF package installation to check Python setup
 */

// Ensure this is only run during package installation
if (!defined('SMF') && !defined('SMF_INSTALLING')) {
    die('Restricted access');
}

// Check if we're in the right context
if (!function_exists('log_error')) {
    return;
}

echo '<div style="border: 2px solid #ff6600; padding: 15px; margin: 10px; background: #fff3e0; border-radius: 5px;">';
echo '<h3 style="color: #e65100; margin-top: 0;">🤖 Sphinx AI Search - Post-Installation Setup Required</h3>';
echo '<p><strong>The SMF plugin has been installed successfully!</strong> However, you need to complete the Python environment setup to enable AI features.</p>';

// Check if Python is available
$python_available = false;
$python_version = '';

// Try to detect Python
$python_commands = ['python3', 'python'];
foreach ($python_commands as $cmd) {
    $output = [];
    $return_code = 1;
    @exec("$cmd --version 2>&1", $output, $return_code);
    
    if ($return_code === 0 && !empty($output[0])) {
        $python_available = true;
        $python_version = $output[0];
        break;
    }
}

if ($python_available) {
    echo '<p style="color: #2e7d32;">✅ Python detected: ' . htmlspecialchars($python_version) . '</p>';
} else {
    echo '<p style="color: #d32f2f;">❌ Python not detected in system PATH</p>';
}

// Check if SphinxAI directory exists
$sphinx_dir = dirname(__FILE__) . '/SphinxAI';
$install_script_windows = $sphinx_dir . '/../install.bat';
$install_script_linux = $sphinx_dir . '/../install.sh';

echo '<h4>Next Steps:</h4>';
echo '<ol>';
echo '<li><strong>Navigate to your SMF root directory</strong> (where this forum is installed)</li>';

if (file_exists($install_script_windows) || file_exists($install_script_linux)) {
    echo '<li><strong>Run the automated installation script:</strong><br>';
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        echo '<code style="background: #f5f5f5; padding: 5px; border-radius: 3px;">install.bat</code> (Windows)';
    } else {
        echo '<code style="background: #f5f5f5; padding: 5px; border-radius: 3px;">chmod +x install.sh && ./install.sh</code> (Linux/Unix)';
    }
    
    echo '<br><em>This will install Python dependencies, download AI models, and set up Hugging Face authentication if needed.</em></li>';
} else {
    echo '<li><strong>Install Python dependencies manually:</strong><br>';
    echo '<code style="background: #f5f5f5; padding: 5px; border-radius: 3px;">cd SphinxAI && pip install -r requirements.txt</code></li>';
    echo '<li><strong>Download AI models:</strong><br>';
    echo '<code style="background: #f5f5f5; padding: 5px; border-radius: 3px;">python unified_model_converter.py --embedding-model multilingual_mpnet</code><br>';
    echo '<code style="background: #f5f5f5; padding: 5px; border-radius: 3px;">python unified_model_converter.py --llm-model chat</code></li>';
}

echo '<li><strong>Configure Sphinx Search daemon</strong> (see README.md for details)</li>';
echo '<li><strong>Go to Admin Panel → Modifications → Sphinx AI Search</strong> to configure the plugin</li>';
echo '</ol>';

echo '<h4>Documentation:</h4>';
echo '<ul>';
echo '<li>📖 <strong>README.md</strong> - Complete installation and configuration guide</li>';
echo '<li>🔧 <strong>HUGGINGFACE_TOKEN_IMPLEMENTATION.md</strong> - Hugging Face token setup guide</li>';
echo '<li>📊 <strong>INSTALLATION_SCRIPTS_ENHANCEMENT.md</strong> - Installation script features</li>';
echo '</ul>';

echo '<p style="background: #e3f2fd; padding: 10px; border-left: 4px solid #2196f3; margin: 15px 0;">';
echo '<strong>💡 Pro Tip:</strong> The installation scripts include automatic Hugging Face token setup, ';
echo 'model download with progress indicators, and comprehensive error handling. ';
echo 'They handle all the complex setup automatically!';
echo '</p>';

echo '<p style="color: #666; font-size: 0.9em; margin-bottom: 0;">';
echo '<strong>Need Help?</strong> Check the documentation files in your SMF root directory, ';
echo 'or visit the SMF community forums for support.';
echo '</p>';

echo '</div>';

// Log the installation for debugging
if (function_exists('log_error')) {
    log_error('Sphinx AI Search: SMF package installed, Python setup required', 'general');
}
?>
