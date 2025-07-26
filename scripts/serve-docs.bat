@echo off
setlocal enabledelayedexpansion

REM ThinkScramble 文档本地预览脚本 (Windows)

echo 🚀 Starting ThinkScramble Documentation Server
echo ==============================================

REM 检查 Node.js
where node >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ Node.js is not installed. Please install Node.js first.
    echo 💡 Visit: https://nodejs.org/
    pause
    exit /b 1
)

REM 检查 npm
where npm >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ npm is not installed. Please install npm first.
    pause
    exit /b 1
)

REM 检查 docsify-cli
where docsify >nul 2>nul
if %errorlevel% neq 0 (
    echo 📦 Installing docsify-cli globally...
    npm install -g docsify-cli
    
    if !errorlevel! neq 0 (
        echo ❌ Failed to install docsify-cli
        echo 💡 Try running as administrator
        pause
        exit /b 1
    )
    
    echo ✅ docsify-cli installed successfully
)

REM 检查文档目录
if not exist "docs" (
    echo ❌ docs directory not found
    echo 💡 Please run this script from the project root directory
    pause
    exit /b 1
)

REM 检查必要文件
if not exist "docs\index.html" (
    echo ❌ Required file not found: docs\index.html
    pause
    exit /b 1
)

if not exist "docs\_sidebar.md" (
    echo ❌ Required file not found: docs\_sidebar.md
    pause
    exit /b 1
)

echo ✅ All required files found

REM 复制 README 到 docs 目录（如果不存在）
if not exist "docs\README.md" (
    echo 📄 Copying README.md to docs directory...
    copy README.md docs\README.md >nul
)

REM 设置端口
set PORT=%1
if "%PORT%"=="" set PORT=3000

echo.
echo 📚 Documentation will be available at:
echo    🌐 http://localhost:%PORT%
echo.
echo 📋 Available pages:
echo    🏠 Home: http://localhost:%PORT%
echo    ⚡ Quick Start: http://localhost:%PORT%/#/quickstart
echo    📦 Installation: http://localhost:%PORT%/#/installation
echo    🥧 PIE Installation: http://localhost:%PORT%/#/pie-installation
echo    🎯 Annotations: http://localhost:%PORT%/#/annotations
echo    ❓ FAQ: http://localhost:%PORT%/#/faq
echo.
echo 💡 Tips:
echo    • Press Ctrl+C to stop the server
echo    • Edit files in docs\ directory to see live changes
echo    • Use search function in the documentation
echo.

REM 启动服务器
echo 🚀 Starting documentation server on port %PORT%...
echo ⏳ Please wait for the server to start...

cd docs

REM 启动 docsify 服务器
docsify serve . --port %PORT% --open

echo.
echo 👋 Documentation server stopped
echo 💝 Thank you for using ThinkScramble!
pause
