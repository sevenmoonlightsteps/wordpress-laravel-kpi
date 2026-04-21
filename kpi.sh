#!/bin/bash
set -e

COMPOSE="docker compose"
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

cd "$DIR"

case "${1:-}" in
  start)
    echo "Starting KPI Dashboard stack..."
    $COMPOSE up -d
    echo ""
    echo "WordPress → http://localhost:8080"
    echo "Laravel   → http://localhost:8081"
    ;;

  stop)
    echo "Stopping KPI Dashboard stack..."
    $COMPOSE down
    ;;

  status)
    $COMPOSE ps
    ;;

  *)
    echo "Usage: $0 {start|stop|status}"
    exit 1
    ;;
esac
