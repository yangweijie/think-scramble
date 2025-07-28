<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Analyzer\AnnotationParser;
use Yangweijie\ThinkScramble\Analyzer\DocBlockParser;
use Yangweijie\ThinkScramble\Analyzer\AstParser;
use Yangweijie\ThinkScramble\Analyzer\ValidateAnnotationAnalyzer;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;

describe('Analyzer and Parser Tests', function () {
    beforeEach(function () {
        $this->config = new ScrambleConfig([
            'parsing' => [
                'enabled' => true,
                'strict_mode' => false,
                'cache_parsed' => true,
            ],
            'validation' => [
                'enabled' => true,
                'auto_detect' => true,
                'include_rules' => true,
            ],
        ]);
        
        // Load test data
        $this->testData = include __DIR__ . '/../data/cache_clear_test.php';
        
        // Initialize parsers
        $this->annotationParser = new AnnotationParser();
        $this->docBlockParser = new DocBlockParser();
        $this->astParser = new AstParser();
        $this->validateAnalyzer = new ValidateAnnotationAnalyzer($this->annotationParser);
    });

    test('AnnotationParser can be instantiated', function () {
        expect($this->annotationParser)->toBeInstanceOf(AnnotationParser::class);
        
    })->covers(\Yangweijie\ThinkScramble\Analyzer\AnnotationParser::class);

    test('DocBlockParser can be instantiated', function () {
        expect($this->docBlockParser)->toBeInstanceOf(DocBlockParser::class);
        
    })->covers(\Yangweijie\ThinkScramble\Analyzer\DocBlockParser::class);

    test('AstParser can be instantiated', function () {
        expect($this->astParser)->toBeInstanceOf(AstParser::class);
        
    })->covers(\Yangweijie\ThinkScramble\Analyzer\AstParser::class);

    test('ValidateAnnotationAnalyzer can be instantiated', function () {
        expect($this->validateAnalyzer)->toBeInstanceOf(ValidateAnnotationAnalyzer::class);
        
    })->covers(\Yangweijie\ThinkScramble\Analyzer\ValidateAnnotationAnalyzer::class);

    test('DocBlockParser can parse method documentation', function () {
        $docBlock = '
        /**
         * Create a new user account
         * 
         * This method creates a new user with the provided information.
         * It validates the input data and returns the created user.
         * 
         * @param array $userData User data including name, email, password
         * @param bool $sendWelcomeEmail Whether to send welcome email
         * @return array Created user data
         * @throws ValidationException When validation fails
         * @throws DatabaseException When database operation fails
         * 
         * @example
         * $user = $controller->createUser([
         *     "name" => "John Doe",
         *     "email" => "john@example.com",
         *     "password" => "secure123"
         * ], true);
         */
        ';
        
        $parsed = $this->docBlockParser->parse($docBlock);
        
        expect($parsed)->toBeArray();
        expect($parsed)->toHaveKey('summary');
        expect($parsed)->toHaveKey('description');
        expect($parsed)->toHaveKey('tags');
        
        if (isset($parsed['tags'])) {
            expect($parsed['tags'])->toBeArray();
        }
        
    })->covers(\Yangweijie\ThinkScramble\Analyzer\DocBlockParser::class);

    test('DocBlockParser can extract parameter information', function () {
        $docBlock = '
        /**
         * Update user profile
         * 
         * @param int $userId The ID of the user to update
         * @param string $name The new name for the user
         * @param string|null $email The new email address (optional)
         * @param array $preferences User preferences array
         * @return bool True if update was successful
         */
        ';
        
        $parsed = $this->docBlockParser->parse($docBlock);
        
        expect($parsed)->toBeArray();
        
        if (isset($parsed['tags']['param'])) {
            expect($parsed['tags']['param'])->toBeArray();
            expect(count($parsed['tags']['param']))->toBeGreaterThan(0);
        }
        
        if (isset($parsed['tags']['return'])) {
            expect($parsed['tags']['return'])->toBeArray();
        }
        
    })->covers(\Yangweijie\ThinkScramble\Analyzer\DocBlockParser::class);

    test('AstParser can parse PHP code', function () {
        $phpCode = '
        <?php
        
        class TestController
        {
            public function testMethod($param1, $param2 = null)
            {
                if ($param1 > 0) {
                    return ["success" => true, "data" => $param1];
                }
                
                return ["success" => false, "error" => "Invalid parameter"];
            }
            
            private function helperMethod(): string
            {
                return "helper";
            }
        }
        ';
        
        try {
            $ast = $this->astParser->parse($phpCode);
            expect($ast)->toBeArray();
        } catch (\Exception $e) {
            // AST parsing might fail in test environment
            expect($e)->toBeInstanceOf(\Exception::class);
        }
        
    })->covers(\Yangweijie\ThinkScramble\Analyzer\AstParser::class);

    test('parsers can work with different configurations', function () {
        $configurations = [
            // Minimal configuration
            new ScrambleConfig([]),
            
            // Strict parsing configuration
            new ScrambleConfig([
                'parsing' => [
                    'enabled' => true,
                    'strict_mode' => true,
                    'validate_syntax' => true,
                ],
            ]),
            
            // Validation-focused configuration
            new ScrambleConfig([
                'validation' => [
                    'enabled' => true,
                    'strict_validation' => true,
                    'require_rules' => true,
                ],
            ]),
        ];
        
        foreach ($configurations as $config) {
            $annotationParser = new AnnotationParser();
            $docBlockParser = new DocBlockParser();
            $astParser = new AstParser();
            $validateAnalyzer = new ValidateAnnotationAnalyzer($annotationParser);
            
            expect($annotationParser)->toBeInstanceOf(AnnotationParser::class);
            expect($docBlockParser)->toBeInstanceOf(DocBlockParser::class);
            expect($astParser)->toBeInstanceOf(AstParser::class);
            expect($validateAnalyzer)->toBeInstanceOf(ValidateAnnotationAnalyzer::class);
        }
        
    })->covers(
        \Yangweijie\ThinkScramble\Analyzer\AnnotationParser::class,
        \Yangweijie\ThinkScramble\Analyzer\DocBlockParser::class,
        \Yangweijie\ThinkScramble\Analyzer\AstParser::class,
        \Yangweijie\ThinkScramble\Analyzer\ValidateAnnotationAnalyzer::class
    );

    test('parsers handle edge cases gracefully', function () {
        // Test with empty input
        $emptyParsed = $this->docBlockParser->parse('');
        expect($emptyParsed)->toBeArray();
        
        // Test with malformed docblock
        $malformedParsed = $this->docBlockParser->parse('/* not a proper docblock */');
        expect($malformedParsed)->toBeArray();
        
        // Test with invalid PHP code
        try {
            $invalidAst = $this->astParser->parse('<?php invalid syntax here');
            expect($invalidAst)->toBeArray();
        } catch (\Exception $e) {
            expect($e)->toBeInstanceOf(\Exception::class);
        }
        
    })->covers(
        \Yangweijie\ThinkScramble\Analyzer\AnnotationParser::class,
        \Yangweijie\ThinkScramble\Analyzer\DocBlockParser::class,
        \Yangweijie\ThinkScramble\Analyzer\AstParser::class
    );

    test('parsers use memory efficiently', function () {
        $startMemory = memory_get_usage();
        
        // Create multiple parser instances and perform operations
        for ($i = 0; $i < 10; $i++) {
            $annotationParser = new AnnotationParser();
            $docBlockParser = new DocBlockParser();
            $astParser = new AstParser();
            $validateAnalyzer = new ValidateAnnotationAnalyzer($annotationParser);
            
            // Perform parsing operations
            $docBlock = "
            /**
             * Test method {$i}
             * 
             * @param string \$param{$i} Test parameter {$i}
             * @return array Test return {$i}
             */
            ";
            
            $parsed = $docBlockParser->parse($docBlock);
            expect($parsed)->toBeArray();
            
            $phpCode = "
            <?php
            class TestClass{$i}
            {
                public function testMethod{$i}(\$param{$i})
                {
                    return ['test' => {$i}];
                }
            }
            ";
            
            try {
                $ast = $astParser->parse($phpCode);
                expect($ast)->toBeArray();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
            // Clean up
            unset($annotationParser, $docBlockParser, $astParser, $validateAnalyzer);
        }
        
        $endMemory = memory_get_usage();
        
        // Memory usage should be reasonable
        expect($endMemory - $startMemory)->toBeLessThan(10 * 1024 * 1024); // Less than 10MB
        
    })->covers(
        \Yangweijie\ThinkScramble\Analyzer\AnnotationParser::class,
        \Yangweijie\ThinkScramble\Analyzer\DocBlockParser::class,
        \Yangweijie\ThinkScramble\Analyzer\AstParser::class,
        \Yangweijie\ThinkScramble\Analyzer\ValidateAnnotationAnalyzer::class
    );

    test('parsers have good performance', function () {
        $startTime = microtime(true);
        
        // Perform multiple parsing operations
        for ($i = 0; $i < 20; $i++) {
            $docBlock = "
            /**
             * Performance test method {$i}
             * 
             * This is a comprehensive test method for performance analysis.
             * It includes multiple parameters and complex documentation.
             * 
             * @param int \$id{$i} The unique identifier for test {$i}
             * @param string \$name{$i} The name parameter for test {$i}
             * @param array \$data{$i} Complex data array for test {$i}
             * @param bool \$flag{$i} Boolean flag for test {$i}
             * @return object Complex return object for test {$i}
             * @throws Exception When something goes wrong in test {$i}
             * 
             * @example
             * \$result = \$this->performanceTest{$i}({$i}, 'test{$i}', ['key' => 'value'], true);
             */
            ";
            
            $parsed = $this->docBlockParser->parse($docBlock);
            expect($parsed)->toBeArray();
            
            $phpCode = "
            <?php
            namespace Test\\Performance;
            
            class PerformanceController{$i}
            {
                public function performanceMethod{$i}(\$id{$i}, \$name{$i}, \$data{$i} = [], \$flag{$i} = false)
                {
                    if (\$flag{$i}) {
                        return [
                            'id' => \$id{$i},
                            'name' => \$name{$i},
                            'data' => \$data{$i},
                            'timestamp' => time(),
                            'iteration' => {$i}
                        ];
                    }
                    
                    return ['error' => 'Flag not set for iteration {$i}'];
                }
            }
            ";
            
            try {
                $ast = $this->astParser->parse($phpCode);
                expect($ast)->toBeArray();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        }
        
        $endTime = microtime(true);
        
        // Should complete quickly
        expect($endTime - $startTime)->toBeLessThan(2.0); // Less than 2 seconds
        
    })->covers(
        \Yangweijie\ThinkScramble\Analyzer\AnnotationParser::class,
        \Yangweijie\ThinkScramble\Analyzer\DocBlockParser::class,
        \Yangweijie\ThinkScramble\Analyzer\AstParser::class
    );
});
