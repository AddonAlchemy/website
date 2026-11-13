#!/usr/bin/env bash

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

export COMPOSER_ALLOW_SUPERUSER=1
export DEBIAN_FRONTEND=noninteractive

NODE_MAJOR=22
PHP_SERIES=8.3
PHP_PACKAGES=(cli mbstring xml curl zip sqlite3 mysql pgsql intl gd)

info() { printf '\033[32m==>\033[0m %s\n' "$1"; }
fail() { printf '\033[31mFehler:\033[0m %s\n' "$1" >&2; exit 1; }

if [ "$(id -u)" -eq 0 ]; then
    sudo_cmd=()
    prefix=/usr/local
else
    sudo_cmd=(sudo)
    prefix="$HOME/.local"
    mkdir -p "$prefix/bin"
fi

export PATH="$prefix/bin:$PATH"

has() { command -v "$1" >/dev/null 2>&1; }

apt_install() {
    has apt-get || fail "Bitte manuell installieren: $*"
    "${sudo_cmd[@]}" apt-get update -qq
    "${sudo_cmd[@]}" apt-get install -y -qq "$@"
}

php_is_current() {
    has php && php -r "exit(version_compare(PHP_VERSION, '${PHP_SERIES}.0', '>=') ? 0 : 1);"
}

add_php_repository() {
    . /etc/os-release

    if [ "${ID:-}" = "ubuntu" ]; then
        apt_install software-properties-common
        "${sudo_cmd[@]}" add-apt-repository -y ppa:ondrej/php
    else
        apt_install ca-certificates curl lsb-release
        "${sudo_cmd[@]}" curl -fsSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg
        echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
            | "${sudo_cmd[@]}" tee /etc/apt/sources.list.d/php.list >/dev/null
    fi
}

ensure_php() {
    if ! php_is_current; then
        info "Installiere PHP ${PHP_SERIES}"
        has apt-get || fail "PHP ${PHP_SERIES} oder neuer wird benötigt."
        apt-cache show "php${PHP_SERIES}-cli" >/dev/null 2>&1 || add_php_repository
        apt_install "${PHP_PACKAGES[@]/#/php${PHP_SERIES}-}"
        php_is_current || fail "PHP ${PHP_SERIES} konnte nicht aktiviert werden."
    fi

    local version missing=()
    version="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"

    for extension in mbstring dom curl zip pdo_sqlite intl fileinfo openssl; do
        php -m | grep -qix "$extension" || missing+=("$extension")
    done

    if [ "${#missing[@]}" -gt 0 ]; then
        info "Installiere fehlende PHP-Erweiterungen: ${missing[*]}"
        apt_install "${PHP_PACKAGES[@]/#/php${version}-}"
    fi
}

ensure_tools() {
    local packages=()

    has curl || packages+=(curl ca-certificates)
    has unzip || packages+=(unzip)
    has git || packages+=(git)

    if [ "${#packages[@]}" -gt 0 ]; then
        info "Installiere Systemwerkzeuge: ${packages[*]}"
        apt_install "${packages[@]}"
    fi
}

ensure_composer() {
    has composer && return

    info "Installiere Composer"

    local setup expected actual
    setup="$(mktemp)"
    expected="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o "$setup"
    actual="$(php -r 'echo hash_file("sha384", $argv[1]);' "$setup")"

    [ "$expected" = "$actual" ] || fail "Die Prüfsumme des Composer-Installers stimmt nicht."

    php "$setup" --quiet --install-dir="$prefix/bin" --filename=composer
    rm -f "$setup"
}

ensure_node() {
    has npm && return

    info "Installiere Node.js ${NODE_MAJOR}"

    local arch base archive checksum download
    case "$(uname -m)" in
        x86_64) arch=x64 ;;
        aarch64 | arm64) arch=arm64 ;;
        *) fail "Nicht unterstützte Architektur: $(uname -m)" ;;
    esac

    base="https://nodejs.org/dist/latest-v${NODE_MAJOR}.x"
    read -r checksum archive < <(curl -fsSL "$base/SHASUMS256.txt" | grep "linux-${arch}.tar.gz$")
    download="$(mktemp)"

    curl -fsSL "$base/$archive" -o "$download"
    echo "$checksum  $download" | sha256sum -c --quiet - || fail "Die Prüfsumme von Node.js stimmt nicht."

    "${sudo_cmd[@]}" tar -xzf "$download" -C "$prefix" --strip-components=1 --exclude='*.md' --exclude='LICENSE'
    rm -f "$download"
}

relocate_for_webserver() {
    local target="/var/www/$domain"

    if "${sudo_cmd[@]}" -u www-data test -r "$PWD/public/index.php" 2>/dev/null || [ "${INSTALL_RELOCATED:-}" = "1" ]; then
        return
    fi

    info "Der Webserver hat keinen Zugriff auf $PWD – kopiere das Projekt nach $target"
    "${sudo_cmd[@]}" mkdir -p "$target"
    "${sudo_cmd[@]}" cp -a . "$target/"

    INSTALL_RELOCATED=1 exec "$target/install.sh" "${original_args[@]}"
}

ensure_port_is_free() {
    if has systemctl && systemctl is-active --quiet apache2 2>/dev/null; then
        fail "Apache belegt bereits Port 80. Bitte zuerst mit 'systemctl disable --now apache2' stoppen und den Installer erneut starten."
    fi
}

