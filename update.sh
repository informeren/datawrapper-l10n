#!/bin/bash

set -e

pushd ../datawrapper

# regenerate template cache
pushd ./scripts
rm -Rf ./tmpl_cache/*
php gen_template_cache.php
popd

xgettext --default-domain=core -o ../datawrapper-localization/locale/messages.pot --from-code=UTF-8 -n --omit-header -k__ -L PHP ./controller/*.php ./scripts/tmpl_cache/*.php ./www/index.php ./www/api/index.php ./lib/api/*.php ./lib/api/*/*.php ./lib/core/build/classes/datawrapper/*.php ./lib/session/*.php ./lib/templates/*.php ./lib/utils/*.php

for dir in ./plugins/*; do
    plugin=${dir:10}
    mkdir -p "../datawrapper-localization/plugins/$plugin/locale"
    pofile="../datawrapper-localization/plugins/$plugin/locale/messages.pot"
    xgettext --default-domain="plugin-$plugin" -k__ -o "$pofile" --from-code=UTF-8 -n --omit-header -L PHP $(shopt -s nullglob; echo ./plugins/$plugin/*.php) $(shopt -s nullglob; echo ./scripts/tmpl_cache/plugins/${plugin}__*.php)

    if [ ! -f "$pofile" ]
    then
        rm -Rf "../datawrapper-localization/plugins/$plugin"
    fi
done

popd
