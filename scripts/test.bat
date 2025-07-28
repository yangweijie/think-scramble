@echo off
setlocal enabledelayedexpansion

REM ThinkScramble 测试脚本 (Windows)

echo 🧪 ThinkScramble Test Suite
echo ==========================

REM 默认参数
set COVERAGE=false
set FILTER=
set TESTSUITE=
set PARALLEL=false
set VERBOSE=false
set STOP_ON_FAILURE=false
set GENERATE_REPORT=false

REM 解析命令行参数
:parse_args
if "%1"=="" goto end_parse
if "%1"=="--coverage" (
    set COVERAGE=true
    shift
    goto parse_args
)
if "%1"=="--filter" (
    set FILTER=%2
    shift
    shift
    goto parse_args
)
if "%1"=="--testsuite" (
    set TESTSUITE=%2
    shift
    shift
    goto parse_args
)
if "%1"=="--parallel" (
    set PARALLEL=true
    shift
    goto parse_args
)
if "%1"=="--verbose" (
    set VERBOSE=true
    shift
    goto parse_args
)
if "%1"=="--stop-on-failure" (
    set STOP_ON_FAILURE=true
    shift
    goto parse_args
)
if "%1"=="--report" (
    set GENERATE_REPORT=true
    shift
    goto parse_args
)
if "%1"=="--help" (
    echo Usage: %0 [options]
    echo.
    echo Options:
    echo   --coverage          Generate code coverage report
    echo   --filter PATTERN    Run only tests matching pattern
    echo   --testsuite SUITE   Run specific test suite (Unit^|Feature^|Integration^)
    echo   --parallel          Run tests in parallel
    echo   --verbose           Verbose output
    echo   --stop-on-failure   Stop on first failure
    echo   --report            Generate detailed test report
    echo   --help              Show this help message
    echo.
    echo Examples:
    echo   %0 --coverage                    # Run all tests with coverage
    echo   %0 --testsuite Unit              # Run only unit tests
    echo   %0 --filter ControllerTest       # Run tests matching pattern
    echo   %0 --parallel --coverage         # Run tests in parallel with coverage
    exit /b 0
)
echo Unknown option: %1
exit /b 1

:end_parse

REM 检查依赖
echo [STEP] Checking dependencies...

where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] PHP is not installed
    exit /b 1
)

where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Composer is not installed
    exit /b 1
)

if not exist "vendor" (
    echo [WARNING] Vendor directory not found, installing dependencies...
    composer install
)

if not exist "vendor\bin\phpunit.bat" (
    echo [ERROR] PHPUnit not found, please run: composer install
    exit /b 1
)

if not exist "vendor\bin\pest.bat" (
    echo [ERROR] Pest not found, please run: composer install
    exit /b 1
)

echo [SUCCESS] Dependencies check passed

REM 设置覆盖率目录
if "%COVERAGE%"=="true" (
    echo [STEP] Setting up coverage directories...
    if not exist "coverage\html" mkdir coverage\html
    if not exist "coverage\xml" mkdir coverage\xml
    echo [SUCCESS] Coverage directories created
)

REM 运行代码质量检查
echo [STEP] Running code quality checks...

echo [INFO] Checking PHP syntax...
for /r src %%f in (*.php) do (
    php -l "%%f" >nul 2>nul
    if !errorlevel! neq 0 (
        echo [ERROR] Syntax error in %%f
        exit /b 1
    )
)
echo [SUCCESS] PHP syntax check passed

REM PHPStan 静态分析
if exist "vendor\bin\phpstan.bat" (
    echo [INFO] Running PHPStan analysis...
    vendor\bin\phpstan.bat analyse --no-progress --quiet
    if !errorlevel! neq 0 (
        echo [WARNING] PHPStan analysis found issues
    )
)

