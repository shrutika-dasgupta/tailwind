#!/bin/bash
source ../../../path.sh

while true
do
  cd $ENGINES_PATH/api_pulls && /usr/bin/php consume_category_pins.php film_music_books 2>> $LOGS_PATH/error_consume_category_pins_film_music_books.log 1> $LOGS_PATH/consume_category_pins_film_music_books.log
done
