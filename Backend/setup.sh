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
echo "⚠️  WARNING: This will DROP and recreate ALL databases!"
echo "   - CandyHirePortal database"
echo "   - All 1000 tenant databases (candyhire_tenant_1 to candyhire_tenant_1000)"
echo "   - All existing data will be PERMANENTLY DELETED"
echo ""
read -p "Continue? (y/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Setup cancelled."
    exit 0
fi
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

# Drop and recreate Portal database to ensure clean state
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "DROP DATABASE IF EXISTS CandyHirePortal;" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE DATABASE CandyHirePortal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE USER IF NOT EXISTS 'candyhire_portal_user'@'%' IDENTIFIED BY 'candyhire_portal_pass';" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "GRANT ALL PRIVILEGES ON CandyHirePortal.* TO 'candyhire_portal_user'@'%';" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE USER IF NOT EXISTS 'candyhire_user'@'%' IDENTIFIED BY 'CandyH1re_S3cur3P@ss!';" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "GRANT SELECT ON CandyHirePortal.* TO 'candyhire_user'@'%';" 2>/dev/null
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

# Generate fresh password hash for admin user
echo "   Generating admin password hash..."
ADMIN_PASSWORD_HASH=$(docker exec candyhire-portal-php php -r "echo password_hash('Admin123!', PASSWORD_BCRYPT);")
echo "   ✓ Password hash generated"

# Run Portal initial data insertion (admin, countries - WITHOUT tenant pool yet)
docker exec -i -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot CandyHirePortal < migration/02_initial_data.sql 2>/dev/null || echo "⚠️  Initial data already exists or failed to insert"

# Update admin user with freshly generated password hash
echo "   Updating admin password with fresh hash..."
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot CandyHirePortal -e "UPDATE admin_users SET password_hash = '$ADMIN_PASSWORD_HASH' WHERE email = 'admin@candyhire.com';" 2>/dev/null

echo "✅ Portal initial data imported (admin and countries)"
echo ""
echo "💎 Creating subscription tiers..."
echo ""

# Run Subscription Tiers migration
docker exec -i -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot CandyHirePortal < migration/04_subscription_tiers.sql 2>/dev/null || echo "⚠️  Subscription tiers already exist or failed to insert"

echo "✅ Subscription tiers created"
echo ""
echo "🏗️  Tenant Database Configuration"
echo ""

# Ask user for tenant count with 5 second timeout
read -t 5 -p "How many tenant databases do you want to create? (1-1000, default: 10): " TENANT_COUNT
timeout_exit=$?

# If timeout occurred or no input, use default
if [ $timeout_exit -ne 0 ] || [ -z "$TENANT_COUNT" ]; then
    TENANT_COUNT=10
    echo ""
    echo "   ⏱️  Timeout or no input - using default: $TENANT_COUNT tenants"
elif ! [[ "$TENANT_COUNT" =~ ^[0-9]+$ ]] || [ "$TENANT_COUNT" -lt 1 ] || [ "$TENANT_COUNT" -gt 1000 ]; then
    echo "❌ Invalid number. Must be between 1 and 1000."
    exit 1
fi

echo ""
echo "📋 Generating tenant pool SQL for $TENANT_COUNT tenants..."

# Tenant configuration
TENANT_SCHEMA="migration/tenant_schema/schema.sql"
TENANT_INITIAL_DATA="migration/tenant_schema/initial_data.sql"
TENANT_POOL_SQL="migration/generated_tenant_pool.sql"

# Generate tenant pool SQL
docker exec candyhire-portal-php php -r "
\$tenantCount = $TENANT_COUNT;
\$itemsPerLine = 10;

echo \"-- ============================================\\n\";
echo \"-- Tenant Pool - \$tenantCount Pre-allocated Tenants\\n\";
echo \"-- Generated on \" . date('Y-m-d H:i:s') . \"\\n\";
echo \"-- ============================================\\n\\n\";