REM PHP CodeSniffer
if exist "vendor\bin\phpcs.bat" (
    echo [INFO] Running PHP CodeSniffer...
    vendor\bin\phpcs.bat --standard=PSR12 src\ --quiet
    if !errorlevel! neq 0 (
        echo [WARNING] Code style issues found
    )
)

echo [SUCCESS] Quality checks completed

REM 构建测试命令
set TEST_CMD=vendor\bin\pest.bat

REM 添加覆盖率选项
if "%COVERAGE%"=="true" (
    set TEST_CMD=!TEST_CMD! --coverage-html=coverage\html --coverage-clover=coverage\clover.xml --coverage-xml=coverage\xml
    
    REM 检查 Xdebug
    php -m | findstr /i xdebug >nul
    if !errorlevel! equ 0 (
        set XDEBUG_MODE=coverage
        echo [INFO] Using Xdebug for coverage
    ) else (
        php -m | findstr /i pcov >nul
        if !errorlevel! equ 0 (
            echo [INFO] Using PCOV for coverage
        ) else (
            echo [WARNING] No coverage driver found (Xdebug or PCOV^)
        )
    )
)

REM 添加过滤器
if not "%FILTER%"=="" (
    set TEST_CMD=!TEST_CMD! --filter="!FILTER!"
)

REM 添加测试套件
if not "%TESTSUITE%"=="" (
    set TEST_CMD=!TEST_CMD! --testsuite="!TESTSUITE!"
)

REM 添加并行选项
if "%PARALLEL%"=="true" (
    set TEST_CMD=!TEST_CMD! --parallel
)

REM 添加详细输出 (Pest 使用 -v)
if "%VERBOSE%"=="true" (
    set TEST_CMD=!TEST_CMD! -v
)

REM 添加失败时停止
if "%STOP_ON_FAILURE%"=="true" (
    set TEST_CMD=!TEST_CMD! --stop-on-failure
)

REM 运行测试
echo [STEP] Running tests...
echo [INFO] Test command: !TEST_CMD!

set START_TIME=%time%
!TEST_CMD!
set TEST_RESULT=%errorlevel%
set END_TIME=%time%

if %TEST_RESULT% equ 0 (
    echo [SUCCESS] Tests completed successfully
) else (
    echo [ERROR] Tests failed
)

REM 生成测试报告
if "%GENERATE_REPORT%"=="true" (
    echo [STEP] Generating test report...
    
    echo ThinkScramble Test Report > coverage\test-report.txt
    echo ======================== >> coverage\test-report.txt
    echo Generated: %date% %time% >> coverage\test-report.txt
    echo. >> coverage\test-report.txt
    echo Test Results: >> coverage\test-report.txt
    echo ============= >> coverage\test-report.txt
    echo Filter: %FILTER% >> coverage\test-report.txt
    echo Test Suite: %TESTSUITE% >> coverage\test-report.txt
    echo Parallel: %PARALLEL% >> coverage\test-report.txt
    echo Coverage: %COVERAGE% >> coverage\test-report.txt
    
    echo [SUCCESS] Test report generated: coverage\test-report.txt
)

REM 显示覆盖率摘要
if "%COVERAGE%"=="true" (
    echo [STEP] Coverage Summary
    
    if exist "coverage\coverage.txt" (
        type coverage\coverage.txt
    ) else if exist "coverage\clover.xml" (
        echo [INFO] Coverage report generated in coverage\html\index.html
    )
    
    echo.
    echo [INFO] Coverage reports:
    echo   📊 HTML Report: coverage\html\index.html
    echo   📄 Clover XML: coverage\clover.xml
    echo   📋 Text Report: coverage\coverage.txt
)

REM 清理
echo [INFO] Cleaning up...
if exist "tests\data" (
    del /q tests\data\test_* 2>nul
)

REM 结果
if %TEST_RESULT% equ 0 (
    echo [SUCCESS] 🎉 All tests passed!
    exit /b 0
) else (
    echo [ERROR] 💥 Tests failed!
    exit /b 1
)
