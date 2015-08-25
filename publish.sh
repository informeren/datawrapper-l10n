#!/bin/bash

set -e

while IFS= read -r -d '' source
do
  target="../datawrapper${source:1:${#source}-3}json"
  echo "$source -> $target"
  php ./po2json.php "$source" "$target"
done < <(find . -name "*.po" -print0)
