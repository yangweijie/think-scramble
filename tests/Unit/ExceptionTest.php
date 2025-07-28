<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Exception\ScrambleException;
use Yangweijie\ThinkScramble\Exception\ConfigException;
use Yangweijie\ThinkScramble\Exception\AnalysisException;
use Yangweijie\ThinkScramble\Exception\GenerationException;
use Yangweijie\ThinkScramble\Exception\CacheException;
use Yangweijie\ThinkScramble\Exception\PerformanceException;

describe('Exception Classes Tests', function () {
    test('ScrambleException can be instantiated with message', function () {
        $message = 'Test scramble exception';
        $exception = new ScrambleException($message);
        
        expect($exception)->toBeInstanceOf(ScrambleException::class);
        expect($exception)->toBeInstanceOf(\Exception::class);
        expect($exception->getMessage())->toBe($message);
        expect($exception->getCode())->toBe(0);
    })->covers(\Yangweijie\ThinkScramble\Exception\ScrambleException::class);

    test('ScrambleException can be instantiated with message and code', function () {
        $message = 'Test scramble exception with code';
        $code = 500;
        $exception = new ScrambleException($message, $code);
        
        expect($exception->getMessage())->toBe($message);
        expect($exception->getCode())->toBe($code);
    })->covers(\Yangweijie\ThinkScramble\Exception\ScrambleException::class);

    test('ScrambleException can be instantiated with previous exception', function () {
        $previousException = new \Exception('Previous exception');
        $message = 'Test scramble exception with previous';
        $code = 400;
        
        $exception = new ScrambleException($message, $code, $previousException);
        
        expect($exception->getMessage())->toBe($message);
        expect($exception->getCode())->toBe($code);
        expect($exception->getPrevious())->toBe($previousException);
    })->covers(\Yangweijie\ThinkScramble\Exception\ScrambleException::class);

    test('ConfigException inherits from ScrambleException', function () {
        $message = 'Configuration error';
        $exception = new ConfigException($message);
        
        expect($exception)->toBeInstanceOf(ConfigException::class);
        expect($exception)->toBeInstanceOf(ScrambleException::class);
        expect($exception)->toBeInstanceOf(\Exception::class);
        expect($exception->getMessage())->toBe($message);
    })->covers(\Yangweijie\ThinkScramble\Exception\ConfigException::class);

    test('AnalysisException inherits from ScrambleException', function () {
        $message = 'Analysis error';
        $exception = new AnalysisException($message);
        
        expect($exception)->toBeInstanceOf(AnalysisException::class);
        expect($exception)->toBeInstanceOf(ScrambleException::class);
        expect($exception)->toBeInstanceOf(\Exception::class);
        expect($exception->getMessage())->toBe($message);
    })->covers(\Yangweijie\ThinkScramble\Exception\AnalysisException::class);

    test('GenerationException inherits from ScrambleException', function () {
        $message = 'Generation error';
        $exception = new GenerationException($message);
        
        expect($exception)->toBeInstanceOf(GenerationException::class);
        expect($exception)->toBeInstanceOf(ScrambleException::class);
        expect($exception)->toBeInstanceOf(\Exception::class);
        expect($exception->getMessage())->toBe($message);
    })->covers(\Yangweijie\ThinkScramble\Exception\GenerationException::class);

    test('CacheException inherits from ScrambleException', function () {
        $message = 'Cache error';
        $exception = new CacheException($message);
        
        expect($exception)->toBeInstanceOf(CacheException::class);
        expect($exception)->toBeInstanceOf(ScrambleException::class);
        expect($exception)->toBeInstanceOf(\Exception::class);
        expect($exception->getMessage())->toBe($message);
    })->covers(\Yangweijie\ThinkScramble\Exception\CacheException::class);

    test('PerformanceException inherits from ScrambleException', function () {
        $message = 'Performance error';
        $exception = new PerformanceException($message);

        expect($exception)->toBeInstanceOf(PerformanceException::class);
        expect($exception)->toBeInstanceOf(ScrambleException::class);
        expect($exception)->toBeInstanceOf(\Exception::class);
        expect($exception->getMessage())->toBe($message);
    })->covers(\Yangweijie\ThinkScramble\Exception\PerformanceException::class);

    test('exceptions can be thrown and caught', function () {
        $message = 'Test throwable exception';
        
        try {
            throw new ScrambleException($message);
        } catch (ScrambleException $e) {
            expect($e->getMessage())->toBe($message);
            expect($e)->toBeInstanceOf(ScrambleException::class);
        }
        
        try {
            throw new ConfigException($message);
        } catch (ScrambleException $e) {
            expect($e->getMessage())->toBe($message);
            expect($e)->toBeInstanceOf(ConfigException::class);
        }
    })->covers(
        \Yangweijie\ThinkScramble\Exception\ScrambleException::class,
        \Yangweijie\ThinkScramble\Exception\ConfigException::class
    );

    test('exceptions can be chained', function () {
        $rootCause = new \InvalidArgumentException('Root cause');
        $scrambleException = new ScrambleException('Scramble error', 0, $rootCause);
        $configException = new ConfigException('Config error', 0, $scrambleException);
        
        expect($configException->getPrevious())->toBe($scrambleException);
        expect($scrambleException->getPrevious())->toBe($rootCause);
        expect($rootCause->getPrevious())->toBeNull();
        
        // Test exception chain traversal
        $current = $configException;
        $count = 0;
        while ($current !== null) {
            $count++;
            $current = $current->getPrevious();
        }
        expect($count)->toBe(3);
    })->covers(
        \Yangweijie\ThinkScramble\Exception\ScrambleException::class,
        \Yangweijie\ThinkScramble\Exception\ConfigException::class
    );

    test('exceptions can have different error codes', function () {
        $exceptions = [
            new ScrambleException('General error', 500),
            new ConfigException('Config error', 400),
            new AnalysisException('Analysis error', 422),
            new GenerationException('Generation error', 500),
            new CacheException('Cache error', 503),
            new PerformanceException('Performance error', 400)
        ];
        
        foreach ($exceptions as $exception) {
            expect($exception->getCode())->toBeInt();
            expect($exception->getCode())->toBeGreaterThanOrEqual(0);
        }
        
        expect($exceptions[0]->getCode())->toBe(500);
        expect($exceptions[1]->getCode())->toBe(400);
        expect($exceptions[2]->getCode())->toBe(422);
        expect($exceptions[3]->getCode())->toBe(500);
        expect($exceptions[4]->getCode())->toBe(503);
        expect($exceptions[5]->getCode())->toBe(400);
    })->covers(
        \Yangweijie\ThinkScramble\Exception\ScrambleException::class,
        \Yangweijie\ThinkScramble\Exception\ConfigException::class,
        \Yangweijie\ThinkScramble\Exception\AnalysisException::class,
        \Yangweijie\ThinkScramble\Exception\GenerationException::class,
        \Yangweijie\ThinkScramble\Exception\CacheException::class,
        \Yangweijie\ThinkScramble\Exception\PerformanceException::class
    );

    test('exceptions can have empty messages', function () {
        $exceptions = [
            new ScrambleException(''),
            new ConfigException(''),
            new AnalysisException(''),
            new GenerationException(''),
            new CacheException(''),
            new PerformanceException('')
        ];
        
        foreach ($exceptions as $exception) {
            expect($exception->getMessage())->toBe('');
            expect($exception)->toBeInstanceOf(\Exception::class);
        }
    })->covers(
        \Yangweijie\ThinkScramble\Exception\ScrambleException::class,
        \Yangweijie\ThinkScramble\Exception\ConfigException::class,
        \Yangweijie\ThinkScramble\Exception\AnalysisException::class,
        \Yangweijie\ThinkScramble\Exception\GenerationException::class,
        \Yangweijie\ThinkScramble\Exception\CacheException::class,
        \Yangweijie\ThinkScramble\Exception\PerformanceException::class
    );

    test('exceptions can have very long messages', function () {
        $longMessage = str_repeat('This is a very long error message. ', 100);
        
        $exception = new ScrambleException($longMessage);
        
        expect($exception->getMessage())->toBe($longMessage);
        expect(strlen($exception->getMessage()))->toBeGreaterThan(1000);
    })->covers(\Yangweijie\ThinkScramble\Exception\ScrambleException::class);

    test('exceptions can be converted to JSON', function () {
        $originalException = new ConfigException('JSON test', 400);

        $data = [
            'message' => $originalException->getMessage(),
            'code' => $originalException->getCode(),
            'file' => $originalException->getFile(),
            'line' => $originalException->getLine()
        ];

        $json = json_encode($data);
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray();
        expect($decoded['message'])->toBe($originalException->getMessage());
        expect($decoded['code'])->toBe($originalException->getCode());
        expect($decoded['file'])->toBe($originalException->getFile());
        expect($decoded['line'])->toBe($originalException->getLine());
    })->covers(\Yangweijie\ThinkScramble\Exception\ConfigException::class);

    test('exceptions have proper stack traces', function () {
        $exception = new AnalysisException('Stack trace test');

        $trace = $exception->getTrace();
        expect($trace)->toBeArray();

        $traceString = $exception->getTraceAsString();
        expect($traceString)->toBeString();
        expect(strlen($traceString))->toBeGreaterThan(0);

        // Check that it contains some expected elements
        expect($traceString)->toContain('#0');
        expect($traceString)->toContain('(');
        expect($traceString)->toContain(')');
    })->covers(\Yangweijie\ThinkScramble\Exception\AnalysisException::class);

    test('exceptions can be converted to string', function () {
        $message = 'String conversion test';
        $exception = new GenerationException($message);
        
        $stringRepresentation = (string) $exception;
        
        expect($stringRepresentation)->toBeString();
        expect($stringRepresentation)->toContain($message);
        expect($stringRepresentation)->toContain('GenerationException');
        expect($stringRepresentation)->toContain(__FILE__);
    })->covers(\Yangweijie\ThinkScramble\Exception\GenerationException::class);

    test('exceptions use memory efficiently', function () {
        $startMemory = memory_get_usage();
        
        // Create many exceptions
        $exceptions = [];
        for ($i = 0; $i < 100; $i++) {
            $exceptions[] = new ScrambleException("Exception {$i}");
        }
        
        $endMemory = memory_get_usage();
        $memoryUsed = $endMemory - $startMemory;
        
        // Should use less than 2MB for 100 exceptions
        expect($memoryUsed)->toBeLessThan(2 * 1024 * 1024);
        expect($exceptions)->toHaveCount(100);
    })->covers(\Yangweijie\ThinkScramble\Exception\ScrambleException::class);

    test('exceptions have good performance', function () {
        $startTime = microtime(true);
        
        // Create and throw many exceptions
        for ($i = 0; $i < 100; $i++) {
            try {
                throw new CacheException("Performance test {$i}");
            } catch (CacheException $e) {
                // Caught and continue
            }
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Should complete in less than 0.1 seconds
        expect($duration)->toBeLessThan(0.1);
    })->covers(\Yangweijie\ThinkScramble\Exception\CacheException::class);

    test('exception hierarchy is correct', function () {
        $scrambleException = new ScrambleException('Base exception');
        $configException = new ConfigException('Config exception');
        $analysisException = new AnalysisException('Analysis exception');
        $generationException = new GenerationException('Generation exception');
        $cacheException = new CacheException('Cache exception');
        $performanceException = new PerformanceException('Performance exception');
        
        // Test instanceof relationships
        expect($scrambleException)->toBeInstanceOf(\Exception::class);
        expect($scrambleException)->toBeInstanceOf(\Throwable::class);
        
        expect($configException)->toBeInstanceOf(ScrambleException::class);
        expect($analysisException)->toBeInstanceOf(ScrambleException::class);
        expect($generationException)->toBeInstanceOf(ScrambleException::class);
        expect($cacheException)->toBeInstanceOf(ScrambleException::class);
        expect($performanceException)->toBeInstanceOf(ScrambleException::class);

        // Test that they are all throwable
        expect($configException)->toBeInstanceOf(\Throwable::class);
        expect($analysisException)->toBeInstanceOf(\Throwable::class);
        expect($generationException)->toBeInstanceOf(\Throwable::class);
        expect($cacheException)->toBeInstanceOf(\Throwable::class);
        expect($performanceException)->toBeInstanceOf(\Throwable::class);
    })->covers(
        \Yangweijie\ThinkScramble\Exception\ScrambleException::class,
        \Yangweijie\ThinkScramble\Exception\ConfigException::class,
        \Yangweijie\ThinkScramble\Exception\AnalysisException::class,
        \Yangweijie\ThinkScramble\Exception\GenerationException::class,
        \Yangweijie\ThinkScramble\Exception\CacheException::class,
        \Yangweijie\ThinkScramble\Exception\PerformanceException::class
    );
});
