#!/bin/bash

# ============================================
# CandyHire Portal + SaaS FULL RESET Setup
# (Ubuntu only – destroys and recreates everything)
# ============================================

set -e

# ------------------------------
# Paths (dynamic to $HOME)
# ------------------------------
ATTACH_DIR="$HOME/candyhire-data/attachments"
# Usa la directory corrente dello script per determinare PROJECT_ROOT in modo dinamico
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
PORTAL_DIR="$PROJECT_ROOT/CandyHirePortal/Backend"
SAAS_BACKEND_DIR="$PROJECT_ROOT/CandyHire/Backend"
PROJECT_ATTACH_IN_REPO="$SAAS_BACKEND_DIR/Attach"   # non usata per runtime (solo compat)
PROJECT_LOGS_IN_REPO="$SAAS_BACKEND_DIR/logs"

# MySQL Root Password (UPDATED for shared MySQL)
MYSQL_ROOT_PASSWORD='CandyHire2024Root'

echo "========================================"
echo "CandyHire Portal + SaaS FULL RESET Setup"
echo "========================================"
echo "HOME:               $HOME"
echo "ATTACH_DIR:         $ATTACH_DIR"
echo "PORTAL compose dir: $PORTAL_DIR"
echo ""

# ============================================
# Sudo upfront (for Ollama + perms)
# ============================================
echo "🔐 This script requires sudo for some steps."
sudo -v
# Keep sudo alive
while true; do sudo -n true; sleep 60; kill -0 "$$" || exit; done 2>/dev/null &

# ============================================
# OS detect (kept from your original)
# ============================================
detect_os() {
  if [[ "$OSTYPE" == "darwin"* ]]; then
    echo "macos"
  elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
    echo "linux"
  else
    echo "unknown"
  fi
}
OS_TYPE=$(detect_os)

echo "💻 Detected OS: $OS_TYPE"
echo ""
echo "⚠️  WARNING: This will DROP and recreate ALL databases!"
echo "   - CandyHirePortal database"
echo "   - All tenant databases (candyhire_tenant_N)"
echo "   - n8n database"
echo "   - All existing data will be PERMANENTLY DELETED"
echo ""
read -p "Continue? (y/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
  echo "Setup cancelled."
  exit 0
fi
echo ""

# ============================================
# Ensure Docker is running
# ============================================
if ! docker info >/dev/null 2>&1; then
  echo "❌ Error: Docker is not running. Start Docker and retry."
  exit 1
fi
echo "✅ Docker is running"

# ============================================
# Choose docker compose command
# ============================================
if command -v docker-compose &>/dev/null; then
  DOCKER_COMPOSE="docker-compose"
elif docker compose version &>/dev/null; then
  DOCKER_COMPOSE="docker compose"
else
  echo "❌ Error: docker-compose or docker compose not found"
  exit 1
fi

# ============================================
# Attach dir (host) – definitive place for uploads
# ============================================
echo "📦 Preparing Attach persistent storage at: $ATTACH_DIR"
if [ ! -d "$ATTACH_DIR" ]; then
  mkdir -p "$ATTACH_DIR"
fi
# NB: owner www-data:www-data per consentire mkdir da Apache/PHP nel container
sudo chown -R www-data:www-data "$ATTACH_DIR"
sudo chmod -R 775 "$ATTACH_DIR"
echo "   ✓ Permissions set (www-data:www-data, 775)"

# ============================================
# Logs dir (in repo) – keep your original behavior
# (These are mapped in compose; adjust perms to avoid write errors)
# ============================================
if [ -d "$PROJECT_LOGS_IN_REPO" ]; then
  echo "📝 Ensuring repo logs dir permissions: $PROJECT_LOGS_IN_REPO"
  sudo chown -R $USER:www-data "$PROJECT_LOGS_IN_REPO" || true
  sudo chmod -R 775 "$PROJECT_LOGS_IN_REPO" || true
fi

# (Compat) old in-repo Attach dir: not used for runtime, ensure exists to avoid surprises
if [ -d "$PROJECT_ATTACH_IN_REPO" ]; then
  echo "ℹ️  Repo Attach dir exists (not used at runtime): $PROJECT_ATTACH_IN_REPO"
fi

