#!/bin/bash
while true
do
category=("design" "diy_crafts" "education" "film_music_books"
          "food_drink" "gardening" "geek" "hair_beauty" "health_fitness")
for i in "${category[@]}"
do
    cd /var/www/dataengines/production/current/engines/api_pulls && /usr/bin/php consume_category_pins.php $i 2>> /var/www/dataengines/production/storage/logs/error_consume_category_pins_"$i".log 1> /var/www/dataengines/production/storage/logs/consume_category_pins_"$i".log
done
done