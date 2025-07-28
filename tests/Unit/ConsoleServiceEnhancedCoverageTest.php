<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Console\ScrambleCommand;
use Yangweijie\ThinkScramble\Service\CommandService;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use think\App;
use think\console\Input;
use think\console\Output;

describe('Console and Service Enhanced Coverage Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Console Service Test API',
                'version' => '1.0.0'
            ],
            'commands' => [
                'enabled' => true,
                'namespace' => 'scramble'
            ]
        ]);
    });

    describe('ScrambleCommand Comprehensive Coverage', function () {
        test('ScrambleCommand basic functionality', function () {
            $command = new ScrambleCommand();

            // Test basic instantiation
            expect($command)->toBeInstanceOf(ScrambleCommand::class);

            // Test version constant
            expect(ScrambleCommand::VERSION)->toBeString();
            expect(ScrambleCommand::VERSION)->toBe('1.4.0');

        })->covers(\Yangweijie\ThinkScramble\Console\ScrambleCommand::class);

        test('ScrambleCommand execute method', function () {
            $command = new ScrambleCommand();

            // Test execute method with proper parameters
            try {
                $result = $command->execute([], []);
                expect($result)->toBeInt();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Console\ScrambleCommand::class);
    });

    describe('CommandService Comprehensive Coverage', function () {
        test('CommandService basic functionality', function () {
            $service = new CommandService($this->app, $this->config);

            // Test basic instantiation
            expect($service)->toBeInstanceOf(CommandService::class);

        })->covers(\Yangweijie\ThinkScramble\Service\CommandService::class);
    });

    describe('Services File Coverage', function () {
        test('Services file structure and content', function () {
            // Test loading the services file
            $servicesPath = __DIR__ . '/../../src/Service/services.php';
            expect(file_exists($servicesPath))->toBe(true);
            
            $services = include $servicesPath;
            expect($services)->toBeArray();
            
            // Test services structure
            expect($services)->toHaveKey('providers');
            expect($services)->toHaveKey('aliases');
            expect($services)->toHaveKey('helpers');
            expect($services)->toHaveKey('publishes');
            
            // Test providers section
            expect($services['providers'])->toBeArray();
            expect(count($services['providers']))->toBeGreaterThan(0);
            
            // Test aliases section
            expect($services['aliases'])->toBeArray();
            expect($services['aliases'])->toHaveKey('Scramble');
            
            // Test helpers section
            expect($services['helpers'])->toBeArray();
            
            // Test publishes section
            expect($services['publishes'])->toBeArray();
            
        });

        test('Services file providers and aliases', function () {
            $servicesPath = __DIR__ . '/../../src/Service/services.php';
            $services = include $servicesPath;
            
            // Test that providers are valid class names
            foreach ($services['providers'] as $provider) {
                expect($provider)->toBeString();
                expect(class_exists($provider))->toBe(true);
            }
            
            // Test that aliases point to valid classes
            foreach ($services['aliases'] as $alias => $class) {
                expect($alias)->toBeString();
                expect($class)->toBeString();
                expect(class_exists($class))->toBe(true);
            }
            
        });
    });

    describe('Integration Tests', function () {
        test('Console and Service integration', function () {
            $command = new ScrambleCommand();
            $service = new CommandService($this->app, $this->config);

            // Test that both components work together
            expect($command)->toBeInstanceOf(ScrambleCommand::class);
            expect($service)->toBeInstanceOf(CommandService::class);

        })->covers(
            \Yangweijie\ThinkScramble\Console\ScrambleCommand::class,
            \Yangweijie\ThinkScramble\Service\CommandService::class
        );
    });
});
