#!/usr/bin/env bash
# Test mdviewer endpoint

echo "Testing mdviewer API..."

# Test API
response=$(curl -s "http://localhost:8082/api/mdviewer/data?page=1&perPage=5")

# Check if valid JSON with expected fields
if echo "$response" | jq -e '.data and .total and .page' > /dev/null 2>&1; then
    echo "✅ API OK"
    echo "$response" | jq '.'
    exit 0
else
    echo "❌ API FAILED"
    echo "$response"
    exit 1
fi
