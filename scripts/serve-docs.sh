#!/bin/bash

# ThinkScramble 文档本地预览脚本

set -e

echo "🚀 Starting ThinkScramble Documentation Server"
echo "=============================================="

# 检查 Node.js 和 npm
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js first."
    echo "💡 Visit: https://nodejs.org/"
    exit 1
fi

if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm first."
    exit 1
fi

# 检查 docsify-cli
if ! command -v docsify &> /dev/null; then
    echo "📦 Installing docsify-cli globally..."
    npm install -g docsify-cli
    
    if [ $? -ne 0 ]; then
        echo "❌ Failed to install docsify-cli"
        echo "💡 Try: sudo npm install -g docsify-cli"
        exit 1
    fi
    
    echo "✅ docsify-cli installed successfully"
fi

# 检查文档目录
if [ ! -d "docs" ]; then
    echo "❌ docs directory not found"
    echo "💡 Please run this script from the project root directory"
    exit 1
fi

# 检查必要文件
required_files=("docs/index.html" "docs/_sidebar.md" "docs/README.md")

for file in "${required_files[@]}"; do
    if [ ! -f "$file" ]; then
        echo "❌ Required file not found: $file"
        exit 1
    fi
done

echo "✅ All required files found"

# 复制 README 到 docs 目录（如果不存在）
if [ ! -f "docs/README.md" ]; then
    echo "📄 Copying README.md to docs directory..."
    cp README.md docs/README.md
fi

# 设置端口
PORT=${1:-3000}

echo ""
echo "📚 Documentation will be available at:"
echo "   🌐 http://localhost:$PORT"
echo ""
echo "📋 Available pages:"
echo "   🏠 Home: http://localhost:$PORT"
echo "   ⚡ Quick Start: http://localhost:$PORT/#/quickstart"
echo "   📦 Installation: http://localhost:$PORT/#/installation"
echo "   🥧 PIE Installation: http://localhost:$PORT/#/pie-installation"
echo "   🎯 Annotations: http://localhost:$PORT/#/annotations"
echo "   ❓ FAQ: http://localhost:$PORT/#/faq"
echo ""
echo "💡 Tips:"
echo "   • Press Ctrl+C to stop the server"
echo "   • Edit files in docs/ directory to see live changes"
echo "   • Use search function in the documentation"
echo ""

# 启动服务器
echo "🚀 Starting documentation server on port $PORT..."
echo "⏳ Please wait for the server to start..."

cd docs

# 启动 docsify 服务器
docsify serve . --port $PORT --open

echo ""
echo "👋 Documentation server stopped"
echo "💝 Thank you for using ThinkScramble!"
