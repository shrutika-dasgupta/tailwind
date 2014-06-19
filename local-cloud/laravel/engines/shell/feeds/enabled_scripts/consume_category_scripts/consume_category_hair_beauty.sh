#!/bin/bash
source ../../../path.sh

while true
do
  cd $ENGINES_PATH/api_pulls && /usr/bin/php consume_category_pins.php hair_beauty 2>> $LOGS_PATH/error_consume_category_pins_hair_beauty.log 1> $LOGS_PATH/consume_category_pins_hair_beauty.log
done
