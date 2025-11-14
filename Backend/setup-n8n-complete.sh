#!/bin/bash

# ============================================
# Setup Completo n8n + Import Workflow Ollama
# ============================================
# Questo script:
# 1. Resetta n8n completamente
# 2. Ricrea il container pulito
# 3. Importa automaticamente il workflow Ollama
# 4. Attiva il workflow
# ============================================

set -e

N8N_URL="http://localhost:5678"
WORKFLOW_CV="n8n_workflows/cv-extract-data-ollama.json"
WORKFLOW_MATCHING="n8n_workflows/ai-candidate-matching.json"

echo "=========================================="
echo "Setup Completo n8n + Workflows"
echo "=========================================="
echo ""

# STEP 1: Reset n8n
echo "Step 1/6: Stopping and removing n8n container..."
docker-compose stop n8n 2>/dev/null || true
docker-compose rm -f n8n 2>/dev/null || true

echo "Step 2/6: Removing n8n volumes (full reset)..."
docker volume rm -f candyhire-n8n-data 2>/dev/null || true
docker volume rm -f candyhire-n8n-files 2>/dev/null || true

echo "Step 3/6: Recreating n8n container..."
docker-compose up -d n8n

# STEP 2: Wait for n8n
echo "Step 4/6: Waiting for n8n to be ready..."
MAX_RETRIES=30
RETRY_COUNT=0

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if curl -s "$N8N_URL/healthz" > /dev/null 2>&1; then
        echo "✓ n8n is ready!"
        break
    fi
    echo "  Waiting... ($((RETRY_COUNT+1))/$MAX_RETRIES)"
    sleep 2
    RETRY_COUNT=$((RETRY_COUNT+1))
done

if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
    echo "✗ n8n failed to start"
    exit 1
fi

# STEP 3: Create owner account
echo ""
echo "Step 5/6: Creating owner account..."
curl -s -X POST "$N8N_URL/rest/owner/setup" \
    -H "Content-Type: application/json" \
    -d '{
        "firstName": "Admin",
        "lastName": "CandyHire",
        "email": "admin@candyhire.local",
        "password": "Admin123456"
    }' > /dev/null 2>&1
echo "✓ Owner created (admin@candyhire.local / Admin123456)"
sleep 3

# STEP 4: Login
echo ""
echo "Step 6/6: Importing and activating workflow..."
COOKIE_FILE="/tmp/n8n-cookie.txt"
rm -f "$COOKIE_FILE"

LOGIN_RESPONSE=$(curl -s -c "$COOKIE_FILE" -X POST "$N8N_URL/rest/login" \
    -H "Content-Type: application/json" \
    -d '{
        "emailOrLdapLoginId": "admin@candyhire.local",
        "password": "Admin123456"
    }')

if [ ! -f "$COOKIE_FILE" ] || ! grep -q "n8n-auth" "$COOKIE_FILE"; then
    echo "✗ Login failed"
    exit 1
fi

# STEP 5: Import workflows (active)
echo ""
echo "Importing workflows..."

# Import CV Extract workflow
if [ ! -f "$WORKFLOW_CV" ]; then
    echo "✗ CV workflow file not found: $WORKFLOW_CV"
    exit 1
fi

WORKFLOW_JSON=$(cat "$WORKFLOW_CV" | sed 's/"active":\s*false/"active": true/g')
IMPORT_RESPONSE=$(curl -s -b "$COOKIE_FILE" -X POST "$N8N_URL/rest/workflows" \
    -H "Content-Type: application/json" \
    -d "$WORKFLOW_JSON")
WORKFLOW_ID_CV=$(echo "$IMPORT_RESPONSE" | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

if [ -n "$WORKFLOW_ID_CV" ]; then
    echo "✓ CV Extract workflow imported (ID: $WORKFLOW_ID_CV)"
else
    echo "✗ Failed to import CV Extract workflow"
fi

# Import AI Candidate Matching workflow
if [ ! -f "$WORKFLOW_MATCHING" ]; then
    echo "✗ Matching workflow file not found: $WORKFLOW_MATCHING"
    exit 1
fi

WORKFLOW_JSON=$(cat "$WORKFLOW_MATCHING" | sed 's/"active":\s*false/"active": true/g')
IMPORT_RESPONSE=$(curl -s -b "$COOKIE_FILE" -X POST "$N8N_URL/rest/workflows" \
    -H "Content-Type: application/json" \
    -d "$WORKFLOW_JSON")
WORKFLOW_ID_MATCHING=$(echo "$IMPORT_RESPONSE" | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

rm -f "$COOKIE_FILE"

if [ -n "$WORKFLOW_ID_MATCHING" ]; then
    echo "✓ AI Candidate Matching workflow imported (ID: $WORKFLOW_ID_MATCHING)"
else
    echo "✗ Failed to import AI Candidate Matching workflow"
    exit 1
fi

echo ""
echo "=========================================="
echo "Setup Completato! 🎉"
echo "=========================================="
echo ""
echo "Webhooks attivi:"
echo "  1. CV Extract: POST http://localhost:5678/webhook/upload-cv"
echo "  2. AI Matching: POST http://localhost:5678/webhook/ai-match-candidates"
echo ""
echo "n8n UI:"
echo "  URL: http://localhost:5678"
echo "  Email: admin@candyhire.local"
echo "  Password: Admin123456"
echo ""
echo "Test CV webhook:"
echo "  curl -X POST http://localhost:5678/webhook/upload-cv \\"
echo "    -H 'Content-Type: application/json' \\"
echo "    -d '{\"fileName\":\"test.pdf\",\"fileContent\":\"dGVzdA==\"}'"
echo ""
echo "Test AI Matching webhook:"
echo "  curl -X POST http://localhost:5678/webhook/ai-match-candidates \\"
echo "    -H 'Content-Type: application/json' \\"
echo "    -d '{\"tenant_id\":\"1\",\"job\":{\"id\":\"1\"},\"config\":{\"maxCandidates\":10}}'"
echo ""