# ============================================
# Helper: import SQL into MySQL container
# (kept from your original, linux uses docker cp)
# ============================================
import_sql_file() {
  local sql_file=$1
  local database=$2
  local container_name="candyhire-portal-mysql"

  if [ "$OS_TYPE" == "linux" ]; then
    local temp_file="/tmp/$(basename "$sql_file")"
    docker cp "$sql_file" "$container_name:$temp_file" 2>/dev/null
    docker exec "$container_name" bash -c "MYSQL_PWD=\"$MYSQL_ROOT_PASSWORD\" mysql -uroot $database < $temp_file" 2>/dev/null
    docker exec "$container_name" rm -f "$temp_file" 2>/dev/null
  else
    docker exec -i -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" "$container_name" mysql -uroot "$database" < "$sql_file" 2>/dev/null
  fi
}

# ============================================
# Bring up stack (Portal dir contains the compose)
# ============================================
echo ""
echo "🐳 Starting Docker containers (MySQL + Portal PHP + SaaS PHP + PHPMyAdmin + n8n)..."
echo ""
cd "$PORTAL_DIR"

# FULL RESET: stop & remove
$DOCKER_COMPOSE down -v --remove-orphans || true
# Rebuild & up
$DOCKER_COMPOSE up -d --build

echo ""
echo "⏳ Waiting for MySQL to be ready..."
echo ""
sleep 5

# Wait for MySQL healthy
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

# ============================================
# Portal DB reset + base grants
# ============================================
echo ""
echo "📊 Creating Portal database..."
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "DROP DATABASE IF EXISTS CandyHirePortal;" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE DATABASE CandyHirePortal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE USER IF NOT EXISTS 'candyhire_portal_user'@'%' IDENTIFIED BY 'candyhire_portal_pass';" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "GRANT ALL PRIVILEGES ON CandyHirePortal.* TO 'candyhire_portal_user'@'%';" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE USER IF NOT EXISTS 'candyhire_user'@'%' IDENTIFIED BY 'CandyH1re_S3cur3P@ss!';" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "GRANT SELECT ON CandyHirePortal.* TO 'candyhire_user'@'%';" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "FLUSH PRIVILEGES;" 2>/dev/null
echo "✅ Portal database created"

echo ""
echo "🏗️  Applying Portal schema..."
import_sql_file "migration/01_schema.sql" "CandyHirePortal"
echo "✅ Portal schema created"

echo ""
echo "🎯 Inserting Portal initial data (admin, countries)..."
ADMIN_PASSWORD_HASH=$(docker exec candyhire-portal-php php -r "echo password_hash('Admin123!', PASSWORD_BCRYPT);")
import_sql_file "migration/02_initial_data.sql" "CandyHirePortal" || echo "⚠️  Initial data already exists or failed to insert"
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot CandyHirePortal -e "UPDATE admin_users SET password_hash = '$ADMIN_PASSWORD_HASH' WHERE email = 'admin@candyhire.com';" 2>/dev/null
echo "✅ Portal initial data imported"

echo ""
echo "💎 Creating subscription tiers..."
import_sql_file "migration/04_subscription_tiers.sql" "CandyHirePortal" || echo "⚠️  Subscription tiers already exist or failed to insert"
echo "✅ Subscription tiers created"

# ============================================
# n8n DB reset + container
# ============================================
echo ""
echo "🔧 Setting up n8n..."
docker stop candyhire-n8n 2>/dev/null || true
docker rm candyhire-n8n 2>/dev/null || true
docker volume rm candyhire-n8n-data candyhire-n8n-files 2>/dev/null || true

docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "SET GLOBAL log_bin_trust_function_creators = 1;" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "DROP DATABASE IF EXISTS n8n;" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE DATABASE n8n CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE USER IF NOT EXISTS 'n8n_user'@'%' IDENTIFIED BY 'n8n_secure_pass';" 2>/dev/null
docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "GRANT ALL PRIVILEGES ON n8n.* TO 'n8n_user'@'%'; FLUSH PRIVILEGES;" 2>/dev/null
echo "   ✓ n8n database created (clean)"

$DOCKER_COMPOSE up -d n8n
echo "   Waiting for n8n to initialize..."
for i in {1..60}; do
  if docker logs candyhire-n8n 2>&1 | grep -q "Editor is now accessible"; then
    echo "   ✓ n8n is ready"
    break
  fi
  if docker logs candyhire-n8n 2>&1 | grep -q "There was an error running database migrations"; then
    echo "   ⚠️  n8n migration error detected, restarting container..."
    docker stop candyhire-n8n 2>/dev/null
    docker rm candyhire-n8n 2>/dev/null
    docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "DROP DATABASE IF EXISTS n8n; CREATE DATABASE n8n CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
    $DOCKER_COMPOSE up -d n8n
    sleep 10
    continue
  fi
  [ $i -eq 60 ] && echo "   ⚠️  n8n taking longer than expected..."
  sleep 2