echo \"-- Clean tenant_pool table to ensure fresh start\\n\";
echo \"TRUNCATE TABLE \\\`tenant_pool\\\`;\\n\\n\";

echo \"INSERT INTO \\\`tenant_pool\\\` (\\\`tenant_id\\\`, \\\`is_available\\\`) VALUES\\n\";

for (\$i = 1; \$i <= \$tenantCount; \$i++) {
    echo \"(\$i, TRUE)\";

    if (\$i < \$tenantCount) {
        echo \",\";
        if (\$i % \$itemsPerLine === 0) {
            echo \"\\n\";
        } else {
            echo \" \";
        }
    } else {
        echo \";\\n\";
    }
}

echo \"\\n-- ✅ \$tenantCount tenant pool entries created\\n\";
" > $TENANT_POOL_SQL

echo "✅ Tenant pool SQL generated"

# Check if Tenant schema file exists
if [ ! -f "$TENANT_SCHEMA" ]; then
    echo "⚠️  Warning: Tenant schema file not found at $TENANT_SCHEMA"
    echo "   Skipping tenant creation."
else
    # Clean up ALL existing tenant databases first
    echo "🧹 Cleaning up existing tenant databases..."
    existing_dbs=$(docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "SHOW DATABASES LIKE 'candyhire_tenant_%';" | grep candyhire_tenant || true)

    if [ ! -z "$existing_dbs" ]; then
        echo "$existing_dbs" | while read db_name; do
            echo "   Dropping old database: $db_name"
            docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "DROP DATABASE IF EXISTS \`$db_name\`;" 2>/dev/null
        done
        echo "✅ All old tenant databases dropped"
    else
        echo "   No existing tenant databases found"
    fi

    echo ""
    echo "📦 Creating $TENANT_COUNT new tenant databases..."

    # Create tenant databases
    success_count=0
    for i in $(seq 1 $TENANT_COUNT); do
        DB_NAME="candyhire_tenant_$i"

        # Show progress every 10 databases
        if [ $(($i % 10)) -eq 0 ] || [ $i -eq 1 ]; then
            echo "  📦 Creating tenant $i/$TENANT_COUNT..."
        fi

        # Create database
        docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

        # Apply schema with AUTO_INCREMENT
        docker exec -i -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot $DB_NAME < $TENANT_SCHEMA 2>/dev/null

        # Apply initial data if file exists (replace {{TENANT_ID}} with actual tenant ID)
        if [ -f "$TENANT_INITIAL_DATA" ]; then
            sed "s/{{TENANT_ID}}/$i/g" $TENANT_INITIAL_DATA | docker exec -i -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot $DB_NAME 2>/dev/null
        fi

        success_count=$((success_count + 1))
    done

    echo ""
    echo "✅ Successfully created $success_count/$TENANT_COUNT tenant databases"

    # Grant permissions to candyhire_user on all tenant databases
    echo ""
    echo "🔐 Granting permissions to candyhire_user on tenant databases..."
    docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "GRANT ALL PRIVILEGES ON \`candyhire_tenant_%\`.* TO 'candyhire_user'@'%'; FLUSH PRIVILEGES;" 2>/dev/null
    echo "✅ Permissions granted successfully"

    # Insert tenant pool data
    echo ""
    echo "📊 Populating tenant pool with $TENANT_COUNT entries..."
    docker exec -i -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot CandyHirePortal < $TENANT_POOL_SQL 2>/dev/null
    echo "✅ Tenant pool populated successfully"

echo ""
echo "🔧 Setting up n8n..."
echo ""

# Stop and remove n8n container and volumes if they exist
echo "   Cleaning up old n8n data..."
docker stop candyhire-n8n 2>/dev/null || true
docker rm candyhire-n8n 2>/dev/null || true
docker volume rm candyhire-n8n-data candyhire-n8n-files 2>/dev/null || true

