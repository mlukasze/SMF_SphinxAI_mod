<?php

declare(strict_types=1);

namespace SMF\SphinxAI\Tests\Unit;

use SMF\SphinxAI\Tests\TestCase;
use SMF\SphinxAI\Tests\MockFactory;
use Mockery;

/**
 * Unit tests for SphinxAI Cache Service
 */
class SphinxAICacheTest extends TestCase
{
    private $cache;
    private $mockRedis;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Redis client
        $this->mockRedis = Mockery::mock('Redis');
        
        // Include the cache class (adjust path as needed)
        if (!class_exists('SphinxAICache')) {
            require_once __DIR__ . '/../../../php/services/SphinxAICache.php';
        }
        
        $this->cache = new \SphinxAICache();
    }
    
    public function testCacheIsEnabledByDefault(): void
    {
        $this->assertTrue($this->cache->isEnabled());
    }
    
    public function testCanStoreAndRetrieveData(): void
    {
        $key = 'test_key';
        $value = ['test' => 'data'];
        
        // Test storing data
        $result = $this->cache->set($key, $value, 3600);
        $this->assertTrue($result);
        
        // Test retrieving data
        $retrieved = $this->cache->get($key);
        $this->assertEquals($value, $retrieved);
    }
    
    public function testCacheKeyGeneration(): void
    {
        $type = 'search';
        $key = 'test_query';
        
        $cacheKey = $this->cache->getCacheKey($type, $key);
        
        $this->assertIsString($cacheKey);
        $this->assertStringContainsString('search:', $cacheKey);
        $this->assertStringContainsString('sphinxai:', $cacheKey);
    }
    
    public function testSearchResultsCaching(): void
    {
        $query = 'test search query';
        $filters = ['board' => [1, 2, 3]];
        $results = MockFactory::createSearchResultsData(3);
        
        // Test caching search results
        $success = $this->cache->cacheSearchResults($query, $filters, $results);
        $this->assertTrue($success);
        
        // Test retrieving cached results
        $cached = $this->cache->getCachedSearchResults($query, $filters);
        $this->assertIsArray($cached);
        $this->assertArrayHasKey('results', $cached);
        $this->assertArrayHasKey('timestamp', $cached);
        $this->assertEquals($results, $cached['results']);
    }
    
    public function testEmbeddingsCaching(): void
    {
        $text = 'test text for embeddings';
        $modelId = 'test-model-v1';
        $embeddings = MockFactory::createEmbeddingsData(128);
        
        // Test caching embeddings
        $success = $this->cache->cacheEmbeddings($text, $embeddings, $modelId);
        $this->assertTrue($success);
        
        // Test retrieving cached embeddings
        $cached = $this->cache->getCachedEmbeddings($text, $modelId);
        $this->assertEquals($embeddings, $cached);
    }
    
    public function testModelMetadataCaching(): void
    {
        $modelId = 'test-model-v1';
        $metadata = [
            'name' => 'Test Model',
            'version' => '1.0',
            'dimensions' => 768,
            'type' => 'sentence-transformer',
        ];
        
        // Test caching metadata
        $success = $this->cache->cacheModelMetadata($modelId, $metadata);
        $this->assertTrue($success);
        
        // Test retrieving cached metadata
        $cached = $this->cache->getCachedModelMetadata($modelId);
        $this->assertEquals($metadata, $cached);
    }
    
    public function testCacheStatistics(): void
    {
        $query = 'test query';
        $resultCount = 5;
        $responseTime = 0.25;
        
        // Test updating search stats
        $success = $this->cache->updateSearchStats($query, $resultCount, $responseTime);
        $this->assertTrue($success);
        
        // Test retrieving stats
        $stats = $this->cache->getSearchStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKeys([
            'total_searches',
            'popular_queries',
            'avg_response_time',
            'avg_result_count',
            'cache_hit_rate'
        ], $stats);
    }
    
    public function testCacheClearance(): void
    {
        // Add some test data
        $this->cache->set('test_key_1', 'value1');
        $this->cache->set('test_key_2', 'value2');
        
        // Test clearing specific pattern
        $cleared = $this->cache->clear('test_*');
        $this->assertGreaterThanOrEqual(0, $cleared);
        
        // Test data is cleared
        $this->assertNull($this->cache->get('test_key_1'));
        $this->assertNull($this->cache->get('test_key_2'));
    }
    
    public function testCacheWithTTL(): void
    {
        $key = 'ttl_test_key';
        $value = 'test_value';
        $ttl = 1; // 1 second
        
        // Store with TTL
        $this->cache->set($key, $value, $ttl);
        
        // Should be available immediately
        $this->assertEquals($value, $this->cache->get($key));
        
        // Sleep and check expiration (in real implementation)
        // Note: This would require actual Redis for integration testing
    }
    
    public function testCacheConnectionFailure(): void
    {
        // Test behavior when cache is unavailable
        $cache = new \SphinxAICache();
        $cache->setEnabled(false);
        
        $this->assertFalse($cache->isEnabled());
        $this->assertFalse($cache->set('test', 'value'));
        $this->assertNull($cache->get('test'));
    }
    
    public function testCacheHitAndMissTracking(): void
    {
        $key = 'hit_miss_test';
        
        // First access should be a miss
        $result = $this->cache->get($key);
        $this->assertNull($result);
        
        // Store value
        $this->cache->set($key, 'test_value');
        
        // Second access should be a hit
        $result = $this->cache->get($key);
        $this->assertEquals('test_value', $result);
        
        // Check hit rate calculation
        $stats = $this->cache->getSearchStats();
        $this->assertArrayHasKey('cache_hit_rate', $stats);
        $this->assertIsFloat($stats['cache_hit_rate']);
    }
}
