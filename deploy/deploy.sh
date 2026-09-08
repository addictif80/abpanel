#!/usr/bin/env bash
# Script de déploiement ABPanel
# Usage: bash deploy/deploy.sh

set -e

echo "==> Mise à jour du code"
git pull origin main

echo "==> Dépendances Composer"
composer install --no-dev --optimize-autoloader

echo "==> Configuration"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Migrations"
php artisan migrate --force

echo "==> Redémarrage queue"
php artisan queue:restart

echo "==> Rechargement Supervisor"
supervisorctl reread
supervisorctl update
supervisorctl restart abpanel-queue:*
supervisorctl restart abpanel-scheduler

echo "✓ Déploiement terminé"
