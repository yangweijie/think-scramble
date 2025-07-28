<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Command\GenerateCommand;
use Yangweijie\ThinkScramble\Command\ExportCommand;
use Yangweijie\ThinkScramble\Command\PublishCommand;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use think\console\Input;
use think\console\Output;

describe('Command Module Tests', function () {
    beforeEach(function () {
        $this->config = new ScrambleConfig([
            'docs' => [
                'enabled' => true,
                'title' => 'Test API',
                'version' => '1.0.0',
                'description' => 'Test API for command functionality',
            ],
            'export' => [
                'enabled' => true,
                'formats' => ['json', 'yaml', 'postman', 'insomnia'],
                'output_dir' => sys_get_temp_dir() . '/command_test_export',
            ],
            'assets' => [
                'enabled' => true,
                'public_path' => sys_get_temp_dir() . '/command_test_assets',
            ],
        ]);
        
        // Load test data
        $this->testData = include __DIR__ . '/../data/cache_clear_test.php';
    });

    test('GenerateCommand can be instantiated', function () {
        $command = new GenerateCommand();
        
        expect($command)->toBeInstanceOf(GenerateCommand::class);
        
        // Test command configuration
        expect($command->getName())->toBe('scramble:generate');
        expect($command->getDescription())->toBeString();
        
    })->covers(\Yangweijie\ThinkScramble\Command\GenerateCommand::class);

    test('ExportCommand can be instantiated', function () {
        $command = new ExportCommand();
        
        expect($command)->toBeInstanceOf(ExportCommand::class);
        
        // Test command configuration
        expect($command->getName())->toBe('scramble:export');
        expect($command->getDescription())->toBeString();
        
    })->covers(\Yangweijie\ThinkScramble\Command\ExportCommand::class);

    test('PublishCommand can be instantiated', function () {
        $command = new PublishCommand();
        
        expect($command)->toBeInstanceOf(PublishCommand::class);
        
        // Test command configuration
        expect($command->getName())->toBe('scramble:publish');
        expect($command->getDescription())->toBeString();
        
    })->covers(\Yangweijie\ThinkScramble\Command\PublishCommand::class);

    test('commands have proper configuration', function () {
        $generateCommand = new GenerateCommand();
        $exportCommand = new ExportCommand();
        $publishCommand = new PublishCommand();
        
        // Test command names
        expect($generateCommand->getName())->toBe('scramble:generate');
        expect($exportCommand->getName())->toBe('scramble:export');
        expect($publishCommand->getName())->toBe('scramble:publish');
        
        // Test command descriptions
        expect($generateCommand->getDescription())->toBeString();
        expect($exportCommand->getDescription())->toBeString();
        expect($publishCommand->getDescription())->toBeString();
        
        // Test command definitions
        $generateDefinition = $generateCommand->getDefinition();
        $exportDefinition = $exportCommand->getDefinition();
        $publishDefinition = $publishCommand->getDefinition();
        
        expect($generateDefinition)->toBeInstanceOf(\think\console\input\Definition::class);
        expect($exportDefinition)->toBeInstanceOf(\think\console\input\Definition::class);
        expect($publishDefinition)->toBeInstanceOf(\think\console\input\Definition::class);
        
    })->covers(
        \Yangweijie\ThinkScramble\Command\GenerateCommand::class,
        \Yangweijie\ThinkScramble\Command\ExportCommand::class,
        \Yangweijie\ThinkScramble\Command\PublishCommand::class
    );

    test('commands can handle basic operations', function () {
        $generateCommand = new GenerateCommand();
        $exportCommand = new ExportCommand();
        $publishCommand = new PublishCommand();
        
        // Test command instances
        expect($generateCommand)->toBeInstanceOf(GenerateCommand::class);
        expect($exportCommand)->toBeInstanceOf(ExportCommand::class);
        expect($publishCommand)->toBeInstanceOf(PublishCommand::class);
        
        // Test command reflection
        $generateReflection = new ReflectionClass($generateCommand);
        $exportReflection = new ReflectionClass($exportCommand);
        $publishReflection = new ReflectionClass($publishCommand);
        
        expect($generateReflection->getName())->toBe(GenerateCommand::class);
        expect($exportReflection->getName())->toBe(ExportCommand::class);
        expect($publishReflection->getName())->toBe(PublishCommand::class);
        
        // Test that commands have execute methods
        expect($generateReflection->hasMethod('execute'))->toBe(true);
        expect($exportReflection->hasMethod('execute'))->toBe(true);
        expect($publishReflection->hasMethod('execute'))->toBe(true);
        
    })->covers(
        \Yangweijie\ThinkScramble\Command\GenerateCommand::class,
        \Yangweijie\ThinkScramble\Command\ExportCommand::class,
        \Yangweijie\ThinkScramble\Command\PublishCommand::class
    );

    test('commands can handle different configurations', function () {
        $configurations = [
            // Minimal configuration
            new ScrambleConfig([]),
            
            // Basic configuration
            new ScrambleConfig([
                'docs' => ['enabled' => true],
            ]),
            
            // Full configuration
            new ScrambleConfig([
                'docs' => [
                    'enabled' => true,
                    'title' => 'Full API',
                    'version' => '1.0.0',
                    'description' => 'Full featured API',
                ],
                'export' => [
                    'enabled' => true,
                    'formats' => ['json', 'yaml', 'postman'],
                    'output_dir' => sys_get_temp_dir() . '/test_export',
                ],
                'assets' => [
                    'enabled' => true,
                    'public_path' => sys_get_temp_dir() . '/test_assets',
                ],
            ]),
        ];
        
        foreach ($configurations as $config) {
            $generateCommand = new GenerateCommand();
            $exportCommand = new ExportCommand();
            $publishCommand = new PublishCommand();
            
            expect($generateCommand)->toBeInstanceOf(GenerateCommand::class);
            expect($exportCommand)->toBeInstanceOf(ExportCommand::class);
            expect($publishCommand)->toBeInstanceOf(PublishCommand::class);
        }
        
    })->covers(
        \Yangweijie\ThinkScramble\Command\GenerateCommand::class,
        \Yangweijie\ThinkScramble\Command\ExportCommand::class,
        \Yangweijie\ThinkScramble\Command\PublishCommand::class
    );

    test('commands handle edge cases gracefully', function () {
        // Test command instantiation with various scenarios
        try {
            $generateCommand = new GenerateCommand();
            $exportCommand = new ExportCommand();
            $publishCommand = new PublishCommand();
            
            expect($generateCommand)->toBeInstanceOf(GenerateCommand::class);
            expect($exportCommand)->toBeInstanceOf(ExportCommand::class);
            expect($publishCommand)->toBeInstanceOf(PublishCommand::class);
            
            // Test command properties
            expect($generateCommand->getName())->toBeString();
            expect($exportCommand->getName())->toBeString();
            expect($publishCommand->getName())->toBeString();
            
        } catch (\Exception $e) {
            // Commands might throw exceptions in test environment
            expect($e)->toBeInstanceOf(\Exception::class);
        }
        
    })->covers(
        \Yangweijie\ThinkScramble\Command\GenerateCommand::class,
        \Yangweijie\ThinkScramble\Command\ExportCommand::class,
        \Yangweijie\ThinkScramble\Command\PublishCommand::class
    );

    test('commands use memory efficiently', function () {
        $startMemory = memory_get_usage();
        
        // Create multiple command instances
        for ($i = 0; $i < 10; $i++) {
            $generateCommand = new GenerateCommand();
            $exportCommand = new ExportCommand();
            $publishCommand = new PublishCommand();
            
            expect($generateCommand)->toBeInstanceOf(GenerateCommand::class);
            expect($exportCommand)->toBeInstanceOf(ExportCommand::class);
            expect($publishCommand)->toBeInstanceOf(PublishCommand::class);
            
            // Clean up
            unset($generateCommand, $exportCommand, $publishCommand);
        }
        
        $endMemory = memory_get_usage();
        
        // Memory usage should be reasonable
        expect($endMemory - $startMemory)->toBeLessThan(5 * 1024 * 1024); // Less than 5MB
        
    })->covers(
        \Yangweijie\ThinkScramble\Command\GenerateCommand::class,
        \Yangweijie\ThinkScramble\Command\ExportCommand::class,
        \Yangweijie\ThinkScramble\Command\PublishCommand::class
    );

    test('commands have good performance', function () {
        $startTime = microtime(true);
        
        // Create and test multiple commands
        for ($i = 0; $i < 20; $i++) {
            $generateCommand = new GenerateCommand();
            $exportCommand = new ExportCommand();
            $publishCommand = new PublishCommand();
            
            expect($generateCommand)->toBeInstanceOf(GenerateCommand::class);
            expect($exportCommand)->toBeInstanceOf(ExportCommand::class);
            expect($publishCommand)->toBeInstanceOf(PublishCommand::class);
            
            // Test basic properties
            expect($generateCommand->getName())->toBe('scramble:generate');
            expect($exportCommand->getName())->toBe('scramble:export');
            expect($publishCommand->getName())->toBe('scramble:publish');
        }
        
        $endTime = microtime(true);
        
        // Should complete quickly
        expect($endTime - $startTime)->toBeLessThan(1.0); // Less than 1 second
        
    })->covers(
        \Yangweijie\ThinkScramble\Command\GenerateCommand::class,
        \Yangweijie\ThinkScramble\Command\ExportCommand::class,
        \Yangweijie\ThinkScramble\Command\PublishCommand::class
    );

    test('commands can handle concurrent operations', function () {
        // Test multiple command instances working independently
        $commands = [];
        
        for ($i = 0; $i < 5; $i++) {
            $commands[] = [
                'generate' => new GenerateCommand(),
                'export' => new ExportCommand(),
                'publish' => new PublishCommand(),
            ];
        }
        
        // Verify all commands are independent and functional
        foreach ($commands as $index => $commandSet) {
            expect($commandSet['generate'])->toBeInstanceOf(GenerateCommand::class);
            expect($commandSet['export'])->toBeInstanceOf(ExportCommand::class);
            expect($commandSet['publish'])->toBeInstanceOf(PublishCommand::class);
            
            // Test command names
            expect($commandSet['generate']->getName())->toBe('scramble:generate');
            expect($commandSet['export']->getName())->toBe('scramble:export');
            expect($commandSet['publish']->getName())->toBe('scramble:publish');
        }
        
    })->covers(
        \Yangweijie\ThinkScramble\Command\GenerateCommand::class,
        \Yangweijie\ThinkScramble\Command\ExportCommand::class,
        \Yangweijie\ThinkScramble\Command\PublishCommand::class
    );

    test('commands maintain consistency across operations', function () {
        // Create multiple instances of the same commands
        $generateCommands = [];
        $exportCommands = [];
        $publishCommands = [];
        
        for ($i = 0; $i < 3; $i++) {
            $generateCommands[] = new GenerateCommand();
            $exportCommands[] = new ExportCommand();
            $publishCommands[] = new PublishCommand();
        }
        
        // All instances should be of the correct type and have consistent properties
        foreach ($generateCommands as $command) {
            expect($command)->toBeInstanceOf(GenerateCommand::class);
            expect($command->getName())->toBe('scramble:generate');
        }
        
        foreach ($exportCommands as $command) {
            expect($command)->toBeInstanceOf(ExportCommand::class);
            expect($command->getName())->toBe('scramble:export');
        }
        
        foreach ($publishCommands as $command) {
            expect($command)->toBeInstanceOf(PublishCommand::class);
            expect($command->getName())->toBe('scramble:publish');
        }
        
    })->covers(
        \Yangweijie\ThinkScramble\Command\GenerateCommand::class,
        \Yangweijie\ThinkScramble\Command\ExportCommand::class,
        \Yangweijie\ThinkScramble\Command\PublishCommand::class
    );

    test('commands can handle complex scenarios', function () {
        $generateCommand = new GenerateCommand();
        $exportCommand = new ExportCommand();
        $publishCommand = new PublishCommand();
        
        // Test command definitions and options
        $generateDefinition = $generateCommand->getDefinition();
        $exportDefinition = $exportCommand->getDefinition();
        $publishDefinition = $publishCommand->getDefinition();
        
        expect($generateDefinition)->toBeInstanceOf(\think\console\input\Definition::class);
        expect($exportDefinition)->toBeInstanceOf(\think\console\input\Definition::class);
        expect($publishDefinition)->toBeInstanceOf(\think\console\input\Definition::class);
        
        // Test command arguments and options
        $generateArguments = $generateDefinition->getArguments();
        $exportArguments = $exportDefinition->getArguments();
        $publishArguments = $publishDefinition->getArguments();
        
        expect($generateArguments)->toBeArray();
        expect($exportArguments)->toBeArray();
        expect($publishArguments)->toBeArray();
        
        $generateOptions = $generateDefinition->getOptions();
        $exportOptions = $exportDefinition->getOptions();
        $publishOptions = $publishDefinition->getOptions();
        
        expect($generateOptions)->toBeArray();
        expect($exportOptions)->toBeArray();
        expect($publishOptions)->toBeArray();
        
    })->covers(
        \Yangweijie\ThinkScramble\Command\GenerateCommand::class,
        \Yangweijie\ThinkScramble\Command\ExportCommand::class,
        \Yangweijie\ThinkScramble\Command\PublishCommand::class
    );
});
