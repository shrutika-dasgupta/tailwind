
          "products" "quotes" "science_nature" "sports"
          "tattoos" "technology" "travel" "weddings" "womens_fashion"

#!/bin/bash
while true
do
category=("history" "holidays_events" "home_decor" "humor" "illustrations_posters"
          "kids" "mens_fashion" "outdoors" "photography")
for i in "${category[@]}"
do
    cd /var/www/dataengines/production/current/engines/api_pulls && /usr/bin/php consume_category_pins.php $i 2>> /var/www/dataengines/production/storage/logs/error_consume_category_pins_"$i".log 1> /var/www/dataengines/production/storage/logs/consume_category_pins_"$i".log
done
done