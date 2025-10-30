#!/bin/bash

# ============================================
# CandyHire Portal Backend Setup Script
# ============================================

set -e

echo "========================================"
echo "CandyHire Portal Backend Setup"
echo "========================================"
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Error: Docker is not running. Please start Docker Desktop first."
    exit 1
fi

echo "✅ Docker is running"
echo ""

# Check if .env exists, if not copy from .env.example
if [ ! -f .env ]; then
    echo "📄 Creating .env file from .env.example..."
    cp .env.example .env
    echo "⚠️  Please edit .env file with your configuration before continuing!"
    echo "   Especially set a strong JWT_SECRET (minimum 32 characters)"
    echo ""
    read -p "Press Enter to continue after editing .env file..."
else
    echo "✅ .env file already exists"
fi

echo ""
echo "🐳 Starting Docker containers..."
echo ""

# Stop only Portal containers (not CandyHire containers)
echo "🛑 Stopping existing Portal containers..."
docker stop candyhire-portal-mysql candyhire-portal-php candyhire-portal-phpmyadmin 2>/dev/null || true
docker rm candyhire-portal-mysql candyhire-portal-php candyhire-portal-phpmyadmin 2>/dev/null || true

# Build and start containers
docker-compose up -d --build

echo ""
echo "⏳ Waiting for MySQL to be ready..."
echo ""

# Wait for MySQL to be healthy
max_attempts=30
attempt=0

while [ $attempt -lt $max_attempts ]; do
    if docker exec candyhire-portal-mysql mysqladmin ping -h localhost -uroot -p${MYSQL_ROOT_PASSWORD:-candyhire_portal_root_pass} --silent 2>/dev/null; then
        echo "✅ MySQL is ready!"
        break
    fi
    attempt=$((attempt + 1))
    echo "   Attempt $attempt/$max_attempts - MySQL not ready yet..."
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "❌ Error: MySQL failed to start properly"
    exit 1
fi

echo ""
echo "📊 Database schema created successfully"
echo ""
echo "🎯 Creating tenant pool (4 available tenant schemas)..."
echo ""

# Run initial data insertion
docker exec -i candyhire-portal-mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD:-candyhire_portal_root_pass} CandyHirePortal < migration/02_initial_data.sql 2>/dev/null || echo "⚠️  Initial data already exists or failed to insert"

echo ""
echo "✅ CandyHire Portal Backend is ready!"
echo ""
echo "========================================"
echo "Service URLs:"
echo "========================================"
echo "📡 API Backend:     http://localhost:8082"
echo "🗄️  PHPMyAdmin:     http://localhost:8083"
echo "🐬 MySQL Port:      3308"
echo ""
echo "Database Credentials:"
echo "  Host:     localhost:3308"
echo "  Database: CandyHirePortal"
echo "  User:     candyhire_portal_user"
echo "  Password: candyhire_portal_pass"
echo ""
echo "========================================"
echo "Available tenant schemas in pool: 4"
echo "  - candyhire_tenant_1"
echo "  - candyhire_tenant_2"
echo "  - candyhire_tenant_3"
echo "  - candyhire_tenant_4"
echo "========================================"
echo ""
echo "To stop: docker-compose down"
echo "To view logs: docker-compose logs -f"
echo ""
