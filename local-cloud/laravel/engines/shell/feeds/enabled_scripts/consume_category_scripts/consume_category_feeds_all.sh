#!/bin/bash
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
    nohup ./consume_category_"$i".sh > /dev/null 2&>1 &
sleep 2
done
