#!/bin/bash
while true
do
 > /var/www/dataengines/production/storage/logs/graph_profiles.log
 cd /var/www/dataengines/production/current/engines/api_pulls && /usr/bin/php graph_profiles.php >/var/www/dataengines/production/storage/logs/graph_profiles.log 2>&1
done