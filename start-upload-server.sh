#!/bin/bash

# Start the upload server
export NVM_DIR=/opt/nvm && source /opt/nvm/nvm.sh
cd /usr/local/hestia/plugins/quickstart
node /usr/local/hestia/plugins/quickstart/upload-server.js > /dev/null 2>&1 &
