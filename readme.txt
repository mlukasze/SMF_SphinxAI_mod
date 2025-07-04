[b]Sphinx AI Search Plugin for SMF[/b]

[color=red][size=14pt][b]⚠️ IMPORTANT: Additional Setup Required![/b][/size][/color]

This SMF package installs the forum integration, but you must complete the Python environment setup to enable AI features.

[hr]

[b][size=12pt]🚀 Quick Setup (Recommended)[/size][/b]

After installing this SMF package:

[b]Windows Users:[/b]
1. Open Command Prompt as Administrator
2. Navigate to your SMF directory: [tt]cd C:\path\to\your\smf\forum[/tt]
3. Run: [tt]install.bat[/tt]

[b]Linux/Unix Users:[/b]
1. Open Terminal
2. Navigate to your SMF directory: [tt]cd /path/to/your/smf/forum[/tt]
3. Run: [tt]chmod +x install.sh && ./install.sh[/tt]

[b]The automated scripts will:[/b]
✅ Install all Python dependencies
✅ Download and convert AI models (multilingual support)
✅ Set up Hugging Face authentication (optional)
✅ Verify installation and model availability
✅ Provide detailed error messages and guidance

[hr]

[b][size=12pt]🤖 What This Plugin Provides[/size][/b]

• [b]AI-Powered Search:[/b] Semantic search using multilingual embeddings
• [b]Intelligent Summarization:[/b] AI-generated content summaries
• [b]Source Linking:[/b] Direct links to original forum posts
• [b]Multilingual Support:[/b] Optimized for Polish and other languages
• [b]Sphinx Integration:[/b] Fast, scalable search indexing
• [b]OpenVINO Optimization:[/b] Efficient AI model inference

[hr]

[b][size=12pt]🔑 Hugging Face Token (Optional)[/size][/b]

Some advanced AI models require authentication:

1. Create account at [url=https://huggingface.co]https://huggingface.co[/url]
2. Go to [url=https://huggingface.co/settings/tokens]Settings → Access Tokens[/url]
3. Create new token (read access sufficient)
4. Enter token when prompted during installation
- User-friendly interface with modern design

[b]Requirements:[/b]
- SMF 2.1.*
- PHP 8.1 or higher (uses modern PHP features: enums, union types, constructor property promotion)
- Python 3.8+ (for OpenVINO and language model)
- Sphinx search daemon
- OpenVINO toolkit
- Access to install Python packages

[b]Installation:[/b]
1. Upload the plugin files to your SMF forum
2. Install the plugin through the SMF admin panel
3. Configure the plugin settings in Admin > Modifications > Sphinx AI Search
4. Set up Python environment and dependencies (see setup instructions)
5. Configure Sphinx search daemon
6. Run initial indexing

[b]Configuration:[/b]
After installation, go to Admin > Modifications > Sphinx AI Search to configure:
- OpenVINO model path
- Sphinx configuration settings
- AI model parameters
- Search result formatting options

[b]Python Dependencies Setup:[/b]
The plugin requires several Python packages. Run the following commands:
```
pip install torch transformers openvino sphinx
```

[b]Support:[/b]
For support and updates, visit the plugin homepage.

[b]License:[/b]
This plugin is released under the MIT License.
