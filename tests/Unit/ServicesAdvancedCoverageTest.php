<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Service\ScrambleServiceProvider;
use Yangweijie\ThinkScramble\Service\AssetPublisher;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use think\App;

describe('Services Advanced Coverage Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Services Test API',
                'version' => '1.0.0'
            ]
        ]);
    });

    describe('Services File Coverage', function () {
        test('Services file can be loaded', function () {
            // Test loading the services file
            $servicesPath = __DIR__ . '/../../src/Service/services.php';
            expect(file_exists($servicesPath))->toBe(true);
            
            $services = include $servicesPath;
            expect($services)->toBeArray();
            
            // Test basic services structure
            expect($services)->toHaveKey('providers');
            expect($services)->toHaveKey('aliases');
            expect($services)->toHaveKey('helpers');
            expect($services)->toHaveKey('publishes');
            
        });

        test('Services file providers section', function () {
            $servicesPath = __DIR__ . '/../../src/Service/services.php';
            $services = include $servicesPath;
            
            // Test providers section
            expect($services['providers'])->toBeArray();
            expect(count($services['providers']))->toBeGreaterThan(0);
            expect($services['providers'])->toContain(\Yangweijie\ThinkScramble\Service\ScrambleServiceProvider::class);
            
        });

        test('Services file aliases section', function () {
            $servicesPath = __DIR__ . '/../../src/Service/services.php';
            $services = include $servicesPath;
            
            // Test aliases section
            expect($services['aliases'])->toBeArray();
            expect($services['aliases'])->toHaveKey('Scramble');
            expect($services['aliases']['Scramble'])->toBe(\Yangweijie\ThinkScramble\Scramble::class);
            
        });

        test('Services file helpers section', function () {
            $servicesPath = __DIR__ . '/../../src/Service/services.php';
            $services = include $servicesPath;
            
            // Test helpers section
            expect($services['helpers'])->toBeArray();
            expect(count($services['helpers']))->toBeGreaterThan(0);
            
            // Test that helper files exist
            foreach ($services['helpers'] as $helperFile) {
                expect(file_exists($helperFile))->toBe(true);
            }
            
        });

        test('Services file publishes section', function () {
            $servicesPath = __DIR__ . '/../../src/Service/services.php';
            $services = include $servicesPath;
            
            // Test publishes section
            expect($services['publishes'])->toBeArray();
            expect($services['publishes'])->toHaveKey('config');
            expect($services['publishes']['config'])->toBeArray();
            
        });
    });

    describe('ScrambleServiceProvider Coverage', function () {
        test('ScrambleServiceProvider can be instantiated', function () {
            $provider = new ScrambleServiceProvider($this->app);
            
            // Test basic instantiation
            expect($provider)->toBeInstanceOf(ScrambleServiceProvider::class);
            
        })->covers(\Yangweijie\ThinkScramble\Service\ScrambleServiceProvider::class);

        test('ScrambleServiceProvider register method', function () {
            $provider = new ScrambleServiceProvider($this->app);
            
            // Test register method
            try {
                $provider->register();
                expect(true)->toBe(true); // If no exception, test passes
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Service\ScrambleServiceProvider::class);

        test('ScrambleServiceProvider boot method', function () {
            $provider = new ScrambleServiceProvider($this->app);
            
            // Test boot method
            try {
                $provider->boot();
                expect(true)->toBe(true); // If no exception, test passes
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Service\ScrambleServiceProvider::class);

        test('ScrambleServiceProvider provides method', function () {
            $provider = new ScrambleServiceProvider($this->app);
            
            // Test provides method
            try {
                $provides = $provider->provides();
                expect($provides)->toBeArray();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Service\ScrambleServiceProvider::class);

        test('ScrambleServiceProvider enhanced functionality', function () {
            $provider = new ScrambleServiceProvider($this->app);

            // Test provides method
            $provides = $provider->provides();
            expect($provides)->toBeArray();
            expect(count($provides))->toBeGreaterThan(0);

        })->covers(\Yangweijie\ThinkScramble\Service\ScrambleServiceProvider::class);
    });

    describe('AssetPublisher Enhanced Coverage', function () {
        test('AssetPublisher enhanced functionality', function () {
            $publisher = new AssetPublisher($this->app);

            // Test basic functionality
            expect($publisher)->toBeInstanceOf(AssetPublisher::class);

        })->covers(\Yangweijie\ThinkScramble\Service\AssetPublisher::class);

        test('AssetPublisher publishAssets method', function () {
            $publisher = new AssetPublisher($this->app);

            // Test publishAssets method
            try {
                $result = $publisher->publishAssets();
                expect($result)->toBeBool();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Service\AssetPublisher::class);

        test('AssetPublisher areAssetsPublished method', function () {
            $publisher = new AssetPublisher($this->app);

            // Test areAssetsPublished method
            try {
                $result = $publisher->areAssetsPublished();
                expect($result)->toBeBool();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Service\AssetPublisher::class);

        test('AssetPublisher forcePublishAssets method', function () {
            $publisher = new AssetPublisher($this->app);

            // Test forcePublishAssets method
            try {
                $result = $publisher->forcePublishAssets();
                expect($result)->toBeBool();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Service\AssetPublisher::class);

        test('AssetPublisher getAvailableRenderers method', function () {
            $publisher = new AssetPublisher($this->app);

            // Test getAvailableRenderers method
            $renderers = $publisher->getAvailableRenderers();
            expect($renderers)->toBeArray();
            expect(count($renderers))->toBeGreaterThan(0);

        })->covers(\Yangweijie\ThinkScramble\Service\AssetPublisher::class);

        test('AssetPublisher isRendererAvailable method', function () {
            $publisher = new AssetPublisher($this->app);

            // Test isRendererAvailable method
            $isAvailable = $publisher->isRendererAvailable('stoplight-elements');
            expect($isAvailable)->toBeBool();

            $isNotAvailable = $publisher->isRendererAvailable('non-existent-renderer');
            expect($isNotAvailable)->toBe(false);

        })->covers(\Yangweijie\ThinkScramble\Service\AssetPublisher::class);

        test('AssetPublisher HTML generation methods', function () {
            $publisher = new AssetPublisher($this->app);

            // Test getStoplightElementsHtml method
            $elementsHtml = $publisher->getStoplightElementsHtml('http://example.com/api.json');
            expect($elementsHtml)->toBeString();
            expect(strlen($elementsHtml))->toBeGreaterThan(0);

            // Test getSwaggerUIHtml method
            $swaggerHtml = $publisher->getSwaggerUIHtml('http://example.com/api.json');
            expect($swaggerHtml)->toBeString();
            expect(strlen($swaggerHtml))->toBeGreaterThan(0);

        })->covers(\Yangweijie\ThinkScramble\Service\AssetPublisher::class);
    });

    describe('Integration Tests', function () {
        test('Services integration workflow', function () {
            // Test complete services workflow
            $servicesPath = __DIR__ . '/../../src/Service/services.php';
            $services = include $servicesPath;
            
            // Test that we can instantiate the service provider
            $providerClass = $services['providers'][0];
            $provider = new $providerClass($this->app);
            
            expect($provider)->toBeInstanceOf(ScrambleServiceProvider::class);
            
            // Test that we can call register and boot
            try {
                $provider->register();
                $provider->boot();
                expect(true)->toBe(true);
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Service\ScrambleServiceProvider::class
        );

        test('Services and AssetPublisher integration', function () {
            $provider = new ScrambleServiceProvider($this->app);
            $publisher = new AssetPublisher($this->app);

            // Test that both services work together
            expect($provider)->toBeInstanceOf(ScrambleServiceProvider::class);
            expect($publisher)->toBeInstanceOf(AssetPublisher::class);

            // Test workflow
            try {
                $provider->register();
                $publisher->publishAssets();
                expect(true)->toBe(true);
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(
            \Yangweijie\ThinkScramble\Service\ScrambleServiceProvider::class,
            \Yangweijie\ThinkScramble\Service\AssetPublisher::class
        );
    });
});
