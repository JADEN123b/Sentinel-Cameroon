#!/bin/bash
# Health check script for Sentinel Cameroon
# Used by Docker to verify container health

set -e

# Check if Apache is running
if ! curl -f http://localhost/ > /dev/null 2>&1; then
    echo "Apache is not responding"
    exit 1
fi

# Check if database exists and is accessible
DB_PATH="${DB_PATH:-/var/www/html/database/data/sentinel_cameroon.sqlite}"

if [ ! -f "$DB_PATH" ]; then
    echo "Database file not found at $DB_PATH"
    exit 1
fi

# Try to query the database
if ! sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM sqlite_master;" > /dev/null 2>&1; then
    echo "Database query failed"
    exit 1
fi

echo "Health check passed"
exit 0
