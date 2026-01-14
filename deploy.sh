#!/bin/bash

# Charger la config
if [ -f .deploy-config ]; then
    source .deploy-config
else
    echo "❌ Erreur: .deploy-config manquant."
    exit 1
fi

RELEASE_NAME=$(date +%Y%m%d_%H%M%S)
RELEASE_PATH="$REMOTE_BASE_DIR/releases/$RELEASE_NAME"

echo "🛠️  1. Build des assets CSS & JS ..."
export NODE_OPTIONS=--openssl-legacy-provider
yarn run build; clear
echo "🛠️  1. Build des assets CSS & JS, terminé!"

# Trouver la dernière release sur le serveur pour rsync --link-dest
echo "🔍 2. Recherche de la version précédente pour optimisation..."
LAST_RELEASE=$(ssh $SSH_USER@$SSH_HOST "ls -1dt $REMOTE_BASE_DIR/releases/* 2>/dev/null | head -n 1")

echo "📦 3. Transfert rsync..."
ssh $SSH_USER@$SSH_HOST "mkdir -p $RELEASE_PATH"
rsync -avz --delete --dry-run \
    ${LAST_RELEASE:+--link-dest="$LAST_RELEASE"} \
    --exclude-from=".rsync-exclude" \
    ./ $SSH_USER@$SSH_HOST:$RELEASE_PATH

echo "🧹 4. Configuration release (Cache, .env, htaccess, php.ini)..."
ssh $SSH_USER@$SSH_HOST "rm -rf $RELEASE_PATH/var/cache/prod && \
    ln -sfn $REMOTE_BASE_DIR/.env $RELEASE_PATH/.env && \
    ln -sfn $REMOTE_BASE_DIR/.htaccess $RELEASE_PATH/public/.htaccess && \
    ln -sfn $REMOTE_BASE_DIR/php.ini $RELEASE_PATH/public/php.ini"

echo "🔗 5. Bascule atomique du lien symbolique..."
ssh $SSH_USER@$SSH_HOST "ln -sfn $RELEASE_PATH $REMOTE_BASE_DIR/current && ln -sfn $REMOTE_BASE_DIR/current/public $SYMLINK_PATH"

echo "🗑️  6. Nettoyage des anciennes releases..."
ssh $SSH_USER@$SSH_HOST "cd $REMOTE_BASE_DIR/releases && ls -1tr | head -n -$KEEP | xargs -d '\n' rm -rf --"

echo "🚀 Déploiement terminé ! (Version: $RELEASE_NAME)"
