#!/bin/bash
# Quick start script for Sentinel Cameroon Docker setup
# This script sets up the local development environment

set -e

echo "🚀 Sentinel Cameroon - Docker Quick Start"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    echo "   Visit: https://docs.docker.com/get-docker/"
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    echo "   Visit: https://docs.docker.com/compose/install/"
    exit 1
fi

echo "✅ Docker and Docker Compose are installed"
echo ""

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from template..."
    cp .env.example .env
    echo "✅ .env file created"
else
    echo "ℹ️  .env file already exists"
fi

echo ""
echo "🔨 Building Docker image..."
docker-compose build

echo ""
echo "🚢 Starting containers..."
docker-compose up -d

echo ""
echo "⏳ Waiting for database to initialize..."
sleep 5

# Check if database was created
if [ -f "database/data/sentinel_cameroon.sqlite" ]; then
    echo "✅ Database created successfully"
else
    echo "⚠️  Database file not found. Checking container..."
    docker exec sentinel-cameroon php /var/www/html/database/init-sqlite.php
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Setup Complete!"
echo ""
echo "🌐 Application is running at: http://localhost:8080"
echo "📊 Database: SQLite (database/data/sentinel_cameroon.sqlite)"
echo "📂 Uploads: ./uploads/"
echo ""
echo "📋 Useful commands:"
echo "   • View logs:       docker-compose logs -f web"
echo "   • Stop containers: docker-compose down"
echo "   • Access database: docker exec sentinel-cameroon sqlite3 /var/www/html/database/data/sentinel_cameroon.sqlite"
echo "   • Restart:         docker-compose restart web"
echo ""
echo "📚 Documentation:"
echo "   • Full guide:      DEPLOYMENT_GUIDE.md"
echo "   • Docker details:  DOCKER_SETUP.md"
echo ""
echo "🚀 Next steps:"
echo "   1. Open http://localhost:8080 in your browser"
echo "   2. Register a new account or login"
echo "   3. Start using Sentinel Cameroon!"
echo ""
echo "📤 To deploy to Render:"
echo "   1. Push code to GitHub: git push origin main"
echo "   2. Go to https://render.com"
echo "   3. Create new Web Service and connect your repository"
echo "   4. Render will automatically build and deploy!"
echo ""
