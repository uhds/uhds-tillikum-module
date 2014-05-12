#!/bin/bash

DIR="$(cd $(dirname ${BASH_SOURCE[0]}) && pwd)"

cd "${DIR}/../../.."

echo -n 'interstice nix1 omit' | \
    xargs -I{} -d' ' -n1 \
    ./vendor/bin/phing -f site/oregonstate.edu/build.xml deploy -Dhost={}
