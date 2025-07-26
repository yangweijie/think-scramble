@echo off
echo Installing ThinkScramble CLI...

set INSTALL_DIR=%USERPROFILE%\bin
if not exist "%INSTALL_DIR%" mkdir "%INSTALL_DIR%"

copy scramble.phar "%INSTALL_DIR%\"
copy scramble.bat "%INSTALL_DIR%\"

echo ThinkScramble installed successfully!
echo Add %INSTALL_DIR% to your PATH if not already added
echo Usage: scramble --help
