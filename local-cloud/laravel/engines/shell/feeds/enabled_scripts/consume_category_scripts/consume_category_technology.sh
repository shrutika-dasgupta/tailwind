#!/bin/bash
source ../../../path.sh

while true
do
  cd $ENGINES_PATH/api_pulls && /usr/bin/php consume_category_pins.php technology 2>> $LOGS_PATH/error_consume_category_pins_technology.log 1> $LOGS_PATH/consume_category_pins_technology.log
done
