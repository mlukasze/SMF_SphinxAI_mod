<?php
/**
 * Sphinx AI Search Plugin for SMF - Result Formatter
 * 
 * @package SphinxAISearch
 * @version 2.0.0
 * @author SMF Plugin Team
 * @license MIT
 */

declare(strict_types=1);

if (!defined('SMF'))
    die('Hacking attempt...');

/**
 * Formats search results for display in SMF templates
 * 
 * Following Single Responsibility Principle - this class only handles
 * formatting of search results for display.
 */
class SphinxAIResultFormatter
{
    private array $smcFunc;
    private array $modSettings;
    private array $txt;
    private string $scriptUrl;

    /**
     * Constructor
     * 
     * @param array $smcFunc SMF database functions
     * @param array $modSettings Forum settings
     * @param array $txt Language strings
     * @param string $scriptUrl Base script URL
     */
    public function __construct(
        array $smcFunc,
        array $modSettings,
        array $txt,
        string $scriptUrl
    ) {
        $this->smcFunc = $smcFunc;
        $this->modSettings = $modSettings;
        $this->txt = $txt;
        $this->scriptUrl = $scriptUrl;
    }

    /**
     * Format search results for template display
     * 
     * @param array $searchResponse Response from Python backend
     * @return array Formatted results for template
     */
    public function formatResults(array $searchResponse): array
    {
        if (!$searchResponse['success']) {
            return $this->formatError($searchResponse);
        }

        $data = $searchResponse['data'] ?? [];
        $results = $data['results'] ?? [];

        $formattedResults = [
            'success' => true,
            'query' => $data['query'] ?? '',
            'total_results' => $data['total_results'] ?? 0,
            'search_type' => $data['search_type'] ?? 'hybrid',
            'forum_summary' => $this->formatForumSummary($data['forum_summary'] ?? ''),
            'results' => [],
            'ai_features' => $data['ai_features_used'] ?? [],
            'pagination' => $this->createPagination($data),
            'search_time' => $this->calculateSearchTime(),
            'suggestions' => $this->generateSearchSuggestions($data['query'] ?? '')
        ];

        // Format individual results
        foreach ($results as $result) {
            $formattedResults['results'][] = $this->formatSingleResult($result);
        }

        return $formattedResults;
    }

    /**
     * Format error response
     * 
     * @param array $errorResponse Error response from backend
     * @return array Formatted error
     */
    private function formatError(array $errorResponse): array
    {
        return [
            'success' => false,
            'error_message' => $errorResponse['error'] ?? $this->txt['sphinxai_unknown_error'],
            'error_details' => $errorResponse['errors'] ?? [],
            'query' => '',
            'total_results' => 0,
            'results' => [],
            'show_fallback_search' => true
        ];
    }

    /**
     * Format a single search result
     * 
     * @param array $result Raw result data
     * @return array Formatted result
     */
    private function formatSingleResult(array $result): array
    {
        $postUrl = $this->generatePostUrl($result);
        $topicUrl = $this->generateTopicUrl($result);

        return [
            'id' => $result['id'] ?? '',
            'title' => $this->sanitizeText($result['title'] ?? $this->txt['sphinxai_no_title']),
            'content' => $this->formatContent($result['content'] ?? ''),
            'ai_summary' => $this->formatAISummary($result['ai_summary'] ?? ''),
            'url' => $postUrl,
            'topic_url' => $topicUrl,
            'relevance_score' => $this->formatRelevanceScore($result['relevance_score'] ?? 0),
            'metadata' => $this->formatMetadata($result['metadata'] ?? []),
            'board_info' => $this->formatBoardInfo($result['metadata'] ?? []),
            'post_stats' => $this->formatPostStats($result['metadata'] ?? []),
            'highlight_terms' => $this->extractHighlightTerms($result),
            'snippet' => $this->generateSnippet($result)
        ];
    }

