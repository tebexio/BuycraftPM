#!/bin/bash

# Clean up the existing phar
rm -f BuycraftPM.phar

# Check if phar.readonly is Off
if [ `php -r 'print ini_get("phar.readonly") ? "false" : "true";'` == false ]; then
    echo "PHAR creation is not enabled in your php.ini. Please set phar.readonly = Off and try again."
    exit 1
fi

phar pack -c gz -f BuycraftPM.phar -x "(.git|.idea)" .
