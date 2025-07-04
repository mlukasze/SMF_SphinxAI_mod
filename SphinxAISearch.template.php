<?php
/**
 * Sphinx AI Search Template
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Main search template
 */
function template_sphinx_ai_search()
{
	global $context, $txt, $scripturl;
	
	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<span class="ie6_header floatleft">
				<img src="', $context['theme_url'], '/images/icons/search.png" alt="" class="icon" />
				', $txt['sphinx_ai_search'], '
			</span>
		</h3>
	</div>
	<div class="windowbg">
		<div class="content">
			<form action="', $scripturl, '?action=sphinxai" method="post" class="sphinx-search-form">
				<div class="search-container">
					<input type="text" name="search_query" id="search_query" value="', 
						isset($context['search_query']) ? htmlspecialchars($context['search_query'], ENT_QUOTES, 'UTF-8') : '', 
						'" placeholder="', $txt['sphinx_ai_search_placeholder'], '" class="search-input" />
					<button type="submit" class="search-button">
						<img src="', $context['theme_url'], '/images/icons/search.png" alt="Search" />
						', $txt['sphinx_ai_search_button'], '
					</button>
				</div>
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			</form>
		</div>
	</div>';
	
	// Display search results
	if (!empty($context['search_results'])) {
		echo '
		<div class="cat_bar">
			<h3 class="catbg">
				', $txt['sphinx_ai_search_results'], ' (', count($context['search_results']), ')
			</h3>
		</div>';
		
		foreach ($context['search_results'] as $result) {
			echo '
			<div class="windowbg search-result">
				<div class="content">
					<div class="search-result-header">
						<h4 class="search-result-title">
							<a href="', $scripturl, '?topic=', (int)$result['topic_id'], '.msg', (int)$result['post_id'], '#msg', (int)$result['post_id'], '">
								', htmlspecialchars($result['subject'], ENT_QUOTES, 'UTF-8'), '
							</a>
						</h4>
						<div class="search-result-meta">
							<span class="board-name">
								<img src="', $context['theme_url'], '/images/icons/folder.png" alt="" />
								', htmlspecialchars($result['board_name'], ENT_QUOTES, 'UTF-8'), '
							</span>
							<span class="post-stats">
								<img src="', $context['theme_url'], '/images/icons/replies.png" alt="" />
								', (int)$result['num_replies'], ' ', $txt['replies'], '
								<img src="', $context['theme_url'], '/images/icons/views.png" alt="" />
								', (int)$result['num_views'], ' ', $txt['views'], '
							</span>
						</div>
					</div>
					<div class="search-result-content">
						<div class="ai-summary">
							<h5>', $txt['sphinx_ai_summary'], '</h5>
							<p>', isset($result['summary']) ? htmlspecialchars($result['summary'], ENT_QUOTES, 'UTF-8') : htmlspecialchars(substr(strip_tags($result['content']), 0, 200), ENT_QUOTES, 'UTF-8') . '...', '</p>
						</div>
						<div class="source-links">
							<h5>', $txt['sphinx_ai_sources'], '</h5>
							<ul>';
			
			// Display source links
			if (!empty($result['source_links'])) {
				foreach ($result['source_links'] as $link) {
					echo '
								<li>
									<a href="', $scripturl, '?topic=', (int)$link['topic_id'], '.msg', (int)$link['post_id'], '#msg', (int)$link['post_id'], '">
										', htmlspecialchars($link['title'], ENT_QUOTES, 'UTF-8'), '
									</a>
									<span class="confidence-score">
										(', $txt['sphinx_ai_confidence'], ': ', (int)$link['confidence'], '%)
									</span>
								</li>';
				}
			} else {
				echo '
								<li>
									<a href="', $scripturl, '?topic=', (int)$result['topic_id'], '.msg', (int)$result['post_id'], '#msg', (int)$result['post_id'], '">
										', htmlspecialchars($result['subject'], ENT_QUOTES, 'UTF-8'), '
									</a>
								</li>';
			}
			
			echo '
							</ul>
						</div>
					</div>
				</div>
			</div>';
		}
	}
	
	// Add JavaScript for enhanced functionality
	echo '
	<script>
		$(document).ready(function() {
			// Auto-complete functionality
			$("#search_query").autocomplete({
				source: function(request, response) {
					$.ajax({
						url: "', $scripturl, '?action=sphinxai_api",
						type: "POST",
						data: {
							api_action: "suggestions",
							query: request.term
						},
						success: function(data) {
							response(data.suggestions || []);
						}
					});
				},
				minLength: 2
			});
			
			// Search as you type
			var searchTimeout;
			$("#search_query").on("input", function() {
				clearTimeout(searchTimeout);
				var query = $(this).val();
				if (query.length > 2) {
					searchTimeout = setTimeout(function() {
						performLiveSearch(query);
					}, 500);
				}
			});
		});
		
		function performLiveSearch(query) {
			$.ajax({
				url: "', $scripturl, '?action=sphinxai_api",
				type: "POST",
				data: {
					api_action: "search",
					query: query
				},
				success: function(data) {
					if (data.results && data.results.length > 0) {
						// Show live results dropdown
						showLiveResults(data.results);
					}
				}
			});
		}
		
		function showLiveResults(results) {
			// Implementation for live search results
			var dropdown = $("<div class=\"live-search-results\"></div>");
			results.slice(0, 5).forEach(function(result) {
				var item = $("<div class=\"live-search-item\"></div>");
				item.html("<strong>" + result.subject + "</strong><br/>" + 
					result.summary.substring(0, 100) + "...");
				item.on("click", function() {
					window.location.href = "', $scripturl, '?topic=" + result.topic_id + 
						".msg" + result.post_id + "#msg" + result.post_id;
				});
				dropdown.append(item);
			});
			
			$(".search-container").append(dropdown);
		}
	</script>';
}

