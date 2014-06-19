#!/bin/bash
while true
do
 > /var/www/dataengines/current/engines/logs/followersCompetitors.log
 cd /var/www/dataengine/current/engines/api_pulls && /usr/bin/php followers_competitors.php >/var/www/dataengines/current/engines/logs/followersCompetitors.log 2>&1
done