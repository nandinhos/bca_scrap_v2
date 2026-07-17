#!/bin/sh

set -eu

app_root="${APP_ROOT:-/var/www/html}"
app_uid="${APP_UID:-1000}"
app_gid="${APP_GID:-1000}"
storage_root="$app_root/storage"
public_storage="$storage_root/app/public"
public_link="$app_root/public/storage"

mkdir -p \
    "$public_storage/bcas" \
    "$storage_root/app/private" \
    "$storage_root/framework/cache/data" \
    "$storage_root/framework/sessions" \
    "$storage_root/framework/views" \
    "$storage_root/logs" \
    "$app_root/bootstrap/cache" \
    "$app_root/public"

# A aplicação roda como UID/GID 1000. Diretórios internos permanecem privados;
# apenas a árvore pública precisa ser legível pelo nginx.
chown -R "$app_uid:$app_gid" "$storage_root" "$app_root/bootstrap/cache"
find "$storage_root" "$app_root/bootstrap/cache" -type d -exec chmod 0770 {} \;
find "$storage_root" "$app_root/bootstrap/cache" -type f -exec chmod 0660 {} \;

chmod 0775 "$storage_root" "$storage_root/app" "$public_storage"
find "$public_storage" -type d -exec chmod 0775 {} \;
find "$public_storage" -type f -exec chmod 0664 {} \;

# Um link relativo funciona no host e em todos os containers que montam o projeto.
if [ -e "$public_link" ] && [ ! -L "$public_link" ]; then
    echo "ERRO: $public_link existe e não é um link simbólico." >&2
    exit 1
fi

if [ -L "$public_link" ]; then
    rm -f "$public_link"
fi

ln -s ../storage/app/public "$public_link"
chown -h "$app_uid:$app_gid" "$public_link" 2>/dev/null || true

if [ ! -d "$public_link" ]; then
    echo "ERRO: link simbólico de storage não aponta para um diretório válido." >&2
    exit 1
fi

echo "Storage preparado: permissões e link simbólico validados."
