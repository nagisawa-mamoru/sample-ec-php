#!/bin/sh
set -e

if [ -f composer.json ]; then
    composer install --no-interaction --no-progress
fi

# Windowsのbind mountではホスト側の所有者情報が引き継がれ、
# www-data(php-fpmのワーカーユーザー)がwritable/配下に書き込めないことがあるため、
# 開発環境向けに書き込み権限を緩めておく。
if [ -d writable ]; then
    chmod -R 777 writable
fi

exec "$@"
