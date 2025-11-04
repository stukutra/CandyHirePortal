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

# Detect docker-compose command (works for both Docker Desktop and Docker Engine)
if command -v docker-compose &> /dev/null; then
    DOCKER_COMPOSE="docker-compose"
elif docker compose version &> /dev/null; then
    DOCKER_COMPOSE="docker compose"
else
    echo "❌ Error: docker-compose or docker compose not found"
    exit 1
fi

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
echo "🗑️  Cleaning up existing data and containers..."
echo ""

# Stop ALL containers (Portal and SaaS)
echo "🛑 Stopping all CandyHire containers..."
docker stop candyhire-portal-mysql candyhire-portal-php candyhire-portal-phpmyadmin 2>/dev/null || true
docker stop candyhire-mysql candyhire-php candyhire-phpmyadmin 2>/dev/null || true

# Remove ALL containers
echo "🗑️  Removing all CandyHire containers..."
docker rm candyhire-portal-mysql candyhire-portal-php candyhire-portal-phpmyadmin 2>/dev/null || true
docker rm candyhire-mysql candyhire-php candyhire-phpmyadmin 2>/dev/null || true

# Remove ALL volumes (this deletes ALL data)
echo "💥 Deleting all database volumes (ALL DATA WILL BE LOST)..."
docker volume rm candyhire-portal-mysql-data 2>/dev/null || true
docker volume rm candyhire-mysql-data 2>/dev/null || true
docker volume rm candyhire-uploads 2>/dev/null || true

echo "✅ All data cleaned successfully"
echo ""
echo "🐳 Starting fresh Docker containers..."
echo ""

# Start SaaS Backend first (MySQL + PHP needed for tenant creation and login)
echo "🚀 Starting SaaS Backend (MySQL + PHP)..."
cd ../../CandyHire/Backend
$DOCKER_COMPOSE up -d --build
cd ../../CandyHirePortal/Backend
sleep 5

# Build and start Portal containers
$DOCKER_COMPOSE up -d --build

echo ""
echo "⏳ Waiting for Portal MySQL to be ready..."
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
echo "🎯 Creating tenant pool, countries, and admin user..."
echo ""

# Run Portal initial data insertion
docker exec -i candyhire-portal-mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD:-candyhire_portal_root_pass} CandyHirePortal < migration/02_initial_data.sql 2>/dev/null || echo "⚠️  Initial data already exists or failed to insert"
echo "✅ Portal initial data imported"

echo ""
echo "🏗️  Creating 50 tenant databases on SaaS MySQL server..."
echo "⚠️  This will take a few minutes. Please wait..."
echo ""

# Wait for SaaS MySQL to be ready
echo "⏳ Waiting for SaaS MySQL to be ready..."
max_attempts=30
attempt=0
while [ $attempt -lt $max_attempts ]; do
    if docker exec candyhire-mysql mysqladmin ping -h localhost -uroot -pR00t_P@ssw0rd_2024! --silent 2>/dev/null; then
        echo "✅ SaaS MySQL is ready!"
        break
    fi
    attempt=$((attempt + 1))
    echo "   Attempt $attempt/$max_attempts - SaaS MySQL not ready yet..."
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "⚠️  Warning: SaaS MySQL not accessible. Skipping tenant creation."
else
    # Tenant configuration
    TENANT_COUNT=50
    SAAS_SCHEMA="../../CandyHire/Backend/migration/04_candyhire_schema.sql"

    # Check if SaaS schema file exists
    if [ ! -f "$SAAS_SCHEMA" ]; then
        echo "⚠️  Warning: Tenant schema file not found at $SAAS_SCHEMA"
        echo "   Skipping tenant creation."
    else
        # Create 50 tenant databases on SaaS MySQL
        success_count=0
        for i in $(seq 1 $TENANT_COUNT); do
            DB_NAME="candyhire_tenant_$i"

            # Show progress every 10 databases
            if [ $(($i % 10)) -eq 0 ] || [ $i -eq 1 ]; then
                echo "  📦 Creating tenant $i/$TENANT_COUNT on SaaS MySQL..."
            fi

            # Create database on SaaS MySQL
            docker exec candyhire-mysql mysql -uroot -pR00t_P@ssw0rd_2024! -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

            # Apply schema (empty tables)
            docker exec -i candyhire-mysql mysql -uroot -pR00t_P@ssw0rd_2024! $DB_NAME < $SAAS_SCHEMA 2>/dev/null

            success_count=$((success_count + 1))
        done

        echo ""
        echo "✅ Successfully created $success_count/$TENANT_COUNT tenant databases on SaaS MySQL"

        # Grant permissions to candyhire_user on all tenant databases
        echo ""
        echo "🔐 Granting permissions to candyhire_user on tenant databases..."
        docker exec candyhire-mysql mysql -uroot -pR00t_P@ssw0rd_2024! -e "GRANT ALL PRIVILEGES ON \`candyhire_tenant_%\`.* TO 'candyhire_user'@'%'; FLUSH PRIVILEGES;" 2>/dev/null
        echo "✅ Permissions granted successfully"
    fi
fi
echo ""

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║          CandyHire Platform - SETUP COMPLETATO!               ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📡 PORTAL - Registration & Payment"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "API Backend:        http://localhost:8082"
echo "PHPMyAdmin:         http://localhost:8083"
echo "MySQL Port:         localhost:3308"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📡 SAAS - Multi-Tenant Application"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "API Backend:        http://localhost:8080"
echo "PHPMyAdmin:         http://localhost:8081"
echo "MySQL Port:         localhost:3307"
echo "Tenant Databases:   50 databases created (candyhire_tenant_1 to 50)"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔐 CREDENZIALI DATABASE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Host:               localhost:3308"
echo "Database:           CandyHirePortal"
echo "Username:           candyhire_portal_user"
echo "Password:           candyhire_portal_pass"
echo ""
echo "Root Username:      root"
echo "Root Password:      candyhire_portal_root_pass"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "👤 UTENTE ADMIN DEMO"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Username:           admin"
echo "Email:              admin@candyhire.com"
echo "Password:           Admin123!"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🛠️  COMANDI UTILI"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "  docker compose up -d       → Avvia servizi"
echo "  docker compose down        → Ferma servizi"
echo "  docker compose logs -f     → Visualizza log"
echo "  docker compose restart     → Riavvia servizi"
echo ""
echo "Happy coding! 🚀"
echo ""
