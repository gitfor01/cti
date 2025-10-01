#!/bin/bash

# Tenable Integration Test Launcher
# This script starts a PHP server and opens the test in your browser

echo "================================================"
echo "  Tenable Integration Test Launcher"
echo "================================================"
echo ""

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ ERROR: PHP is not installed or not in PATH"
    echo "Please install PHP to continue"
    exit 1
fi

echo "✅ PHP found: $(php -v | head -n 1)"
echo ""

# Check if port 8080 is already in use
if lsof -Pi :8080 -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo "⚠️  Port 8080 is already in use"
    echo ""
    echo "Option 1: Open the test directly in your browser:"
    echo "   http://localhost:8080/test_tenable_complete.php"
    echo ""
    echo "Option 2: Stop the existing server and run this script again"
    echo ""
    read -p "Press Enter to open in browser anyway, or Ctrl+C to cancel..."
    open "http://localhost:8080/test_tenable_complete.php" 2>/dev/null || \
    xdg-open "http://localhost:8080/test_tenable_complete.php" 2>/dev/null || \
    echo "Please manually open: http://localhost:8080/test_tenable_complete.php"
    exit 0
fi

# Start PHP server
echo "🚀 Starting PHP development server on port 8080..."
echo "📁 Document root: $SCRIPT_DIR"
echo ""
echo "The test will open in your browser automatically."
echo "Press Ctrl+C to stop the server when done."
echo ""
echo "================================================"
echo ""

# Wait a moment for server to start, then open browser
(sleep 2 && open "http://localhost:8080/test_tenable_complete.php" 2>/dev/null || \
 xdg-open "http://localhost:8080/test_tenable_complete.php" 2>/dev/null || \
 echo "Please manually open: http://localhost:8080/test_tenable_complete.php") &

# Start the server (this will block until Ctrl+C)
cd "$SCRIPT_DIR"
php -S localhost:8080

echo ""
echo "Server stopped."