    /**
     * Format forum summary text
     * 
     * @param string $summary Raw summary text
     * @return string Formatted summary
     */
    private function formatForumSummary(string $summary): string
    {
        if (empty($summary)) {
            return $this->txt['sphinxai_no_summary'];
        }

        // Clean and format the summary
        $summary = $this->sanitizeText($summary);
        $summary = $this->addSummaryFormatting($summary);

        return $summary;
    }

    /**
     * Format content with highlighting and truncation
     * 
     * @param string $content Raw content
     * @return string Formatted content
     */
    private function formatContent(string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // Remove BBCode and HTML
        $content = $this->stripForumCode($content);
        
        // Truncate if too long
        $maxLength = (int)($this->modSettings['sphinxai_content_length'] ?? 300);
        if (strlen($content) > $maxLength) {
            $content = substr($content, 0, $maxLength) . '...';
        }

        return $this->sanitizeText($content);
    }

    /**
     * Format AI summary with proper styling
     * 
     * @param string $summary Raw AI summary
     * @return string Formatted summary with HTML
     */
    private function formatAISummary(string $summary): string
    {
        if (empty($summary)) {
            return '';
        }

        $summary = $this->sanitizeText($summary);
        
        // Add AI summary styling
        return sprintf(
            '<div class="ai-summary"><h5>%s</h5><p>%s</p></div>',
            $this->txt['sphinxai_ai_summary'],
            $summary
        );
    }

    /**
     * Generate post URL
     * 
     * @param array $result Result data
     * @return string Post URL
     */
    private function generatePostUrl(array $result): string
    {
        $metadata = $result['metadata'] ?? [];
        $topicId = $metadata['topic_id'] ?? 0;
        $postId = $metadata['post_id'] ?? 0;

        if ($topicId && $postId) {
            return sprintf(
                '%s?topic=%d.msg%d#msg%d',
                $this->scriptUrl,
                $topicId,
                $postId,
                $postId
            );
        } elseif ($topicId) {
            return sprintf('%s?topic=%d', $this->scriptUrl, $topicId);
        }

        return '';
    }

    /**
     * Generate topic URL
     * 
     * @param array $result Result data
     * @return string Topic URL
     */
    private function generateTopicUrl(array $result): string
    {
        $metadata = $result['metadata'] ?? [];
        $topicId = $metadata['topic_id'] ?? 0;

        return $topicId ? sprintf('%s?topic=%d', $this->scriptUrl, $topicId) : '';
    }

    /**
     * Format relevance score for display
     * 
     * @param float $score Raw relevance score
     * @return array Formatted score info
     */
    private function formatRelevanceScore(float $score): array
    {
        $percentage = round($score * 100, 1);
        $level = 'low';

        if ($percentage >= 80) {
            $level = 'high';
        } elseif ($percentage >= 50) {
            $level = 'medium';
        }

        return [
            'raw' => $score,
            'percentage' => $percentage,
            'level' => $level,
            'display' => sprintf('%s%%', $percentage)
        ];
    }

    /**
     * Format metadata information
     * 
     * @param array $metadata Raw metadata
     * @return array Formatted metadata
     */
    private function formatMetadata(array $metadata): array
    {
        return [
            'board_id' => $metadata['board_id'] ?? 0,
            'board_name' => $this->sanitizeText($metadata['board_name'] ?? $this->txt['sphinxai_unknown_board']),
            'topic_id' => $metadata['topic_id'] ?? 0,
            'post_id' => $metadata['post_id'] ?? 0,
            'num_replies' => (int)($metadata['num_replies'] ?? 0),
            'num_views' => (int)($metadata['num_views'] ?? 0),
            'sphinx_weight' => $metadata['sphinx_weight'] ?? 0
        ];
    }

    /**
     * Format board information
     * 
     * @param array $metadata Result metadata
     * @return array Board info
     */
    private function formatBoardInfo(array $metadata): array
    {
        return [
            'id' => $metadata['board_id'] ?? 0,
            'name' => $this->sanitizeText($metadata['board_name'] ?? $this->txt['sphinxai_unknown_board']),
            'url' => $this->generateBoardUrl($metadata['board_id'] ?? 0)
        ];
    }

