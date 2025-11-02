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
echo "🐳 Starting Docker containers..."
echo ""

# Stop only Portal containers (not CandyHire containers)
echo "🛑 Stopping existing Portal containers..."
docker stop candyhire-portal-mysql candyhire-portal-php candyhire-portal-phpmyadmin 2>/dev/null || true
docker rm candyhire-portal-mysql candyhire-portal-php candyhire-portal-phpmyadmin 2>/dev/null || true

# Build and start containers
$DOCKER_COMPOSE up -d --build

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
echo "🎯 Creating tenant pool, countries, and admin user..."
echo ""

# Run Portal initial data insertion
docker exec -i candyhire-portal-mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD:-candyhire_portal_root_pass} CandyHirePortal < migration/02_initial_data.sql 2>/dev/null || echo "⚠️  Initial data already exists or failed to insert"
echo "✅ Portal initial data imported"

echo ""
echo "🏗️  Creating 100 tenant databases..."
echo "⚠️  This will take several minutes. Please wait..."
echo ""

# Tenant configuration
TENANT_COUNT=100
SAAS_SCHEMA="../../CandyHire/Backend/migration/04_candyhire_schema.sql"
SAAS_DATA="../../CandyHire/Backend/migration/05_candyhire_initial_data.sql"

# Check if SaaS schema files exist
if [ ! -f "$SAAS_SCHEMA" ]; then
    echo "⚠️  Warning: SaaS schema file not found at $SAAS_SCHEMA"
    echo "   Skipping tenant creation. Make sure CandyHire Backend migration files exist."
else
    # Create 100 tenant databases
    success_count=0
    for i in $(seq 1 $TENANT_COUNT); do
        DB_NAME="candyhire_tenant_$i"

        # Show progress every 10 databases
        if [ $(($i % 10)) -eq 0 ] || [ $i -eq 1 ]; then
            echo "  📦 Processing tenant $i/$TENANT_COUNT..."
        fi

        # Create database
        docker exec candyhire-portal-mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD:-candyhire_portal_root_pass} -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

        # Apply schema
        docker exec -i candyhire-portal-mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD:-candyhire_portal_root_pass} $DB_NAME < $SAAS_SCHEMA 2>/dev/null

        # Apply initial data
        docker exec -i candyhire-portal-mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD:-candyhire_portal_root_pass} $DB_NAME < $SAAS_DATA 2>/dev/null

        success_count=$((success_count + 1))
    done

    echo ""
    echo "✅ Successfully created $success_count/$TENANT_COUNT tenant databases"
fi

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                CandyHire Portal Backend                       ║"
echo "║                  SETUP COMPLETATO!                            ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📡 ACCESSO BROWSER"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "API Backend:        http://localhost:8082"
echo "PHPMyAdmin:         http://localhost:8083"
echo "MySQL Port:         localhost:3308"
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
