#!/bin/bash

# ============================================
# n8n Workflow Import Script
# ============================================
# This script imports workflows from JSON files to n8n database

set -e

MYSQL_ROOT_PASSWORD='CandyHire2024Root'
WORKFLOWS_DIR="n8n_workflows"

echo "📥 Importing n8n workflows..."

# Check if workflows directory exists and has JSON files
if [ ! -d "$WORKFLOWS_DIR" ] || [ -z "$(ls -A $WORKFLOWS_DIR/*.json 2>/dev/null)" ]; then
    echo "   ℹ️  No workflows to import (directory empty or doesn't exist)"
    return 0
fi

# Count workflows
workflow_count=$(ls -1 $WORKFLOWS_DIR/*.json 2>/dev/null | wc -l)

if [ $workflow_count -eq 0 ]; then
    echo "   ℹ️  No workflow files found"
    return 0
fi

echo "   Found $workflow_count workflow(s) to import"

# Import each workflow
imported=0
for workflow_file in $WORKFLOWS_DIR/*.json; do
    if [ -f "$workflow_file" ]; then
        filename=$(basename "$workflow_file")

        # Extract workflow data from JSON
        name=$(grep -o '"name": *"[^"]*"' "$workflow_file" | head -1 | sed 's/"name": *"\(.*\)"/\1/')
        nodes=$(grep -o '"nodes": *\[.*\]' "$workflow_file" | sed 's/"nodes": *//')
        connections=$(grep -o '"connections": *{.*}' "$workflow_file" | sed 's/"connections": *//')
        settings=$(grep -o '"settings": *{.*}' "$workflow_file" | sed 's/"settings": *//')

        if [ ! -z "$name" ]; then
            # Check if workflow already exists
            existing=$(docker exec candyhire-portal-mysql bash -c "MYSQL_PWD=\"$MYSQL_ROOT_PASSWORD\" mysql -uroot n8n -N -e \"SELECT id FROM workflow WHERE name='$name' LIMIT 1;\"" 2>/dev/null || echo "")

            if [ -z "$existing" ]; then
                # Insert workflow (simplified - in production use proper JSON handling)
                docker exec candyhire-portal-mysql bash -c "MYSQL_PWD=\"$MYSQL_ROOT_PASSWORD\" mysql -uroot n8n -e \"INSERT INTO workflow (name, active, nodes, connections, settings, createdAt, updatedAt) VALUES ('$name', 1, '$nodes', '$connections', '$settings', NOW(), NOW());\"" 2>/dev/null

                echo "   ✅ Imported: $name"
                imported=$((imported + 1))
            else
                echo "   ⏭️  Skipped (already exists): $name"
            fi
        fi
    fi
done

if [ $imported -gt 0 ]; then
    echo "✅ Imported $imported workflow(s)"
else
    echo "   ℹ️  All workflows already exist"
fi