done
sleep 5

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
  echo "   ✓ n8n admin user created"
else
  echo "   ⚠️  Using CLI fallback..."
  docker exec candyhire-n8n n8n user-management:reset \
    --email="$N8N_ADMIN_EMAIL" \
    --password="$N8N_ADMIN_PASSWORD" \
    --firstName="$N8N_ADMIN_FIRSTNAME" \
    --lastName="$N8N_ADMIN_LASTNAME" 2>&1 | grep -v "Permissions" | head -1
fi
echo "✅ n8n setup completed"

# Import n8n workflow Ollama (automatic setup)
echo ""
echo "📋 Importing n8n Ollama workflow..."
if [ -f "setup-n8n-complete.sh" ]; then
  bash ./setup-n8n-complete.sh
  echo "✅ n8n workflow imported and activated"
else
  echo "⚠️  setup-n8n-complete.sh not found, skipping workflow import"
  echo "   You can manually run: ./setup-n8n-complete.sh"
fi

# ============================================
# Ollama (local AI) – install & expose
# ============================================
echo ""
echo "🤖 Setting up Ollama (Local AI)..."
OLLAMA_INSTALLED=false
if ! command -v ollama &>/dev/null; then
  echo "   Installing Ollama..."
  if curl -fsSL https://ollama.com/install.sh | sh; then
    OLLAMA_INSTALLED=true
  else
    echo "   ⚠️  Ollama installation failed or skipped."
  fi
else
  OLLAMA_INSTALLED=true
fi

if [ "$OLLAMA_INSTALLED" = true ]; then
  echo "   Configuring Ollama host..."
  if systemctl list-unit-files | grep -q "ollama.service"; then
    sudo mkdir -p /etc/systemd/system/ollama.service.d
    echo '[Service]
Environment="OLLAMA_HOST=0.0.0.0:11434"' | sudo tee /etc/systemd/system/ollama.service.d/override.conf >/dev/null
    sudo systemctl daemon-reload
    sudo systemctl restart ollama
  else
    killall ollama 2>/dev/null || true
    sleep 2
    OLLAMA_HOST=0.0.0.0:11434 ollama serve >/dev/null 2>&1 &
    sleep 3
  fi

  sleep 2
  if ss -tuln | grep -q "0.0.0.0:11434"; then
    echo "   ✓ Ollama listening on 0.0.0.0:11434"
  else
    echo "   ⚠️  Could not verify Ollama bind"
  fi

  echo "   Checking model qwen2.5:7b..."
  if ! ollama list | grep -q "qwen2.5:7b"; then
    echo "   Pulling model qwen2.5:7b (may take time)..."
    ollama pull qwen2.5:7b || echo "   ⚠️  Model pull failed"
  else
    echo "   ✓ Model already present"
  fi
else
  echo "⏭️  Skipping Ollama setup."
fi

# ============================================
# Tenant creation (FULL RESET)
# ============================================
echo ""
echo "🏗️  Tenant Database Configuration"
read -t 5 -p "How many tenant databases to create? (1-1000, default: 10): " TENANT_COUNT || true
echo ""
if [ -z "$TENANT_COUNT" ]; then
  TENANT_COUNT=10
  echo "   ⏱️  Using default: $TENANT_COUNT"
elif ! [[ "$TENANT_COUNT" =~ ^[0-9]+$ ]] || [ "$TENANT_COUNT" -lt 1 ] || [ "$TENANT_COUNT" -gt 1000 ]; then
  echo "❌ Invalid number. Must be 1..1000."
  exit 1
else
  echo "   ✅ Creating $TENANT_COUNT tenants"
fi

TENANT_SCHEMA="migration/tenant_schema/schema.sql"
TENANT_INITIAL_DATA="migration/tenant_schema/initial_data.sql"
TENANT_POOL_SQL="migration/generated_tenant_pool.sql"

