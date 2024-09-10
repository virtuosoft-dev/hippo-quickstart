#!/bin/bash

# Stop the upload server by PID
kill $(ps aux | grep '[u]pload-server.js' | grep admin | awk '{print $2}')
echo "Upload server stopped";
rm -f /tmp/quickstart_upload_activity