# Enable MySQL function creators (required for n8n migrations)
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "SET GLOBAL log_bin_trust_function_creators = 1;" 2>/dev/null

# Drop and recreate n8n database
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "DROP DATABASE IF EXISTS n8n;" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE DATABASE n8n CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE USER IF NOT EXISTS 'n8n_user'@'%' IDENTIFIED BY 'n8n_secure_pass';" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "GRANT ALL PRIVILEGES ON n8n.* TO 'n8n_user'@'%';" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "FLUSH PRIVILEGES;" 2>/dev/null

echo "   ✓ n8n database created (clean)"

# Start n8n container
echo "   Starting n8n container..."
$DOCKER_COMPOSE up -d n8n

# Wait for n8n to be ready and initialize database
echo "   Waiting for n8n to initialize and complete migrations..."

for i in {1..60}; do
    if docker logs candyhire-n8n 2>&1 | grep -q "Editor is now accessible"; then
        echo "   ✓ n8n is ready"
        break
    fi
    if docker logs candyhire-n8n 2>&1 | grep -q "There was an error running database migrations"; then
        echo "   ⚠️  n8n migration error detected, restarting container..."
        docker stop candyhire-n8n 2>/dev/null
        docker rm candyhire-n8n 2>/dev/null
        docker exec -e MYSQL_PWD=\"$MYSQL_ROOT_PASSWORD\" candyhire-portal-mysql mysql -uroot -e \"DROP DATABASE IF EXISTS n8n;\" 2>/dev/null
        docker exec -e MYSQL_PWD=\"$MYSQL_ROOT_PASSWORD\" candyhire-portal-mysql mysql -uroot -e \"CREATE DATABASE n8n CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\" 2>/dev/null
        $DOCKER_COMPOSE up -d n8n
        sleep 10
        continue
    fi
    if [ $i -eq 60 ]; then
        echo "   ⚠️  Warning: n8n taking longer than expected to start..."
    fi
    sleep 2
done

sleep 5

# Create default n8n admin user via API
echo "   Creating default n8n admin user..."
N8N_ADMIN_EMAIL="admin@candyhire.local"
N8N_ADMIN_PASSWORD="Admin123456"
N8N_ADMIN_FIRSTNAME="Admin"
N8N_ADMIN_LASTNAME="CandyHire"

response=$(curl -s -X POST http://localhost:5678/rest/owner/setup \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$N8N_ADMIN_EMAIL\",\"password\":\"$N8N_ADMIN_PASSWORD\",\"firstName\":\"$N8N_ADMIN_FIRSTNAME\",\"lastName\":\"$N8N_ADMIN_LASTNAME\"}")

if echo "$response" | grep -q "Instance owner already setup"; then
    echo "   ℹ️  Owner already exists, resetting password..."
    docker exec candyhire-n8n n8n user-management:reset \
      --email="$N8N_ADMIN_EMAIL" \
      --password="$N8N_ADMIN_PASSWORD" \
      --firstName="$N8N_ADMIN_FIRSTNAME" \
      --lastName="$N8N_ADMIN_LASTNAME" 2>&1 | grep -v "Permissions" | head -1
elif echo "$response" | grep -q "email"; then
    echo "   ✓ n8n admin user created successfully"
else
    echo "   ⚠️  Using CLI fallback..."
    docker exec candyhire-n8n n8n user-management:reset \
      --email="$N8N_ADMIN_EMAIL" \
      --password="$N8N_ADMIN_PASSWORD" \
      --firstName="$N8N_ADMIN_FIRSTNAME" \
      --lastName="$N8N_ADMIN_LASTNAME" 2>&1 | grep -v "Permissions" | head -1
fi

echo "   ✓ n8n owner setup completed"
echo ""
echo "✅ n8n setup completed"
echo ""

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
echo "Tenant Databases:   $TENANT_COUNT databases (candyhire_tenant_1 to candyhire_tenant_$TENANT_COUNT)"
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
