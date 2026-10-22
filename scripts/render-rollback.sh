#!/usr/bin/env bash
# Rolls the Render service back to an earlier deploy.
# Usage: scripts/render-rollback.sh <deploy-id>
set -euo pipefail

TARGET="${1:?deploy id required}"
API="https://api.render.com/v1"

if curl -fsS -X POST \
  -H "Authorization: Bearer ${RENDER_API_KEY:?}" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d "{\"deployId\": \"${TARGET}\"}" \
  "$API/services/${RENDER_SERVICE_ID:?}/rollback" > /dev/null; then
  echo "Rollback to $TARGET started."
else
  echo "::error::Automatic rollback failed. In the Render dashboard open the service → Events → choose the previous deploy → Rollback."
  exit 1
fi
