#!/bin/bash

# ThinkScramble 测试脚本

set -e

echo "🧪 ThinkScramble Test Suite"
echo "=========================="

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# 函数定义
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_step() {
    echo -e "${PURPLE}[STEP]${NC} $1"
}

# 解析命令行参数
COVERAGE=false
FILTER=""
TESTSUITE=""
PARALLEL=false
VERBOSE=false
STOP_ON_FAILURE=false
GENERATE_REPORT=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --coverage)
            COVERAGE=true
            shift
            ;;
        --filter)
            FILTER="$2"
            shift 2
            ;;
        --testsuite)
            TESTSUITE="$2"
            shift 2
            ;;
        --parallel)
            PARALLEL=true
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        --stop-on-failure)
            STOP_ON_FAILURE=true
            shift
            ;;
        --report)
            GENERATE_REPORT=true
            shift
            ;;
        --help)
            echo "Usage: $0 [options]"
            echo ""
            echo "Options:"
            echo "  --coverage          Generate code coverage report"
            echo "  --filter PATTERN    Run only tests matching pattern"
            echo "  --testsuite SUITE   Run specific test suite (Unit|Feature|Integration)"
            echo "  --parallel          Run tests in parallel"
            echo "  --verbose           Verbose output"
            echo "  --stop-on-failure   Stop on first failure"
            echo "  --report            Generate detailed test report"
            echo "  --help              Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0 --coverage                    # Run all tests with coverage"
            echo "  $0 --testsuite Unit              # Run only unit tests"
            echo "  $0 --filter ControllerTest       # Run tests matching pattern"
            echo "  $0 --parallel --coverage         # Run tests in parallel with coverage"
            exit 0
            ;;
        *)
            log_error "Unknown option: $1"
            exit 1
            ;;
    esac
done

# 检查依赖
check_dependencies() {
    log_step "Checking dependencies..."
    
    # 检查 PHP
    if ! command -v php &> /dev/null; then
        log_error "PHP is not installed"
        exit 1
    fi
    
    # 检查 Composer
    if ! command -v composer &> /dev/null; then
        log_error "Composer is not installed"
        exit 1
    fi
    
    # 检查 vendor 目录
    if [ ! -d "vendor" ]; then
        log_warning "Vendor directory not found, installing dependencies..."
        composer install
    fi
    
    # 检查 PHPUnit
    if [ ! -f "vendor/bin/phpunit" ]; then
        log_error "PHPUnit not found, please run: composer install"
        exit 1
    fi
    
    # 检查 Pest
    if [ ! -f "vendor/bin/pest" ]; then
        log_error "Pest not found, please run: composer install"
        exit 1
    fi
    
    log_success "Dependencies check passed"
}

# 创建覆盖率目录
setup_coverage() {
    if [ "$COVERAGE" = true ]; then
        log_step "Setting up coverage directories..."
        mkdir -p coverage/html
        mkdir -p coverage/xml
        log_success "Coverage directories created"
    fi
}

# 运行代码质量检查
run_quality_checks() {
    log_step "Running code quality checks..."
    
    # PHP 语法检查
    log_info "Checking PHP syntax..."
    find src -name "*.php" -exec php -l {} \; > /dev/null
    log_success "PHP syntax check passed"
    
    # PHPStan 静态分析
    if [ -f "vendor/bin/phpstan" ]; then
        log_info "Running PHPStan analysis..."
        vendor/bin/phpstan analyse --no-progress --quiet || {
            log_warning "PHPStan analysis found issues"
        }
    fi
    
    # PHP CodeSniffer
    if [ -f "vendor/bin/phpcs" ]; then
        log_info "Running PHP CodeSniffer..."
        vendor/bin/phpcs --standard=PSR12 src/ --quiet || {
            log_warning "Code style issues found"
        }
    fi
    
    log_success "Quality checks completed"
}

# 设置覆盖率环境
setup_coverage_env() {
    if [ "$COVERAGE" = true ]; then
        # 检查覆盖率驱动
        if php -m | grep -q xdebug; then
            export XDEBUG_MODE=coverage
            log_info "Using Xdebug for coverage"
        elif php -m | grep -q pcov; then
            log_info "Using PCOV for coverage"
        else
            log_warning "No coverage driver found, coverage may not work properly"
        fi
    fi
}

