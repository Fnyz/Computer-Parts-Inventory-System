#!/usr/bin/env bash
# Deploys a commit to Render through its API and waits until it is live.
# Usage: scripts/render-deploy.sh <commit-sha>
# Needs RENDER_API_KEY and RENDER_SERVICE_ID. Writes previous_deploy_id to $GITHUB_OUTPUT for the rollback job.
set -euo pipefail

COMMIT="${1:?commit sha required}"
API="https://api.render.com/v1"
AUTH=(-H "Authorization: Bearer ${RENDER_API_KEY:?}" -H "Accept: application/json")
SERVICE="${RENDER_SERVICE_ID:?}"

# The deploy that is live right now: what we roll back to if the new one fails its smoke test
previous=$(curl -fsS "${AUTH[@]}" "$API/services/$SERVICE/deploys?limit=20" \
  | jq -r '[.[] | (.deploy // .) | select(.status == "live")][0].id // empty')
echo "Currently live: ${previous:-none}"
echo "previous_deploy_id=${previous}" >> "${GITHUB_OUTPUT:-/dev/null}"

deploy=$(curl -fsS "${AUTH[@]}" -H "Content-Type: application/json" -X POST \
  -d "{\"commitId\": \"${COMMIT}\", \"clearCache\": \"do_not_clear\"}" \
  "$API/services/$SERVICE/deploys" | jq -r '(.deploy // .).id')
echo "Started deploy $deploy for ${COMMIT:0:7}"

# Free instances build slowly; allow up to 20 minutes
for _ in $(seq 1 120); do
  status=$(curl -fsS "${AUTH[@]}" "$API/services/$SERVICE/deploys/$deploy" | jq -r '(.deploy // .).status')
  case "$status" in
    live) echo "Deploy $deploy is live."; exit 0 ;;
    build_failed | update_failed | pre_deploy_failed | canceled | deactivated)
      echo "::error::Render deploy $deploy ended with status $status. The previous version is still live."
      exit 1 ;;
    *) echo "  status: $status"; sleep 10 ;;
  esac
done
echo "::error::Timed out waiting for Render deploy $deploy."
exit 1
