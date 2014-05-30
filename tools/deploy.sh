#!/bin/bash

DIR="$(cd $(dirname ${BASH_SOURCE[0]}) && pwd)"

function usage() {
  echo "Usage: ${0} <hostname>"
}

if [ -z "$1" ]
then
  echo -e "You need to supply <hostname>.\n" >&2
  usage >&2

  exit 1
fi

cd "${DIR}/../../.."

./vendor/bin/phing -f site/oregonstate.edu/build.xml deploy -Dhost=${1}
