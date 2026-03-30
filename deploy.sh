#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="$ROOT_DIR/.deploy.env"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Arquivo .deploy.env nao encontrado."
  echo "Copie .deploy.env.example para .deploy.env e preencha os dados."
  exit 1
fi

set -a
# shellcheck disable=SC1090
source "$ENV_FILE"
set +a

: "${DEPLOY_HOST:?DEPLOY_HOST obrigatorio}"
: "${DEPLOY_USER:?DEPLOY_USER obrigatorio}"
: "${DEPLOY_KEY_PATH:?DEPLOY_KEY_PATH obrigatorio}"

DEPLOY_PORT="${DEPLOY_PORT:-22}"
DEPLOY_REMOTE_DIR="${DEPLOY_REMOTE_DIR:-/home/${DEPLOY_USER}/public_html}"
DEPLOY_LOCAL_DIR="${DEPLOY_LOCAL_DIR:-$ROOT_DIR/}"
DEPLOY_DELETE="${DEPLOY_DELETE:-0}"

SSH_CMD=(ssh -i "$DEPLOY_KEY_PATH" -p "$DEPLOY_PORT")
RSYNC_DELETE_FLAG=()
if [[ "$DEPLOY_DELETE" == "1" ]]; then
  RSYNC_DELETE_FLAG=(--delete)
fi

echo "[1/3] Testando conexao SSH..."
"${SSH_CMD[@]}" "$DEPLOY_USER@$DEPLOY_HOST" "echo SSH_OK"

echo "[2/3] Garantindo diretorio remoto..."
"${SSH_CMD[@]}" "$DEPLOY_USER@$DEPLOY_HOST" "mkdir -p '$DEPLOY_REMOTE_DIR'"

echo "[3/3] Enviando arquivos via rsync..."
rsync -az --progress "${RSYNC_DELETE_FLAG[@]}" \
  --exclude ".git/" \
  --exclude "node_modules/" \
  --exclude ".DS_Store" \
  --exclude "*.log" \
  --exclude "index_temp_restore.php" \
  --exclude "index_backup_before_jogos_fallback.php" \
  -e "ssh -i $DEPLOY_KEY_PATH -p $DEPLOY_PORT" \
  "$DEPLOY_LOCAL_DIR" "$DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_REMOTE_DIR/"

echo "Deploy concluido com sucesso."
