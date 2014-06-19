#!/bin/bash
while true
do
 > /var/www/dataengines/current/engines/logs/gender_log1.log
 cd /var/www/dataengine/current/engines/calculations && /usr/bin/php gender.php >/var/www/dataengines/current/engines/logs/gender_log1.log 2>&1
done