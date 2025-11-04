#!/bin/bash

# ============================================
# CandyHire Portal Backend Setup Script
# ============================================

set -e

# MySQL Root Password (UPDATED for shared MySQL)
MYSQL_ROOT_PASSWORD='CandyHire2024Root'

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

echo ""
echo "🐳 Starting Docker containers (MySQL + Portal PHP + SaaS PHP + PHPMyAdmin)..."
echo ""

# Start Portal services
$DOCKER_COMPOSE up -d --build

echo ""
echo "⏳ Waiting for MySQL to be ready..."
echo ""
sleep 5

# Wait for MySQL to be healthy
max_attempts=30
attempt=0

while [ $attempt -lt $max_attempts ]; do
    if docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysqladmin ping -h localhost -uroot --silent 2>/dev/null; then
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
echo "📊 Creating Portal database..."
echo ""

# Create Portal database and user
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE DATABASE IF NOT EXISTS CandyHirePortal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE USER IF NOT EXISTS 'candyhire_portal_user'@'%' IDENTIFIED BY 'candyhire_portal_pass';" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "GRANT ALL PRIVILEGES ON CandyHirePortal.* TO 'candyhire_portal_user'@'%';" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE USER IF NOT EXISTS 'candyhire_user'@'%' IDENTIFIED BY 'CandyH1re_S3cur3P@ss!';" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "FLUSH PRIVILEGES;" 2>/dev/null

echo "✅ Portal database created"
echo ""
echo "🏗️  Creating Portal schema..."
echo ""

# Apply Portal schema
docker exec -i -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot CandyHirePortal < migration/01_schema.sql 2>/dev/null

echo "✅ Portal schema created"
echo ""
echo "🎯 Inserting Portal initial data (admin, countries, tenant pool)..."
echo ""

# Run Portal initial data insertion
docker exec -i -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot CandyHirePortal < migration/02_initial_data.sql 2>/dev/null || echo "⚠️  Initial data already exists or failed to insert"

echo "✅ Portal initial data imported"
echo ""
echo "💎 Creating subscription tiers..."
echo ""

# Run Subscription Tiers migration
docker exec -i -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot CandyHirePortal < migration/04_subscription_tiers.sql 2>/dev/null || echo "⚠️  Subscription tiers already exist or failed to insert"

echo "✅ Subscription tiers created"
echo ""
echo "🏗️  Creating 50 tenant databases..."
echo "⚠️  This will take a few minutes. Please wait..."
echo ""

# Tenant configuration
TENANT_COUNT=50
SAAS_SCHEMA="../../CandyHire/Backend/migration/04_candyhire_schema.sql"

# Check if SaaS schema file exists
if [ ! -f "$SAAS_SCHEMA" ]; then
    echo "⚠️  Warning: Tenant schema file not found at $SAAS_SCHEMA"
    echo "   Skipping tenant creation."
else
    # Create 50 tenant databases
    success_count=0
    for i in $(seq 1 $TENANT_COUNT); do
        DB_NAME="candyhire_tenant_$i"

        # Show progress every 10 databases
        if [ $(($i % 10)) -eq 0 ] || [ $i -eq 1 ]; then
            echo "  📦 Creating tenant $i/$TENANT_COUNT..."
        fi

        # Create database
        docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

        # Apply schema (empty tables)
        docker exec -i -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot $DB_NAME < $SAAS_SCHEMA 2>/dev/null

        success_count=$((success_count + 1))
    done

    echo ""
    echo "✅ Successfully created $success_count/$TENANT_COUNT tenant databases"

    # Grant permissions to candyhire_user on all tenant databases
    echo ""
    echo "🔐 Granting permissions to candyhire_user on tenant databases..."
    docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "GRANT ALL PRIVILEGES ON \`candyhire_tenant_%\`.* TO 'candyhire_user'@'%'; FLUSH PRIVILEGES;" 2>/dev/null
    echo "✅ Permissions granted successfully"
fi

echo ""
echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║       CandyHire Portal Backend - SETUP COMPLETATO!            ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📡 PORTAL - Registration & Payment"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Frontend:           http://localhost:4200"
echo "API Backend:        http://localhost:8082"
echo "PHPMyAdmin:         http://localhost:8083"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📡 SAAS - Multi-Tenant Application"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Frontend:           http://localhost:4202"
echo "API Backend:        http://localhost:8080"
echo "Tenant Databases:   50 databases (candyhire_tenant_1 to candyhire_tenant_50)"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🗄️  SHARED MYSQL DATABASE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "MySQL Port:         localhost:3306"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔐 CREDENZIALI DATABASE (SHARED MySQL)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Host:               localhost:3306"
echo "Database:           CandyHirePortal"
echo "Username:           candyhire_portal_user"
echo "Password:           candyhire_portal_pass"
echo ""
echo "Root Username:      root"
echo "Root Password:      CandyHire2024Root"
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
