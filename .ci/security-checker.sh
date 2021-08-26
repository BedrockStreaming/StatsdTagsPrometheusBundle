#!/bin/bash

set -euo pipefail

CURRENT_DIR="$(dirname ${0})"
SECURITY_CHECKER_BIN=$CURRENT_DIR/local-php-security-checker

downloadBinary() {
    if [ ! -f "$SECURITY_CHECKER_BIN" ]; then
        curl -L -s -o $SECURITY_CHECKER_BIN https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_$1_amd64
        chmod +x $SECURITY_CHECKER_BIN
    fi
}

if [[ "$OSTYPE" == "linux"* ]]; then
    downloadBinary linux
elif [[ "$OSTYPE" == "darwin"* ]]; then
    downloadBinary darwin
else
    @echo "Current OS is not supported."
    @exit 1
fi

$SECURITY_CHECKER_BIN
