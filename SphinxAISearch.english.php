<?php
/**
 * Sphinx AI Search Language File (English)
 */

$txt['sphinx_ai_search'] = 'AI Search';
$txt['sphinx_ai_search_admin'] = 'AI Search Admin';
$txt['sphinx_ai_search_button'] = 'Search';
$txt['sphinx_ai_search_placeholder'] = 'Ask me anything about the forum...';
$txt['sphinx_ai_search_results'] = 'Search Results';
$txt['sphinx_ai_summary'] = 'AI Summary';
$txt['sphinx_ai_sources'] = 'Sources';
$txt['sphinx_ai_confidence'] = 'Confidence';

// Admin strings
$txt['sphinx_ai_search_settings'] = 'Settings';
$txt['sphinx_ai_search_index'] = 'Indexing';
$txt['sphinx_ai_search_stats'] = 'Statistics';

// Settings
$txt['sphinx_ai_model_path'] = 'Model Path';
$txt['sphinx_ai_model_path_desc'] = 'Path to the OpenVINO model file (e.g., /path/to/model.xml)';
$txt['sphinx_ai_max_results'] = 'Maximum Results';
$txt['sphinx_ai_max_results_desc'] = 'Maximum number of search results to return (1-100)';
$txt['sphinx_ai_summary_length'] = 'Summary Length';
$txt['sphinx_ai_summary_length_desc'] = 'Maximum length of AI-generated summaries in characters (50-500)';
$txt['sphinx_ai_auto_index'] = 'Auto Indexing';
$txt['sphinx_ai_auto_index_desc'] = 'Automatically index new posts as they are created';
$txt['sphinx_ai_settings_saved'] = 'Settings saved successfully!';

// Indexing
$txt['sphinx_ai_total_indexed'] = 'Total Indexed Posts';
$txt['sphinx_ai_last_indexed'] = 'Last Indexed';
$txt['sphinx_ai_start_indexing'] = 'Start Indexing';
$txt['sphinx_ai_indexing_started'] = 'Indexing process started in background';
$txt['sphinx_ai_confirm_reindex'] = 'This will reindex all forum content. Continue?';

// Statistics
$txt['sphinx_ai_total_searches'] = 'Total Searches';
$txt['sphinx_ai_last_30_days'] = 'Last 30 Days';
$txt['sphinx_ai_avg_results'] = 'Average Results per Search';
$txt['sphinx_ai_popular_queries'] = 'Popular Search Queries';
$txt['sphinx_ai_query'] = 'Query';
$txt['sphinx_ai_search_count'] = 'Search Count';
$txt['sphinx_ai_no_data'] = 'No data available';

// General
$txt['replies'] = 'Replies';
$txt['views'] = 'Views';
$txt['save'] = 'Save';

// Permissions
$txt['permissionname_sphinx_ai_search'] = 'Use AI Search';
$txt['permissionhelp_sphinx_ai_search'] = 'Allow members to use the AI-powered search feature';
$txt['cannot_sphinx_ai_search'] = 'You are not allowed to use the AI search feature';

// Errors
$txt['sphinx_ai_error_no_query'] = 'Please enter a search query';
$txt['sphinx_ai_error_short_query'] = 'Search query must be at least 2 characters long';
$txt['sphinx_ai_error_model_not_found'] = 'AI model not found. Please check the model path in settings.';
$txt['sphinx_ai_error_python_not_found'] = 'Python interpreter not found. Please ensure Python is installed.';
$txt['sphinx_ai_error_dependencies'] = 'Required Python dependencies not installed. Please check the setup guide.';
$txt['sphinx_ai_error_index_empty'] = 'Search index is empty. Please run indexing first.';
$txt['sphinx_ai_error_timeout'] = 'Search request timed out. Please try again.';
$txt['sphinx_ai_error_query_too_long'] = 'Search query is too long. Please limit to 1000 characters.';

// Success messages
$txt['sphinx_ai_success_indexed'] = 'Successfully indexed {count} posts';
$txt['sphinx_ai_success_settings'] = 'Settings saved successfully';

// Info messages
$txt['sphinx_ai_info_indexing'] = 'Indexing in progress... This may take several minutes.';
$txt['sphinx_ai_info_first_time'] = 'First time setup: Please configure the AI model path and run initial indexing.';
$txt['sphinx_ai_info_requirements'] = 'This feature requires Python 3.8+, OpenVINO, and Hugging Face Transformers.';
