#!/bin/bash

# Update activity flag to keep any upload server running
touch /home/admin/tmp/quickstart_upload_activity
chown admin:admin /home/admin/tmp/quickstart_upload_activity

# First determine if the upload server is already running
PID=$(ps -aux | grep upload-server.js | grep admin | grep -v grep | awk '{print $2}');
if [ -n "$PID" ]; then
  echo "Upload server is already running with PID $PID";
  exit 0;
fi

# Start the upload server by re-invoking ourself with a start now flag
if [ "$1" == "start_now" ]; then
  export NVM_DIR=/opt/nvm && source /opt/nvm/nvm.sh
  cd /usr/local/hestia/plugins/quickstart || exit
  node /usr/local/hestia/plugins/quickstart/upload-server.js > /dev/null 2>&1 &
  echo "Upload server started";
else
  echo "Re-spawning with start_now flag as admin";
  runuser -u admin -- /usr/local/hestia/plugins/quickstart/start-upload-server.sh start_now  
fi


