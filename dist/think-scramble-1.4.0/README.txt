ThinkScramble CLI - ThinkPHP OpenAPI Documentation Generator

INSTALLATION:
  Linux/macOS: Run ./install.sh as root
  Windows: Run install.bat as administrator

USAGE:
  scramble --help                    Show help
  scramble --version                 Show version
  scramble --output=api.json         Generate documentation
  scramble --watch --output=api.json Monitor file changes
  scramble --stats                   Show statistics

EXAMPLES:
  # Generate basic documentation
  scramble --output=api.json

  # Generate with middleware analysis
  scramble --output=api.json --middleware

  # Export to Postman format
  scramble --format=postman --output=api.postman.json

  # Watch for file changes
  scramble --watch --output=api.json

For more information, visit: https://github.com/yangweijie/think-scramble
