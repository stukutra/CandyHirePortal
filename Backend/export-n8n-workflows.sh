#!/bin/bash

# ============================================
# n8n Workflow Export Script
# ============================================
# This script exports all workflows from n8n database to JSON files

set -e

MYSQL_ROOT_PASSWORD='CandyHire2024Root'
WORKFLOWS_DIR="n8n_workflows"

echo "========================================" echo "n8n Workflow Export"
echo "========================================"
echo ""

# Create workflows directory if it doesn't exist
mkdir -p "$WORKFLOWS_DIR"

# Export workflows from database
echo "📦 Exporting workflows from n8n database..."

# Get all workflows as JSON
WORKFLOWS=$(docker exec candyhire-portal-mysql bash -c "MYSQL_PWD=\"$MYSQL_ROOT_PASSWORD\" mysql -uroot n8n -N -e \"SELECT id, name, nodes, connections, settings FROM workflow WHERE active=1 OR active=0;\" --batch" 2>/dev/null)

if [ -z "$WORKFLOWS" ]; then
    echo "⚠️  No workflows found in database"
    exit 0
fi

# Export each workflow to JSON file
count=0
while IFS=$'\t' read -r id name nodes connections settings; do
    # Sanitize filename
    filename=$(echo "$name" | tr ' ' '_' | tr -cd '[:alnum:]_-')
    filepath="$WORKFLOWS_DIR/${id}_${filename}.json"

    # Create JSON structure
    cat > "$filepath" << EOF
{
  "id": $id,
  "name": "$name",
  "nodes": $nodes,
  "connections": $connections,
  "settings": $settings
}
EOF

    echo "  ✅ Exported: $name -> $filepath"
    count=$((count + 1))
done <<< "$WORKFLOWS"

echo ""
echo "✅ Exported $count workflow(s) to $WORKFLOWS_DIR/"
echo ""
echo "Don't forget to commit these files to Git!"
