<?php

declare(strict_types=1);

namespace SMF\SphinxAI\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Mockery;

/**
 * Base Test Case for SMF Sphinx AI Search Plugin
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset global state
        $this->resetGlobalState();
        
        // Set up test-specific configuration
        $this->setUpTestConfiguration();
    }
    
    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        // Close all Mockery mocks
        Mockery::close();
        
        parent::tearDown();
    }
    
    /**
     * Reset global state for clean tests
     */
    protected function resetGlobalState(): void
    {
        global $modSettings, $user_info, $context;
        
        // Reset modSettings to default test values
        $modSettings = [
            'sphinx_ai_enabled' => '1',
            'sphinx_ai_model_path' => '/tmp/test_model',
            'sphinx_ai_max_results' => '50',
            'cache_enable' => '1',
            'cache_memcached' => '',
        ];
        
        // Reset user info
        $user_info = [
            'id' => 1,
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'groups' => [1],
            'is_admin' => true,
            'is_guest' => false,
        ];
        
        // Reset context
        $context = [
            'user' => $user_info,
            'session_id' => 'test_session_id',
            'session_var' => 'sesc',
        ];
    }
    
    /**
     * Set up test-specific configuration
     */
    protected function setUpTestConfiguration(): void
    {
        // Override in subclasses if needed
    }
    
    /**
     * Create a mock SMF database connection
     */
    protected function mockDatabase(): Mockery\MockInterface
    {
        return Mockery::mock('SMFDatabase');
    }
    
    /**
     * Create a mock cache interface
     */
    protected function mockCache(): Mockery\MockInterface
    {
        return Mockery::mock('SMFCache');
    }
    
    /**
     * Create a mock HTTP client
     */
    protected function mockHttpClient(): Mockery\MockInterface
    {
        return Mockery::mock('HttpClient');
    }
    
    /**
     * Assert that an array has specific keys
     */
    protected function assertArrayHasKeys(array $keys, array $array, string $message = ''): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "Array should have key '{$key}'");
        }
    }
    
    /**
     * Assert that a string is valid JSON
     */
    protected function assertValidJson(string $json, string $message = ''): void
    {
        json_decode($json);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), $message ?: 'String should be valid JSON');
    }
    
    /**
     * Create test search results data
     */
    protected function createTestSearchResults(int $count = 5): array
    {
        $results = [];
        for ($i = 1; $i <= $count; $i++) {
            $results[] = [
                'id' => $i,
                'title' => "Test Post {$i}",
                'content' => "This is test content for post {$i}",
                'author' => "testuser{$i}",
                'date' => date('Y-m-d H:i:s', time() - ($i * 3600)),
                'board' => 'Test Board',
                'relevance' => 1.0 - ($i * 0.1),
            ];
        }
        return $results;
    }
    
    /**
     * Create test configuration data
     */
    protected function createTestConfig(): array
    {
        return [
            'sphinx_ai_enabled' => true,
            'sphinx_ai_model_path' => '/tmp/test_model',
            'sphinx_ai_max_results' => 50,
            'sphinx_ai_confidence_threshold' => 0.1,
            'sphinx_ai_cache_enabled' => true,
            'sphinx_ai_cache_ttl' => 3600,
        ];
    }
    
    /**
     * Get path to test fixtures
     */
    protected function getFixturesPath(): string
    {
        return __DIR__ . '/../fixtures';
    }
    
    /**
     * Load test fixture data
     */
    protected function loadFixture(string $filename): array
    {
        $path = $this->getFixturesPath() . '/' . $filename;
        $this->assertFileExists($path, "Fixture file {$filename} should exist");
        
        $content = file_get_contents($path);
        $this->assertNotFalse($content, "Should be able to read fixture file {$filename}");
        
        return json_decode($content, true) ?: [];
    }
}