    /**
     * Format post statistics
     * 
     * @param array $metadata Result metadata
     * @return array Post stats
     */
    private function formatPostStats(array $metadata): array
    {
        $replies = (int)($metadata['num_replies'] ?? 0);
        $views = (int)($metadata['num_views'] ?? 0);

        return [
            'replies' => $replies,
            'views' => $views,
            'replies_text' => sprintf($this->txt['sphinxai_replies_count'], $replies),
            'views_text' => sprintf($this->txt['sphinxai_views_count'], $views)
        ];
    }

    /**
     * Generate board URL
     * 
     * @param int $boardId Board ID
     * @return string Board URL
     */
    private function generateBoardUrl(int $boardId): string
    {
        return $boardId ? sprintf('%s?board=%d', $this->scriptUrl, $boardId) : '';
    }

    /**
     * Sanitize text for safe HTML output
     * 
     * @param string $text Raw text
     * @return string Sanitized text
     */
    private function sanitizeText(string $text): string
    {
        // Use SMF's built-in sanitization if available
        if (isset($this->smcFunc['htmlspecialchars'])) {
            return $this->smcFunc['htmlspecialchars']($text, ENT_QUOTES);
        }
        
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Strip forum-specific formatting codes
     * 
     * @param string $content Content with BBCode/HTML
     * @return string Clean text
     */
    private function stripForumCode(string $content): string
    {
        // Remove BBCode
        $content = preg_replace('/\[(?:[^\]]*)\]/', '', $content);
        
        // Remove HTML tags
        $content = strip_tags($content);
        
        // Clean up whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        return trim($content);
    }

    /**
     * Add formatting to summary text
     * 
     * @param string $summary Summary text
     * @return string Formatted summary
     */
    private function addSummaryFormatting(string $summary): string
    {
        // Add proper paragraph breaks
        $summary = str_replace(["\r\n", "\n", "\r"], ' ', $summary);
        $summary = preg_replace('/\s+/', ' ', $summary);
        
        return trim($summary);
    }

    /**
     * Extract highlight terms from result
     * 
     * @param array $result Result data
     * @return array Highlight terms
     */
    private function extractHighlightTerms(array $result): array
    {
        // This would be enhanced to extract actual search terms
        return [];
    }

    /**
     * Generate content snippet
     * 
     * @param array $result Result data
     * @return string Content snippet
     */
    private function generateSnippet(array $result): string
    {
        $content = $result['content'] ?? '';
        return $this->formatContent($content);
    }

    /**
     * Create pagination info
     * 
     * @param array $data Search response data
     * @return array Pagination info
     */
    private function createPagination(array $data): array
    {
        $totalResults = $data['total_results'] ?? 0;
        $resultsPerPage = (int)($this->modSettings['sphinxai_results_per_page'] ?? 10);
        $currentPage = 1; // This would come from request parameters
        
        $totalPages = ceil($totalResults / $resultsPerPage);
        
        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'results_per_page' => $resultsPerPage,
            'total_results' => $totalResults,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages
        ];
    }

    /**
     * Calculate search execution time
     * 
     * @return string Formatted search time
     */
    private function calculateSearchTime(): string
    {
        // This would be calculated from actual timing data
        return '0.15s';
    }

    /**
     * Generate search suggestions
     * 
     * @param string $query Original query
     * @return array Search suggestions
     */
    private function generateSearchSuggestions(string $query): array
    {
        // This could be enhanced with actual suggestion logic
        return [];
    }
}

/**
 * Factory function to create formatter instance
 * 
 * @param array $smcFunc SMF functions
 * @param array $modSettings Forum settings
 * @param array $txt Language strings
 * @param string $scriptUrl Script URL
 * @return SphinxAIResultFormatter Formatter instance
 */
function createSphinxAIResultFormatter(
    array $smcFunc,
    array $modSettings,
    array $txt,
    string $scriptUrl
): SphinxAIResultFormatter {
    return new SphinxAIResultFormatter($smcFunc, $modSettings, $txt, $scriptUrl);
}
