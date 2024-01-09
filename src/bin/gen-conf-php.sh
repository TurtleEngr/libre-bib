#!/usr/bin/env bash
# Convert bash env. var. to be php global vars

echo "<?php"
echo "# Generated with gen-conf-php.sh"
while read -r tLine; do
    tRegEx="^export[[:space:]]+([[:alnum:]_]+)"
    if [[ "$tLine" =~ $tRegEx ]]; then
        tVar="${BASH_REMATCH[1]}"
        echo -e "\nglobal \$$tVar;"
        echo -e "\$$tVar=\$_ENV[\"$tVar\"];"
    fi
done