/**
 * Admin template
 */
function template_sphinx_ai_admin()
{
	global $context, $txt, $scripturl;
	
	// Sanitize input parameters
	$current_action = filter_input(INPUT_GET, 'sa', FILTER_SANITIZE_STRING);
	$current_action = !empty($current_action) ? trim($current_action) : 'settings';
	
	// Validate against allowed actions
	$allowed_actions = array('settings', 'index', 'stats');
	if (!in_array($current_action, $allowed_actions)) {
		$current_action = 'settings';
	}
	
	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<span class="ie6_header floatleft">
				<img src="', $context['theme_url'], '/images/icons/settings.png" alt="" class="icon" />
				', $txt['sphinx_ai_search_admin'], '
			</span>
		</h3>
	</div>';
	
	// Admin tabs
	echo '
	<div class="windowbg">
		<div class="content">
			<div class="admin-tabs">
				<ul>
					<li><a href="', $scripturl, '?action=sphinxai_admin;sa=settings" class="', 
						($current_action == 'settings') ? 'active' : '', '">
						', $txt['sphinx_ai_search_settings'], '</a></li>
					<li><a href="', $scripturl, '?action=sphinxai_admin;sa=index" class="', 
						($current_action == 'index') ? 'active' : '', '">
						', $txt['sphinx_ai_search_index'], '</a></li>
					<li><a href="', $scripturl, '?action=sphinxai_admin;sa=stats" class="', 
						($current_action == 'stats') ? 'active' : '', '">
						', $txt['sphinx_ai_search_stats'], '</a></li>
				</ul>
			</div>
		</div>
	</div>';
	
	// Settings tab
	if ($current_action == 'settings') {
		echo '
		<div class="windowbg">
			<div class="content">
				<h4>', $txt['sphinx_ai_search_settings'], '</h4>';
				
		if (!empty($context['settings_saved'])) {
			echo '
				<div class="infobox">
					', $txt['sphinx_ai_settings_saved'], '
				</div>';
		}
		
		echo '
				<form action="', $scripturl, '?action=sphinxai_admin;sa=settings" method="post">
					<dl class="settings">
						<dt>
							<label for="sphinx_ai_model_path">', $txt['sphinx_ai_model_path'], '</label>
						</dt>
						<dd>
							<input type="text" name="sphinx_ai_model_path" id="sphinx_ai_model_path" 
								value="', $context['current_settings']['sphinx_ai_model_path'], '" size="50" />
							<div class="smalltext">', $txt['sphinx_ai_model_path_desc'], '</div>
						</dd>
						
						<dt>
							<label for="sphinx_ai_max_results">', $txt['sphinx_ai_max_results'], '</label>
						</dt>
						<dd>
							<input type="number" name="sphinx_ai_max_results" id="sphinx_ai_max_results" 
								value="', $context['current_settings']['sphinx_ai_max_results'], '" min="1" max="100" />
							<div class="smalltext">', $txt['sphinx_ai_max_results_desc'], '</div>
						</dd>
						
						<dt>
							<label for="sphinx_ai_summary_length">', $txt['sphinx_ai_summary_length'], '</label>
						</dt>
						<dd>
							<input type="number" name="sphinx_ai_summary_length" id="sphinx_ai_summary_length" 
								value="', $context['current_settings']['sphinx_ai_summary_length'], '" min="50" max="500" />
							<div class="smalltext">', $txt['sphinx_ai_summary_length_desc'], '</div>
						</dd>
						
						<dt>
							<label for="sphinx_ai_auto_index">', $txt['sphinx_ai_auto_index'], '</label>
						</dt>
						<dd>
							<input type="checkbox" name="sphinx_ai_auto_index" id="sphinx_ai_auto_index" 
								', $context['current_settings']['sphinx_ai_auto_index'] ? 'checked' : '', ' />
							<div class="smalltext">', $txt['sphinx_ai_auto_index_desc'], '</div>
						</dd>
					</dl>
					
					<div class="righttext">
						<input type="submit" name="save_settings" value="', $txt['save'], '" class="button_submit" />
					</div>
				</form>
			</div>
		</div>';
	}
	
	// Index tab
	if ($current_action == 'index') {
		echo '
		<div class="windowbg">
			<div class="content">
				<h4>', $txt['sphinx_ai_search_index'], '</h4>';
				
		if (!empty($context['indexing_started'])) {
			echo '
				<div class="infobox">
					', $txt['sphinx_ai_indexing_started'], '
				</div>';
		}
		
		echo '
				<dl class="settings">
					<dt>', $txt['sphinx_ai_total_indexed'], '</dt>
					<dd>', $context['index_stats']['total_indexed'], '</dd>
					
					<dt>', $txt['sphinx_ai_last_indexed'], '</dt>
					<dd>', $context['index_stats']['last_indexed'], '</dd>
				</dl>
				
				<form action="', $scripturl, '?action=sphinxai_admin;sa=index" method="post">
					<div class="righttext">
						<input type="submit" name="start_indexing" value="', $txt['sphinx_ai_start_indexing'], '" 
							class="button_submit" onclick="return confirm(\'', $txt['sphinx_ai_confirm_reindex'], '\')" />
					</div>
				</form>
			</div>
		</div>';
	}
	
	// Stats tab
	if ($current_action == 'stats') {
		echo '
		<div class="windowbg">
			<div class="content">
				<h4>', $txt['sphinx_ai_search_stats'], '</h4>
				
				<dl class="settings">
					<dt>', $txt['sphinx_ai_total_searches'], '</dt>
					<dd>', $context['search_stats']['total_searches'], ' (', $txt['sphinx_ai_last_30_days'], ')</dd>
					
					<dt>', $txt['sphinx_ai_avg_results'], '</dt>
					<dd>', round($context['search_stats']['avg_results'], 2), '</dd>
				</dl>
				
				<h5>', $txt['sphinx_ai_popular_queries'], '</h5>
				<table class="table_grid">
					<thead>
						<tr class="catbg">
							<th>', $txt['sphinx_ai_query'], '</th>
							<th>', $txt['sphinx_ai_search_count'], '</th>
						</tr>
					</thead>
					<tbody>';
		
		if (!empty($context['popular_queries'])) {
			foreach ($context['popular_queries'] as $query) {
				echo '
						<tr class="windowbg">
							<td>', $query['query_text'], '</td>
							<td>', $query['search_count'], '</td>
						</tr>';
			}
		} else {
			echo '
						<tr class="windowbg">
							<td colspan="2" class="centertext">', $txt['sphinx_ai_no_data'], '</td>
						</tr>';
		}
		
		echo '
					</tbody>
				</table>
			</div>
		</div>';
	}
}
