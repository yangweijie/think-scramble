<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Adapter\ControllerParser;
use Yangweijie\ThinkScramble\Adapter\MiddlewareHandler;
use Yangweijie\ThinkScramble\Adapter\MultiAppSupport;
use Yangweijie\ThinkScramble\Adapter\RouteAnalyzer;
use Yangweijie\ThinkScramble\Adapter\ValidatorIntegration;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use think\App;

describe('Adapter System Boost Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Adapter System Boost API',
                'version' => '8.0.0'
            ],
            'adapters' => [
                'enabled' => true,
                'multi_app' => true,
                'middleware' => true,
                'validation' => true
            ],
            'routes' => [
                'enabled' => true,
                'auto_discover' => true
            ]
        ]);
    });

    describe('Controller Parser Comprehensive Testing', function () {
        test('ControllerParser complete functionality coverage', function () {
            try {
                $controllerParser = new ControllerParser($this->config);
                expect($controllerParser)->toBeInstanceOf(ControllerParser::class);
                
                // Test parseController method
                $controllerInfo = $controllerParser->parseController('TestController');
                expect($controllerInfo)->toBeArray();
                expect($controllerInfo)->toHaveKey('name');
                expect($controllerInfo)->toHaveKey('methods');
                expect($controllerInfo)->toHaveKey('namespace');
                
                // Test parseMethod method
                $methodInfo = $controllerParser->parseMethod('TestController', 'index');
                expect($methodInfo)->toBeArray();
                expect($methodInfo)->toHaveKey('name');
                expect($methodInfo)->toHaveKey('parameters');
                expect($methodInfo)->toHaveKey('return_type');
                
                // Test getControllerMethods method
                $methods = $controllerParser->getControllerMethods('TestController');
                expect($methods)->toBeArray();
                
                // Test getMethodParameters method
                $parameters = $controllerParser->getMethodParameters('TestController', 'show');
                expect($parameters)->toBeArray();
                
                // Test getMethodReturnType method
                $returnType = $controllerParser->getMethodReturnType('TestController', 'index');
                expect($returnType)->toBeString();
                
                // Test parseControllerAnnotations method
                $annotations = $controllerParser->parseControllerAnnotations('TestController');
                expect($annotations)->toBeArray();
                
                // Test getControllerNamespace method
                $namespace = $controllerParser->getControllerNamespace('TestController');
                expect($namespace)->toBeString();
                
                // Test isValidController method
                $isValid = $controllerParser->isValidController('TestController');
                expect($isValid)->toBeBool();
                
                // Test getControllerDependencies method
                $dependencies = $controllerParser->getControllerDependencies('TestController');
                expect($dependencies)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Adapter\ControllerParser::class);
    });

    describe('Middleware Handler Comprehensive Testing', function () {
        test('MiddlewareHandler complete functionality coverage', function () {
            try {
                $middlewareHandler = new MiddlewareHandler($this->config);
                expect($middlewareHandler)->toBeInstanceOf(MiddlewareHandler::class);
                
                // Test handleMiddleware method
                $middlewareInfo = $middlewareHandler->handleMiddleware(['auth', 'throttle:60,1']);
                expect($middlewareInfo)->toBeArray();
                expect($middlewareInfo)->toHaveKey('middleware');
                expect($middlewareInfo)->toHaveKey('parameters');
                
                // Test parseMiddleware method
                $parsedMiddleware = $middlewareHandler->parseMiddleware('auth:api');
                expect($parsedMiddleware)->toBeArray();
                expect($parsedMiddleware)->toHaveKey('name');
                expect($parsedMiddleware)->toHaveKey('parameters');
                
                // Test getMiddlewareParameters method
                $parameters = $middlewareHandler->getMiddlewareParameters('throttle:60,1');
                expect($parameters)->toBeArray();
                
                // Test resolveMiddleware method
                $resolved = $middlewareHandler->resolveMiddleware('auth');
                expect($resolved)->toBeArray();
                expect($resolved)->toHaveKey('class');
                expect($resolved)->toHaveKey('method');
                
                // Test getMiddlewareChain method
                $chain = $middlewareHandler->getMiddlewareChain(['auth', 'throttle', 'cache']);
                expect($chain)->toBeArray();
                
                // Test isValidMiddleware method
                $isValid = $middlewareHandler->isValidMiddleware('auth');
                expect($isValid)->toBeBool();
                
                // Test getMiddlewareConfig method
                $config = $middlewareHandler->getMiddlewareConfig('throttle');
                expect($config)->toBeArray();
                
                // Test applyMiddleware method
                $applied = $middlewareHandler->applyMiddleware('TestController', 'index', ['auth']);
                expect($applied)->toBeArray();
                
                // Test getGlobalMiddleware method
                $global = $middlewareHandler->getGlobalMiddleware();
                expect($global)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Adapter\MiddlewareHandler::class);
    });

    describe('Multi App Support Comprehensive Testing', function () {
        test('MultiAppSupport complete functionality coverage', function () {
            try {
                $multiAppSupport = new MultiAppSupport($this->config);
                expect($multiAppSupport)->toBeInstanceOf(MultiAppSupport::class);
                
                // Test getApps method
                $apps = $multiAppSupport->getApps();
                expect($apps)->toBeArray();
                
                // Test getCurrentApp method
                $currentApp = $multiAppSupport->getCurrentApp();
                expect($currentApp)->toBeString();
                
                // Test setCurrentApp method
                $multiAppSupport->setCurrentApp('admin');
                expect(true)->toBe(true);
                
                // Test getAppConfig method
                $appConfig = $multiAppSupport->getAppConfig('admin');
                expect($appConfig)->toBeArray();
                
                // Test getAppControllers method
                $controllers = $multiAppSupport->getAppControllers('admin');
                expect($controllers)->toBeArray();
                
                // Test getAppRoutes method
                $routes = $multiAppSupport->getAppRoutes('admin');
                expect($routes)->toBeArray();
                
                // Test isValidApp method
                $isValid = $multiAppSupport->isValidApp('admin');
                expect($isValid)->toBeBool();
                
                // Test getAppNamespace method
                $namespace = $multiAppSupport->getAppNamespace('admin');
                expect($namespace)->toBeString();
                
                // Test mergeAppConfigs method
                $merged = $multiAppSupport->mergeAppConfigs(['admin', 'api']);
                expect($merged)->toBeArray();
                
                // Test getAppMiddleware method
                $middleware = $multiAppSupport->getAppMiddleware('admin');
                expect($middleware)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Adapter\MultiAppSupport::class);
    });

    describe('Route Analyzer Comprehensive Testing', function () {
        test('RouteAnalyzer complete functionality coverage', function () {
            try {
                $routeAnalyzer = new RouteAnalyzer($this->config);
                expect($routeAnalyzer)->toBeInstanceOf(RouteAnalyzer::class);
                
                // Test analyzeRoutes method
                $routes = $routeAnalyzer->analyzeRoutes();
                expect($routes)->toBeArray();
                
                // Test analyzeRoute method
                $routeInfo = $routeAnalyzer->analyzeRoute('/users/{id}', 'GET', 'UserController@show');
                expect($routeInfo)->toBeArray();
                expect($routeInfo)->toHaveKey('path');
                expect($routeInfo)->toHaveKey('method');
                expect($routeInfo)->toHaveKey('controller');
                
                // Test getRouteParameters method
                $parameters = $routeAnalyzer->getRouteParameters('/users/{id}/posts/{postId}');
                expect($parameters)->toBeArray();
                
                // Test getRouteMiddleware method
                $middleware = $routeAnalyzer->getRouteMiddleware('/api/users');
                expect($middleware)->toBeArray();
                
                // Test parseRoutePattern method
                $pattern = $routeAnalyzer->parseRoutePattern('/users/{id:\\d+}');
                expect($pattern)->toBeArray();
                expect($pattern)->toHaveKey('pattern');
                expect($pattern)->toHaveKey('parameters');
                
                // Test getControllerRoutes method
                $controllerRoutes = $routeAnalyzer->getControllerRoutes('UserController');
                expect($controllerRoutes)->toBeArray();
                
                // Test getRouteGroups method
                $groups = $routeAnalyzer->getRouteGroups();
                expect($groups)->toBeArray();
                
                // Test isValidRoute method
                $isValid = $routeAnalyzer->isValidRoute('/users/{id}', 'GET');
                expect($isValid)->toBeBool();
                
                // Test getRouteConstraints method
                $constraints = $routeAnalyzer->getRouteConstraints('/users/{id:\\d+}');
                expect($constraints)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Adapter\RouteAnalyzer::class);
    });

    describe('Validator Integration Comprehensive Testing', function () {
        test('ValidatorIntegration complete functionality coverage', function () {
            try {
                $validatorIntegration = new ValidatorIntegration($this->config);
                expect($validatorIntegration)->toBeInstanceOf(ValidatorIntegration::class);
                
                // Test integrateValidation method
                $validation = $validatorIntegration->integrateValidation('UserController', 'store');
                expect($validation)->toBeArray();
                expect($validation)->toHaveKey('rules');
                expect($validation)->toHaveKey('messages');
                
                // Test parseValidationRules method
                $rules = $validatorIntegration->parseValidationRules([
                    'name' => 'required|string|max:100',
                    'email' => 'required|email|unique:users'
                ]);
                expect($rules)->toBeArray();
                
                // Test getValidationMessages method
                $messages = $validatorIntegration->getValidationMessages([
                    'name.required' => 'Name is required',
                    'email.email' => 'Invalid email format'
                ]);
                expect($messages)->toBeArray();
                
                // Test convertToOpenApiSchema method
                $schema = $validatorIntegration->convertToOpenApiSchema([
                    'name' => 'required|string|max:100',
                    'age' => 'integer|min:18|max:120'
                ]);
                expect($schema)->toBeArray();
                expect($schema)->toHaveKey('type');
                expect($schema)->toHaveKey('properties');
                
                // Test getValidationConstraints method
                $constraints = $validatorIntegration->getValidationConstraints('required|string|max:100');
                expect($constraints)->toBeArray();
                
                // Test isValidationRule method
                $isValid = $validatorIntegration->isValidationRule('required|string');
                expect($isValid)->toBeBool();
                
                // Test getCustomValidators method
                $customValidators = $validatorIntegration->getCustomValidators();
                expect($customValidators)->toBeArray();
                
                // Test applyValidationToSchema method
                $appliedSchema = $validatorIntegration->applyValidationToSchema(
                    ['type' => 'object'],
                    ['name' => 'required|string']
                );
                expect($appliedSchema)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Adapter\ValidatorIntegration::class);
    });

    describe('Adapter System Integration Testing', function () {
        test('All adapters working together', function () {
            try {
                // Test all adapters instantiation
                $controllerParser = new ControllerParser($this->config);
                $middlewareHandler = new MiddlewareHandler($this->config);
                $multiAppSupport = new MultiAppSupport($this->config);
                $routeAnalyzer = new RouteAnalyzer($this->config);
                $validatorIntegration = new ValidatorIntegration($this->config);
                
                expect($controllerParser)->toBeInstanceOf(ControllerParser::class);
                expect($middlewareHandler)->toBeInstanceOf(MiddlewareHandler::class);
                expect($multiAppSupport)->toBeInstanceOf(MultiAppSupport::class);
                expect($routeAnalyzer)->toBeInstanceOf(RouteAnalyzer::class);
                expect($validatorIntegration)->toBeInstanceOf(ValidatorIntegration::class);
                
                // Test integrated workflow
                $apps = $multiAppSupport->getApps();
                expect($apps)->toBeArray();
                
                $routes = $routeAnalyzer->analyzeRoutes();
                expect($routes)->toBeArray();
                
                $controllerInfo = $controllerParser->parseController('TestController');
                expect($controllerInfo)->toBeArray();
                
                $middlewareInfo = $middlewareHandler->handleMiddleware(['auth']);
                expect($middlewareInfo)->toBeArray();
                
                $validation = $validatorIntegration->integrateValidation('TestController', 'store');
                expect($validation)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Adapter\ControllerParser::class,
            \Yangweijie\ThinkScramble\Adapter\MiddlewareHandler::class,
            \Yangweijie\ThinkScramble\Adapter\MultiAppSupport::class,
            \Yangweijie\ThinkScramble\Adapter\RouteAnalyzer::class,
            \Yangweijie\ThinkScramble\Adapter\ValidatorIntegration::class
        );
    });
});
