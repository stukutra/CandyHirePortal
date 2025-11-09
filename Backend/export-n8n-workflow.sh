#!/bin/bash

# Login to n8n
COOKIE_FILE="/tmp/n8n-export-cookie.txt"
rm -f "$COOKIE_FILE"

curl -s -c "$COOKIE_FILE" -X POST "http://localhost:5678/rest/login" \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@candyhire.local","password":"Admin123456"}' > /dev/null

# Get workflows list
WORKFLOWS=$(curl -s -b "$COOKIE_FILE" "http://localhost:5678/rest/workflows")

# Extract the workflow ID (assuming there's only one)
WORKFLOW_ID=$(echo "$WORKFLOWS" | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

echo "Exporting workflow ID: $WORKFLOW_ID"

# Export the workflow
curl -s -b "$COOKIE_FILE" "http://localhost:5678/rest/workflows/$WORKFLOW_ID" | python3 -m json.tool > n8n_workflows/cv-extract-data-ollama.json

rm -f "$COOKIE_FILE"

echo "✓ Workflow exported to n8n_workflows/cv-extract-data-ollama.json"