echo "📋 Generating tenant pool SQL for $TENANT_COUNT tenants..."
docker exec candyhire-portal-php php -r "
\$tenantCount = $TENANT_COUNT;
\$itemsPerLine = 10;
\$out = \"-- Tenant Pool (\$tenantCount)\\nTRUNCATE TABLE \\\`tenant_pool\\\`;\\nINSERT INTO \\\`tenant_pool\\\` (\\\`tenant_id\\\`, \\\`is_available\\\`) VALUES\\n\";
for (\$i=1; \$i<=\$tenantCount; \$i++){
  \$out .= \"(\$i, TRUE)\" . (\$i<\$tenantCount ? ( (\$i%10==0)?\",\n\":\", \") : \";\\n\" );
}
file_put_contents('/tmp/tenant_pool.sql', \$out);
" 2>/dev/null
docker cp candyhire-portal-php:/tmp/tenant_pool.sql "$TENANT_POOL_SQL" 2>/dev/null
docker exec candyhire-portal-php rm -f /tmp/tenant_pool.sql 2>/dev/null
echo "✅ Tenant pool SQL generated"

if [ ! -f "$TENANT_SCHEMA" ]; then
  echo "⚠️  Tenant schema not found at $TENANT_SCHEMA – skipping tenant creation."
else
  echo "🧹 Dropping existing tenant DBs..."
  existing_dbs=$(docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -N -e "SHOW DATABASES LIKE 'candyhire_tenant_%';" || true)
  if [ -n "$existing_dbs" ]; then
    echo "$existing_dbs" | while read dbn; do
      [ -n "$dbn" ] && docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "DROP DATABASE IF EXISTS \`$dbn\`;" 2>/dev/null
    done
    echo "✅ Old tenant DBs dropped"
  else
    echo "   No tenant DBs to drop"
  fi

  echo "📦 Creating $TENANT_COUNT tenant DBs..."
  success_count=0
  for i in $(seq 1 $TENANT_COUNT); do
    DB_NAME="candyhire_tenant_$i"
    (( i % 10 == 1 )) && echo "  ... $i/$TENANT_COUNT"
    docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
    import_sql_file "$TENANT_SCHEMA" "$DB_NAME"
    if [ -f "$TENANT_INITIAL_DATA" ]; then
      TEMP_INIT="migration/generated_tenant_${i}_initial_data.sql"
      sed "s/{{TENANT_ID}}/$i/g" "$TENANT_INITIAL_DATA" > "$TEMP_INIT"
      import_sql_file "$TEMP_INIT" "$DB_NAME"
      rm -f "$TEMP_INIT"
    fi
    success_count=$((success_count+1))
  done
  echo "✅ Created $success_count/$TENANT_COUNT tenant DBs"

  echo "🔐 Granting permissions to candyhire_user on tenant DBs..."
  docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" candyhire-portal-mysql mysql -uroot -e "GRANT ALL PRIVILEGES ON \`candyhire_tenant_%\`.* TO 'candyhire_user'@'%'; FLUSH PRIVILEGES;" 2>/dev/null
  echo "✅ Permissions granted"

  echo "📊 Populating tenant_pool..."
  import_sql_file "$TENANT_POOL_SQL" "CandyHirePortal"
  echo "✅ Tenant pool populated"
fi

# ============================================
# Post-check: verify Attach mount is visible inside SaaS
# ============================================
echo ""
echo "🔎 Verifying Attach mount inside SaaS container..."
if docker exec candyhire-saas-php bash -lc 'ls -ld /var/www/html/Attach && test -w /var/www/html/Attach && echo WRITABLE'; then
  echo "   ✓ Attach is visible & writable inside container"
else
  echo "   ⚠️  Attach not writable inside container."
  echo "   Ensure docker-compose for saas-php includes:"
  echo "     - $HOME/candyhire-data/attachments:/var/www/html/Attach"
fi

# ============================================
# Final summary
# ============================================
echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║       CandyHire - FULL RESET SETUP COMPLETED                  ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📡 PORTAL (Registration & Payment)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Frontend:           http://localhost:4200"
echo "API Backend:        http://localhost:8082"
echo "PHPMyAdmin:         http://localhost:8083"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📡 SAAS (Multi-Tenant App)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Frontend:           http://localhost:4202"
echo "API Backend:        http://localhost:8080"
echo "Attachments (host): $ATTACH_DIR"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔄 n8n"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Web Interface:      http://localhost:5678"
echo "Email:              admin@candyhire.local"
echo "Password:           Admin123456"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🤖 Ollama"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Endpoint:           http://localhost:11434"
echo "Model:              qwen2.5:7b"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔐 MYSQL (shared)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Host:               localhost:3306"
echo "DB:                 CandyHirePortal"
echo "User:               candyhire_portal_user"
echo "Pass:               candyhire_portal_pass"
echo "Root:               root / $MYSQL_ROOT_PASSWORD"
echo ""
echo "Happy coding! 🚀"
