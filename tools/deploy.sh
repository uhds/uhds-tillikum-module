#!/bin/bash

DIR="$(cd $(dirname ${BASH_SOURCE[0]}) && pwd)"

function usage() {
  echo "Usage: ${0} <hostname>" >&2
}

if [ -z "$1" ]
then
  echo "You need to supply <hostname>."
  echo
  usage

  exit 1
fi

cd "${DIR}/../../.."

./vendor/bin/phing -f site/oregonstate.edu/build.xml deploy -Dhost=${1}
