#!/bin/bash
while true
do
 > /var/www/dataengines/current/engines/logs/update_locale_info.log
 cd /var/www/dataengine/current/engines/api_pulls && /usr/bin/php update_locale.php >/var/www/dataengines/current/engines/logs/update_locale_info.log 2>&1
done