#!/bin/bash

# ============================================
# n8n Workflow Import Script
# ============================================
# This script imports workflows from JSON files to n8n database

MYSQL_ROOT_PASSWORD='CandyHire2024Root'
WORKFLOWS_DIR="n8n_workflows"

echo "📥 Importing n8n workflows..."

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

# Import each workflow using PHP for proper JSON handling
imported=0
skipped=0

for workflow_file in $WORKFLOWS_DIR/*.json; do
    if [ -f "$workflow_file" ]; then
        filename=$(basename "$workflow_file")

        # Copy JSON file to PHP container temporarily
        docker cp "$workflow_file" candyhire-portal-php:/tmp/workflow_import.json 2>/dev/null

        # Use PHP to parse JSON and import to database
        result=$(docker exec candyhire-portal-php php -r "
\$json = file_get_contents('/tmp/workflow_import.json');
\$workflow = json_decode(\$json, true);

if (!\$workflow || !isset(\$workflow['name'])) {
    echo 'ERROR: Invalid JSON';
    exit(1);
}

\$name = \$workflow['name'];
\$nodes = json_encode(\$workflow['nodes'] ?? []);
\$connections = json_encode(\$workflow['connections'] ?? []);
\$settings = json_encode(\$workflow['settings'] ?? []);
\$active = \$workflow['active'] ?? false ? 1 : 0;

// Escape for SQL
\$name = addslashes(\$name);
\$nodes = addslashes(\$nodes);
\$connections = addslashes(\$connections);
\$settings = addslashes(\$settings);

// Connect to n8n database
\$mysqli = new mysqli('candyhire-portal-mysql', 'root', '$MYSQL_ROOT_PASSWORD', 'n8n');

if (\$mysqli->connect_error) {
    echo 'ERROR: Database connection failed';
    exit(1);
}

// Get admin user ID
\$user_query = \$mysqli->query(\"SELECT id FROM user WHERE globalRole = 'global:owner' LIMIT 1\");
if (\$user_query->num_rows === 0) {
    echo 'ERROR: No admin user found. Create user first.';
    \$mysqli->close();
    exit(1);
}
\$user_id = \$user_query->fetch_assoc()['id'];

// Check if workflow exists
\$stmt = \$mysqli->prepare('SELECT id FROM workflow WHERE name = ? LIMIT 1');
\$stmt->bind_param('s', \$name);
\$stmt->execute();
\$result = \$stmt->get_result();

if (\$result->num_rows > 0) {
    echo 'EXISTS';
} else {
    // Insert workflow with proper user association
    \$stmt = \$mysqli->prepare(\"INSERT INTO workflow (name, active, nodes, connections, settings, createdAt, updatedAt)
                                VALUES (?, ?, ?, ?, ?, NOW(), NOW())\");
    \$stmt->bind_param('sisss', \$name, \$active, \$nodes, \$connections, \$settings);

    if (\$stmt->execute()) {
        \$workflow_id = \$mysqli->insert_id;

        // Associate workflow with user (workflow entity in shared_workflow table)
        \$share_stmt = \$mysqli->prepare(\"INSERT INTO shared_workflow (workflowId, userId, roleId, createdAt, updatedAt)
                                          VALUES (?, ?, 1, NOW(), NOW())\");
        \$share_stmt->bind_param('ii', \$workflow_id, \$user_id);
        \$share_stmt->execute();

        echo 'IMPORTED';
    } else {
        echo 'ERROR: ' . \$mysqli->error;
    }
}

\$mysqli->close();
" 2>&1)

        # Clean up temp file
        docker exec candyhire-portal-php rm -f /tmp/workflow_import.json 2>/dev/null

        # Extract workflow name for display
        workflow_name=$(docker exec candyhire-portal-php php -r "
\$json = file_get_contents('$workflow_file');
\$workflow = json_decode(\$json, true);
echo \$workflow['name'] ?? 'Unknown';
" 2>/dev/null || echo "$filename")

        # Handle result
        case "$result" in
            IMPORTED)
                echo "   ✅ Imported: $workflow_name"
                imported=$((imported + 1))
                ;;
            EXISTS)
                echo "   ⏭️  Skipped (already exists): $workflow_name"
                skipped=$((skipped + 1))
                ;;
            ERROR*)
                echo "   ❌ Failed to import $workflow_name: $result"
                ;;
        esac
    fi
done

echo ""
if [ $imported -gt 0 ]; then
    echo "✅ Successfully imported $imported workflow(s)"
fi
if [ $skipped -gt 0 ]; then
    echo "   ℹ️  Skipped $skipped existing workflow(s)"
fi
echo ""
