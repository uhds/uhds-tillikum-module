#!/bin/sh

DEST_HOST=$1

if [ -z "$DEST_HOST" ]; then
  echo "You must specify the host to deploy to."
  exit 1;
fi

source="`dirname $0`/../../../vendor"
dest_path="/usr/local/share/tillikum-vendor"

rsync -crlvz ${source}/ ${DEST_HOST}:${dest_path} \
  --delay-updates \
  --delete-delay \
  --rsync-path="sudo rsync" \
