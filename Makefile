# ThinkScramble Makefile

.PHONY: help build install clean test phar release

# 默认目标
help:
	@echo "ThinkScramble Build Commands"
	@echo "============================"
	@echo "make build    - Build PHAR executable"
	@echo "make install  - Install globally"
	@echo "make clean    - Clean build files"
	@echo "make test     - Run tests"
	@echo "make phar     - Build PHAR only"
	@echo "make release  - Create release package"

# 构建所有
build:
	@echo "🚀 Building ThinkScramble..."
	php build.php

# 仅构建 PHAR
phar:
	@echo "📦 Building PHAR..."
	@if [ ! -f "build/tools/box.phar" ]; then \
		mkdir -p build/tools; \
		curl -L https://github.com/box-project/box/releases/latest/download/box.phar -o build/tools/box.phar; \
		chmod +x build/tools/box.phar; \
	fi
	@mkdir -p dist
	php build/tools/box.phar compile --config=box.json
	chmod +x dist/scramble.phar

# 安装到系统
install: build
	@echo "📥 Installing ThinkScramble..."
	@if [ "$(shell id -u)" != "0" ]; then \
		echo "Please run as root: sudo make install"; \
		exit 1; \
	fi
	cp dist/scramble-linux /usr/local/bin/scramble
	chmod +x /usr/local/bin/scramble
	@echo "✅ ThinkScramble installed successfully!"

# 清理构建文件
clean:
	@echo "🧹 Cleaning build files..."
	rm -rf dist/
	rm -rf build/tools/
	@echo "✅ Clean completed"

# 运行测试
test:
	@echo "🧪 Running tests..."
	@find src -name "*.php" -exec php -l {} \; > /dev/null
	@php bin/scramble --version > /dev/null
	@echo "✅ Tests passed"

# 创建发布包
release: build
	@echo "📦 Creating release package..."
	@VERSION=$$(php -r "echo json_decode(file_get_contents('composer.json'))->version ?? '1.4.0';"); \
	echo "Creating release for version: $$VERSION"

# 开发模式
dev:
	@echo "🔧 Setting up development environment..."
	composer install
	@echo "✅ Development environment ready"

# 生产模式
prod:
	@echo "🚀 Setting up production environment..."
	composer install --no-dev --optimize-autoloader
	@echo "✅ Production environment ready"
