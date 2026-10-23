#!/usr/bin/env bash
# Quick, read-only checks against a running site. Usage: APP_URL=https://… scripts/smoke.sh
# Retries for up to ~2 minutes because Render's free instance sleeps and needs time to wake up.
set -uo pipefail

URL="${APP_URL:?set APP_URL, e.g. https://partsdesk.onrender.com}"
URL="${URL%/}"

for attempt in $(seq 1 12); do
  body=$(curl -s --max-time 30 "$URL/health")
  if echo "$body" | grep -q '"status":"ok"'; then
    echo "✔ /health: $body"
    break
  fi
  echo "  waiting for the site to wake up (attempt $attempt): ${body:-no answer}"
  sleep 10
  if [ "$attempt" = 12 ]; then echo "✘ /health never reported ok"; exit 1; fi
done

# Follow redirects: a brand-new site with no accounts sends /login to /setup, which is correct
result=$(curl -s -L -o /tmp/smoke-login.html -w '%{http_code} %{url_effective}' --max-time 30 "$URL/login")
code=${result%% *}
landed=${result#* }
if [ "$code" = 200 ] && grep -q 'PARTSDESK' /tmp/smoke-login.html; then
  case "$landed" in
    */setup) echo "✔ /login redirects to /setup (no accounts yet: open the site and create the first Admin)" ;;
    *) echo "✔ /login loads" ;;
  esac
else
  echo "✘ /login ended at $landed with HTTP $code"; exit 1
fi

for secret in /.env /composer.json /vendor/autoload.php /src/Core/App.php; do
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 30 "$URL$secret")
  if [ "$code" = 200 ]; then echo "✘ $secret is publicly reachable"; exit 1; fi
done
echo "✔ source files and .env are not reachable"
