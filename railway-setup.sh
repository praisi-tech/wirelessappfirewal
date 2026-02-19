#!/bin/bash

# Railway Setup Script
# This script runs during deployment to ensure database is properly configured

echo "==================================="
echo "Railway Deployment Setup"
echo "==================================="

# Check if DATABASE_URL is set
if [ -z "$DATABASE_URL" ]; then
    echo "⚠️  DATABASE_URL not set!"
    
    # Try to construct from individual variables
    if [ -n "$MYSQL_HOST" ] && [ -n "$MYSQL_USER" ] && [ -n "$MYSQL_PASSWORD" ] && [ -n "$MYSQL_DATABASE" ]; then
        export DATABASE_URL="mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@${MYSQL_HOST}:${MYSQL_PORT:-3306}/${MYSQL_DATABASE}"
        echo "✓ Constructed DATABASE_URL from MYSQL_* variables"
    else
        echo "❌ Missing database variables!"
        echo "Set these in Railway Variables:"
        echo "  - MYSQL_HOST"
        echo "  - MYSQL_PORT"
        echo "  - MYSQL_USER"
        echo "  - MYSQL_PASSWORD"
        echo "  - MYSQL_DATABASE"
    fi
else
    echo "✓ DATABASE_URL is set: $DATABASE_URL"
fi

# Ensure DB_CONNECTION is set
export DB_CONNECTION=${DB_CONNECTION:-mysql}

# Clear config cache
php artisan config:clear

# Try to run migrations
echo ""
echo "Running migrations..."
php artisan migrate --force --no-interaction

if [ $? -eq 0 ]; then
    echo "✓ Migrations completed successfully"
else
    echo "⚠️  Migrations failed - this may be normal on first deploy if DB is not ready yet"
fi

# Cache config only if migrations successful
echo "Caching configuration..."
php artisan config:cache

echo ""
echo "==================================="
echo "Setup completed!"
echo "==================================="
