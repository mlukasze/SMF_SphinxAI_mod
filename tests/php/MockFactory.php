<?php

declare(strict_types=1);

namespace SMF\SphinxAI\Tests;

use Mockery;

/**
 * Factory for creating test mocks and stubs
 */
class MockFactory
{
    /**
     * Create a mock SphinxAI cache service
     */
    public static function createCacheMock(): Mockery\MockInterface
    {
        $mock = Mockery::mock('SphinxAICache');
        
        $mock->shouldReceive('isEnabled')
             ->andReturn(true)
             ->byDefault();
             
        $mock->shouldReceive('get')
             ->andReturn(null)
             ->byDefault();
             
        $mock->shouldReceive('set')
             ->andReturn(true)
             ->byDefault();
             
        $mock->shouldReceive('delete')
             ->andReturn(true)
             ->byDefault();
             
        $mock->shouldReceive('clear')
             ->andReturn(true)
             ->byDefault();
        
        return $mock;
    }
    
    /**
     * Create a mock configuration service
     */
    public static function createConfigMock(): Mockery\MockInterface
    {
        $mock = Mockery::mock('SphinxAIConfigGenerator');
        
        $mock->shouldReceive('generateConfig')
             ->andReturn(true)
             ->byDefault();
             
        $mock->shouldReceive('getConfig')
             ->andReturn([
                 'model_path' => '/tmp/test_model',
                 'max_results' => 50,
                 'confidence_threshold' => 0.1,
             ])
             ->byDefault();
        
        return $mock;
    }
    
    /**
     * Create a mock search service
     */
    public static function createSearchMock(): Mockery\MockInterface
    {
        $mock = Mockery::mock('SphinxAISearchService');
        
        $mock->shouldReceive('search')
             ->andReturn([
                 'results' => [
                     [
                         'id' => 1,
                         'title' => 'Test Post',
                         'content' => 'Test content',
                         'relevance' => 0.9,
                     ]
                 ],
                 'total' => 1,
                 'time' => 0.1,
             ])
             ->byDefault();
             
        $mock->shouldReceive('isAvailable')
             ->andReturn(true)
             ->byDefault();
        
        return $mock;
    }
    
    /**
     * Create a mock HTTP response
     */
    public static function createHttpResponseMock(int $statusCode = 200, array $data = []): Mockery\MockInterface
    {
        $mock = Mockery::mock('HttpResponse');
        
        $mock->shouldReceive('getStatusCode')
             ->andReturn($statusCode);
             
        $mock->shouldReceive('getBody')
             ->andReturn(json_encode($data));
             
        $mock->shouldReceive('isSuccess')
             ->andReturn($statusCode >= 200 && $statusCode < 300);
        
        return $mock;
    }
    
    /**
     * Create a mock logger
     */
    public static function createLoggerMock(): Mockery\MockInterface
    {
        $mock = Mockery::mock('Logger');
        
        $mock->shouldReceive('info')
             ->andReturnNull()
             ->byDefault();
             
        $mock->shouldReceive('error')
             ->andReturnNull()
             ->byDefault();
             
        $mock->shouldReceive('warning')
             ->andReturnNull()
             ->byDefault();
             
        $mock->shouldReceive('debug')
             ->andReturnNull()
             ->byDefault();
        
        return $mock;
    }
    
    /**
     * Create a mock file system
     */
    public static function createFileSystemMock(): Mockery\MockInterface
    {
        $mock = Mockery::mock('FileSystem');
        
        $mock->shouldReceive('exists')
             ->andReturn(true)
             ->byDefault();
             
        $mock->shouldReceive('read')
             ->andReturn('test file content')
             ->byDefault();
             
        $mock->shouldReceive('write')
             ->andReturn(true)
             ->byDefault();
             
        $mock->shouldReceive('delete')
             ->andReturn(true)
             ->byDefault();
        
        return $mock;
    }
    
    /**
     * Create test data for search results
     */
    public static function createSearchResultsData(int $count = 5): array
    {
        $results = [];
        for ($i = 1; $i <= $count; $i++) {
            $results[] = [
                'id' => $i,
                'title' => "Test Post {$i}",
                'content' => "This is test content for post {$i}. It contains keywords for testing search functionality.",
                'author' => "testuser{$i}",
                'author_id' => $i,
                'date' => date('Y-m-d H:i:s', time() - ($i * 3600)),
                'board' => 'Test Board',
                'board_id' => 1,
                'topic_id' => $i,
                'message_id' => $i,
                'relevance' => round(1.0 - ($i * 0.1), 2),
                'snippet' => "...test content snippet {$i}...",
            ];
        }
        return $results;
    }
    
    /**
     * Create test embeddings data
     */
    public static function createEmbeddingsData(int $dimensions = 768): array
    {
        $embeddings = [];
        for ($i = 0; $i < $dimensions; $i++) {
            $embeddings[] = rand(0, 1000) / 1000.0 - 0.5; // Random float between -0.5 and 0.5
        }
        return $embeddings;
    }
}
