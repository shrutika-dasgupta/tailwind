#!/bin/bash
source ../../path.sh

while true
do
category=("popular" "everything" "gifts" "videos" "animals"
          "architecture" "art" "cars_motorcycles" "celebrities"
          "design" "diy_crafts" "education" "film_music_books"
          "food_drink" "gardening" "geek" "hair_beauty"
          "health_fitness" "history" "holidays_events"
          "home_decor" "humor" "illustrations_posters"
          "kids" "mens_fashion" "outdoors" "photography"
          "products" "quotes" "science_nature" "sports"
          "tattoos" "technology" "travel" "weddings" "womens_fashion")
for i in "${category[@]}"
do
    cd $ENGINES_PATH/api_pulls && /usr/bin/php pull_category_pins.php $i 2>> $LOGS_PATH/error_pull_category_pins_"$i".log 1> $LOGS_PATH/pull_category_pins_"$i".log &
done
sleep 45
done
