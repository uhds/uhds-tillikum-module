#!/bin/sh

DEST_HOST=$1
DEST_BASE=$2

if [ -z "$DEST_HOST" ]; then
  echo "You must specify the host to deploy to as the first argument."
  exit 1;
fi

if [ -z "$DEST_BASE" ]; then
  echo "You must specify the base path to deploy to as the second argument."
  exit 1;
fi

dest_path="${DEST_BASE}/tillikum-new"
source="`dirname $0`/../../../build"

rsync -crlvz ${source}/ ${DEST_HOST}:${dest_path} \
  --delay-updates \
  --delete-delay \
  --rsync-path="sudo rsync" \
  --exclude 'vendor' \
  --exclude 'config/*local.config.php'

ssh ${DEST_HOST} \
  "sudo cp /srv/etc/tillikum/local.config.php ${dest_path}/config/local.config.php &&" \
  "sudo ln -fns /usr/local/share/tillikum-vendor ${dest_path}/vendor"
