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

production=false
demo=false

for argument in "$@"; do
    case "$argument" in
        --production) production=true ;;
        --demo) demo=true ;;
    esac
done

ensure_tools
ensure_php
ensure_composer
ensure_node

composer_flags=(--no-interaction --prefer-dist)

if [ "$production" = true ] && [ "$demo" = false ]; then
    composer_flags+=(--no-dev --optimize-autoloader)
fi

info "Installiere PHP-Abhängigkeiten"
composer install "${composer_flags[@]}"

[ -f .env ] || cp .env.example .env

php artisan marketplace:install "$@"
