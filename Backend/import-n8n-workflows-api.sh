#!/bin/bash

# ============================================
# n8n Workflow Import Script (using REST API)
# ============================================
# This script imports workflows from JSON files to n8n via REST API

N8N_URL="http://localhost:5678"
N8N_EMAIL="admin@candyhire.local"
N8N_PASSWORD="Admin123456"
WORKFLOWS_DIR="n8n_workflows"
COOKIE_FILE="/tmp/n8n-cookies.txt"

echo "📥 Importing n8n workflows via API..."

# Check if workflows directory exists and has JSON files
if [ ! -d "$WORKFLOWS_DIR" ] || [ -z "$(ls -A $WORKFLOWS_DIR/*.json 2>/dev/null)" ]; then
    echo "   ℹ️  No workflows to import (directory empty or doesn't exist)"
    return 0 2>/dev/null || exit 0
fi

# Count workflows
workflow_count=$(ls -1 $WORKFLOWS_DIR/*.json 2>/dev/null | wc -l)

if [ $workflow_count -eq 0 ]; then
    echo "   ℹ️  No workflow files found"
    return 0 2>/dev/null || exit 0
fi

echo "   Found $workflow_count workflow(s) to import"

# Wait for n8n to be ready
echo "   Waiting for n8n API to be ready..."
for i in {1..30}; do
    if curl -s -f "$N8N_URL/healthz" > /dev/null 2>&1; then
        echo "   ✓ n8n API is ready"
        break
    fi
    if [ $i -eq 30 ]; then
        echo "   ❌ Timeout waiting for n8n API"
        return 1 2>/dev/null || exit 1
    fi
    sleep 2
done

# Login to n8n to get authentication cookie
echo "   Authenticating with n8n..."
rm -f "$COOKIE_FILE"
login_response=$(curl -s -c "$COOKIE_FILE" -X POST "$N8N_URL/rest/login" \
    -H "Content-Type: application/json" \
    -d "{\"emailOrLdapLoginId\":\"$N8N_EMAIL\",\"password\":\"$N8N_PASSWORD\"}" 2>&1)

if [ ! -f "$COOKIE_FILE" ]; then
    echo "   ⚠️  Warning: Could not authenticate with n8n"
    echo "   Attempting to import without authentication..."
fi

# Import each workflow using n8n REST API
imported=0
skipped=0
failed=0

for workflow_file in $WORKFLOWS_DIR/*.json; do
    if [ -f "$workflow_file" ]; then
        filename=$(basename "$workflow_file")

        # Extract workflow name from JSON
        workflow_name=$(python3 -c "
import json
import sys
try:
    with open('$workflow_file', 'r') as f:
        data = json.load(f)
        print(data.get('name', 'Unknown'))
except Exception as e:
    print('Unknown')
" 2>/dev/null || echo "$filename")

        # Read the workflow JSON
        workflow_json=$(cat "$workflow_file")

        # Import via n8n REST API (internal endpoint, not public API)
        # Use /rest/workflows instead of /api/v1/workflows for cookie-based auth
        response=$(curl -s -w "\n%{http_code}" -b "$COOKIE_FILE" -X POST "$N8N_URL/rest/workflows" \
            -H "Content-Type: application/json" \
            -d "$workflow_json" 2>&1)

        # Extract HTTP status code (last line)
        http_code=$(echo "$response" | tail -n 1)
        # Extract response body (everything except last line)
        response_body=$(echo "$response" | sed '$d')

        # Check if import was successful
        if [ "$http_code" = "200" ] || [ "$http_code" = "201" ]; then
            echo "   ✅ Imported: $workflow_name"
            imported=$((imported + 1))
        elif echo "$response_body" | grep -q "already exists\|duplicate"; then
            echo "   ⏭️  Skipped (already exists): $workflow_name"
            skipped=$((skipped + 1))
        else
            echo "   ❌ Failed to import $workflow_name (HTTP $http_code)"
            if [ ! -z "$response_body" ]; then
                echo "      Error: $response_body" | head -c 200
                echo ""
            fi
            failed=$((failed + 1))
        fi
    fi
done

echo ""
if [ $imported -gt 0 ]; then
    echo "✅ Successfully imported $imported workflow(s)"
fi
if [ $skipped -gt 0 ]; then
    echo "   ℹ️  Skipped $skipped existing workflow(s)"
fi
if [ $failed -gt 0 ]; then
    echo "   ⚠️  Failed to import $failed workflow(s)"
fi
echo ""

# Clean up cookie file
rm -f "$COOKIE_FILE"

return 0 2>/dev/null || exit 0