restart_service() {
    if has systemctl && [ -d /run/systemd/system ]; then
        "${sudo_cmd[@]}" systemctl enable --quiet "$1"
        "${sudo_cmd[@]}" systemctl restart "$1"
    else
        "${sudo_cmd[@]}" service "$1" restart
    fi
}

setup_webserver() {
    local version fpm site
    version="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
    fpm="php${version}-fpm"
    site="/etc/nginx/sites-available/${domain}.conf"

    info "Richte nginx und PHP-FPM für ${domain} ein"

    printf 'upload_max_filesize = 64M\npost_max_size = 64M\n' \
        | "${sudo_cmd[@]}" tee "/etc/php/${version}/fpm/conf.d/99-marketplace.ini" >/dev/null

    "${sudo_cmd[@]}" tee "$site" >/dev/null <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${domain};
    root ${PWD}/public;
    index index.php;
    charset utf-8;
    client_max_body_size 64m;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ ^/index\.php(/|\$) {
        fastcgi_pass unix:/run/php/${fpm}.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

    "${sudo_cmd[@]}" ln -sf "$site" "/etc/nginx/sites-enabled/${domain}.conf"
    "${sudo_cmd[@]}" rm -f /etc/nginx/sites-enabled/default
    "${sudo_cmd[@]}" chown -R www-data:www-data storage bootstrap/cache database
    "${sudo_cmd[@]}" nginx -t -q || fail "Die nginx-Konfiguration ist ungültig."

    restart_service "$fpm"
    restart_service nginx

    printf '* * * * * www-data cd %s && php artisan schedule:run >/dev/null 2>&1\n' "$PWD" \
        | "${sudo_cmd[@]}" tee /etc/cron.d/marketplace >/dev/null

    if has ufw && "${sudo_cmd[@]}" ufw status | grep -q "Status: active"; then
        "${sudo_cmd[@]}" ufw allow 'Nginx Full' >/dev/null
    fi
}

issue_certificate() {
    local contact=(--register-unsafely-without-email)

    if [ -n "$email" ]; then
        contact=(--email "$email" --no-eff-email)
    fi

    info "Fordere HTTPS-Zertifikat für ${domain} an"

    if "${sudo_cmd[@]}" certbot --nginx --non-interactive --agree-tos --redirect "${contact[@]}" -d "$domain"; then
        info "Fertig: https://${domain} (das Zertifikat wird automatisch verlängert)"
    else
        printf '\033[33mHinweis:\033[0m Das Zertifikat konnte nicht ausgestellt werden. Die Seite läuft vorerst unter http://%s.\n' "$domain"
        printf '         Prüfe, ob der DNS-A-Record der Domain auf diesen Server zeigt und die Ports 80/443 offen sind,\n'
        printf '         und starte dann erneut: certbot --nginx --redirect -d %s\n' "$domain"
    fi
}

original_args=("$@")
artisan_args=()
production=false
demo=false
interactive=true
has_url=false
domain=""
email=""

while [ $# -gt 0 ]; do
    case "$1" in
        --domain=*) domain="${1#*=}" ;;
        --domain) domain="${2:-}"; shift ;;
        --email=*) email="${1#*=}" ;;
        --email) email="${2:-}"; shift ;;
        --production) production=true ;;
        --demo) demo=true; artisan_args+=("$1") ;;
        --no-interaction | -n) interactive=false; artisan_args+=("$1") ;;
        --url | --url=*) has_url=true; artisan_args+=("$1") ;;
        --admin-email=*) [ -n "$email" ] || email="${1#*=}"; artisan_args+=("$1") ;;
        *) artisan_args+=("$1") ;;
    esac
    shift
done

if [ -z "$domain" ] && [ "$interactive" = true ] && [ -t 0 ] && has apt-get; then
    read -r -p "Wollen Sie HTTPS einrichten? [j/N] " answer

    case "$answer" in
        [jJyY]*)
            read -r -p "Domain (z. B. shop.example.de): " domain
            [ -n "$domain" ] || fail "Für die HTTPS-Einrichtung wird eine Domain benötigt."
            [ -n "$email" ] || read -r -p "E-Mail für Let's Encrypt (optional): " email
            ;;
    esac
fi

domain="$(printf '%s' "$domain" | tr '[:upper:]' '[:lower:]')"

if [ -n "$domain" ]; then
    [[ "$domain" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$ ]] || fail "Ungültige Domain: $domain"
    has apt-get || fail "Die automatische HTTPS-Einrichtung wird nur auf Debian/Ubuntu unterstützt."

    production=true
    [ "$has_url" = true ] || artisan_args+=("--url=https://${domain}")
fi

[ "$production" = false ] || artisan_args+=(--production)

ensure_tools
ensure_php

if [ -n "$domain" ]; then
    ensure_port_is_free
    info "Installiere nginx, PHP-FPM und certbot"
    apt_install nginx "php$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-fpm" certbot python3-certbot-nginx
    relocate_for_webserver
fi

ensure_composer
ensure_node

composer_flags=(--no-interaction --prefer-dist)

if [ "$production" = true ] && [ "$demo" = false ]; then
    composer_flags+=(--no-dev --optimize-autoloader)
fi

info "Installiere PHP-Abhängigkeiten"
composer install "${composer_flags[@]}"

[ -f .env ] || cp .env.example .env

php artisan marketplace:install "${artisan_args[@]}"

if [ -n "$domain" ]; then
    setup_webserver
    issue_certificate
fi
