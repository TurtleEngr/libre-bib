#!/bin/bash

fUsage() {
    cat <<EOF
Usage:
        rot13.sh -e <<PLAIN.in >FILE-13.out
        rot13.sh -d <FILE-13.in >PLAIN.out

        rot13.sh -E <<PLAIN.in >FILE-13.out
        rot13.sh -D <FILE-13.in >PLAIN.out

    -e - encode a file with rot13 rotation (-E includes digits)
    -d - decode a rot13 file (-D includes digits)
Note: this not "encryption," it is juat obfuscation.
EOF
    exit 1
} # fUsage

# --------------------
if [[ $# -eq 0 ]]; then
    fUsage
fi
if [[ $# -ne 1 ]]; then
    fUsage
fi

# --------------------
if [[ "$1" = "-d" ]]; then
    tr 'n-za-mN-ZA-M' 'a-zA-Z'
    exit 0
fi
if [[ "$1" = "-D" ]]; then
    tr 'n-za-mN-ZA-M5-90-4' 'a-zA-Z0-9'
    exit 0
fi

# --------------------
if [[ "$1" = "-e" ]]; then
    tr 'a-zA-Z' 'n-za-mN-ZA-M'
    exit 0
fi
if [[ "$1" = "-E" ]]; then
    tr 'a-zA-Z0-9' 'n-za-mN-ZA-M5-90-4'
    exit 0
fi

# --------------------
echo "Error: unknown option: $*"
fUsage