# 构建测试命令
build_test_command() {
    local cmd="vendor/bin/pest"

    # 添加覆盖率选项
    if [ "$COVERAGE" = true ]; then
        cmd="$cmd --coverage-html=coverage/html --coverage-clover=coverage/clover.xml --coverage-xml=coverage/xml"
    fi
    
    # 添加过滤器
    if [ -n "$FILTER" ]; then
        cmd="$cmd --filter=\"$FILTER\""
    fi
    
    # 添加测试套件
    if [ -n "$TESTSUITE" ]; then
        cmd="$cmd --testsuite=\"$TESTSUITE\""
    fi
    
    # 添加并行选项
    if [ "$PARALLEL" = true ]; then
        cmd="$cmd --parallel"
    fi
    
    # 添加详细输出 (Pest 使用 -v)
    if [ "$VERBOSE" = true ]; then
        cmd="$cmd -v"
    fi
    
    # 添加失败时停止
    if [ "$STOP_ON_FAILURE" = true ]; then
        cmd="$cmd --stop-on-failure"
    fi
    
    echo "$cmd"
}

# 运行测试
run_tests() {
    log_step "Running tests..."

    # 设置覆盖率环境
    setup_coverage_env

    local start_time=$(date +%s)
    local test_cmd=$(build_test_command)

    log_info "Test command: $test_cmd"
    
    # 运行测试
    if eval "$test_cmd"; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_success "Tests completed successfully in ${duration}s"
        return 0
    else
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_error "Tests failed after ${duration}s"
        return 1
    fi
}

# 生成测试报告
generate_report() {
    if [ "$GENERATE_REPORT" = true ]; then
        log_step "Generating test report..."
        
        local report_file="coverage/test-report.txt"
        
        {
            echo "ThinkScramble Test Report"
            echo "========================"
            echo "Generated: $(date)"
            echo ""
            
            if [ "$COVERAGE" = true ] && [ -f "coverage/clover.xml" ]; then
                echo "Coverage Summary:"
                echo "=================="
                
                # 解析覆盖率数据
                if command -v xmllint &> /dev/null; then
                    local lines_covered=$(xmllint --xpath "//coverage/project/metrics/@coveredstatements" coverage/clover.xml 2>/dev/null | sed 's/.*="\([^"]*\)".*/\1/')
                    local lines_total=$(xmllint --xpath "//coverage/project/metrics/@statements" coverage/clover.xml 2>/dev/null | sed 's/.*="\([^"]*\)".*/\1/')
                    
                    if [ -n "$lines_covered" ] && [ -n "$lines_total" ] && [ "$lines_total" -gt 0 ]; then
                        local coverage_percent=$(echo "scale=2; $lines_covered * 100 / $lines_total" | bc -l 2>/dev/null || echo "0")
                        echo "Line Coverage: ${coverage_percent}% (${lines_covered}/${lines_total})"
                    fi
                fi
                
                echo ""
            fi
            
            echo "Test Results:"
            echo "============="
            echo "Filter: ${FILTER:-'All tests'}"
            echo "Test Suite: ${TESTSUITE:-'All suites'}"
            echo "Parallel: ${PARALLEL}"
            echo "Coverage: ${COVERAGE}"
            echo ""
            
        } > "$report_file"
        
        log_success "Test report generated: $report_file"
    fi
}

# 显示覆盖率摘要
show_coverage_summary() {
    if [ "$COVERAGE" = true ]; then
        log_step "Coverage Summary"
        
        if [ -f "coverage/coverage.txt" ]; then
            cat coverage/coverage.txt
        elif [ -f "coverage/clover.xml" ]; then
            log_info "Coverage report generated in coverage/html/index.html"
        fi
        
        echo ""
        log_info "Coverage reports:"
        echo "  📊 HTML Report: coverage/html/index.html"
        echo "  📄 Clover XML: coverage/clover.xml"
        echo "  📋 Text Report: coverage/coverage.txt"
    fi
}

# 清理函数
cleanup() {
    log_info "Cleaning up..."
    
    # 清理临时文件
    if [ -d "tests/data" ]; then
        rm -rf tests/data/test_*
    fi
    
    # 重置环境变量
    unset XDEBUG_MODE
}

# 主函数
main() {
    # 设置错误处理
    trap cleanup EXIT
    
    echo "🧪 Starting ThinkScramble test suite..."
    echo ""
    
    # 显示配置
    log_info "Test Configuration:"
    echo "  Coverage: ${COVERAGE}"
    echo "  Filter: ${FILTER:-'None'}"
    echo "  Test Suite: ${TESTSUITE:-'All'}"
    echo "  Parallel: ${PARALLEL}"
    echo "  Verbose: ${VERBOSE}"
    echo "  Stop on Failure: ${STOP_ON_FAILURE}"
    echo ""
    
    # 执行测试流程
    check_dependencies
    setup_coverage
    run_quality_checks
    
    if run_tests; then
        generate_report
        show_coverage_summary
        
        log_success "🎉 All tests passed!"
        exit 0
    else
        log_error "💥 Tests failed!"
        exit 1
    fi
}

# 运行主函数
main "$@"
