# GUIDE DE DÉPLOIEMENT KAKEIBO (RSYNC + RELEASES)

## 1. STRUCTURE SUR LE SERVEUR

```bash
/home/ton_user/
├── apps/
│   └── kakeibo/
│       ├── .env             (Fichier prod maître)
│       ├── .htaccess        (Config Apache maître)
│       ├── php.ini          (Config PHP maître)
│       ├── current          (Lien vers la release active)
│       └── releases/        (Dossiers datés)
└── kakeibo/                 (Lien symbolique vers apps/kakeibo/current)
```

## 2. FICHIER : .rsync-exclude

```bash
.git/
.deploy-config
deploy.sh
/vendor/
/var/cache/
/node_modules/
/assets/
.env.local
.env.*.local
.htaccess
php.ini
```

## 3. FICHIER : .deploy-config (Sur ton PC)

```conf
SSH_USER="ton_utilisateur"
SSH_HOST="ton_serveur.o2switch.net"
REMOTE_BASE_DIR="/home/$SSH_USER/apps/kakeibo"
FINAL_LINK="/home/$SSH_USER/kakeibo"
KEEP=5
```

## 4. FICHIER : deploy.sh (Sur ton PC)

```bash
#!/bin/bash
if [ -f .deploy-config ]; then source .deploy-config; else echo "❌ Config manquante"; exit 1; fi

RELEASE_NAME=$(date +%Y%m%d_%H%M%S)
RELEASE_PATH="$REMOTE_BASE_DIR/releases/$RELEASE_NAME"

echo "🛠️ 1. Build local..."
yarn run build

echo "🔍 2. Recherche version précédente..."
LAST_RELEASE=$(ssh $SSH_USER@$SSH_HOST "ls -1dt $REMOTE_BASE_DIR/releases/* 2>/dev/null | head -n 1")

echo "📦 3. Transfert rsync (Hard Links)..."
ssh $SSH_USER@$SSH_HOST "mkdir -p $RELEASE_PATH"
rsync -avz --delete ${LAST_RELEASE:+--link-dest="$LAST_RELEASE"} --exclude-from=".rsync-exclude" ./ $SSH_USER@$SSH_HOST:$RELEASE_PATH

echo "🧹 4. Liens symboliques internes..."
ssh $SSH_USER@$SSH_HOST "rm -rf $RELEASE_PATH/var/cache/prod && \
    ln -sfn $REMOTE_BASE_DIR/.env $RELEASE_PATH/.env && \
    ln -sfn $REMOTE_BASE_DIR/.htaccess $RELEASE_PATH/public/.htaccess && \
    ln -sfn $REMOTE_BASE_DIR/php.ini $RELEASE_PATH/public/php.ini"

echo "🔗 5. Bascule du site..."
ssh $SSH_USER@$SSH_HOST "ln -sfn $RELEASE_PATH $REMOTE_BASE_DIR/current && ln -sfn $REMOTE_BASE_DIR/current $FINAL_LINK"

echo "🗑️ 6. Nettoyage (Gardé: $KEEP)..."
ssh $SSH_USER@$SSH_HOST "cd $REMOTE_BASE_DIR/releases && ls -1tr | head -n -$KEEP | xargs -r rm -rf"

echo "🚀 Terminé !"
```

## 5. COMMANDES D'INITIALISATION (SSH)
```bash
mkdir -p ~/apps/kakeibo/releases
cp ~/kakeibo/.env ~/apps/kakeibo/.env
cp ~/kakeibo/public/.htaccess ~/apps/kakeibo/.htaccess
cp ~/kakeibo/public/php.ini ~/apps/kakeibo/php.ini
rm -rf ~/kakeibo
```
