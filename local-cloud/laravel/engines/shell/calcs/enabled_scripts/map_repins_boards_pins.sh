#!/bin/bash

source ../../path.sh

while true
do
 cd $ENGINES_PATH/api_pulls && /usr/bin/php map_repins_boards_pins.php >$LOGS_PATH/map_repins_boards_pins.log 2>> $LOGS_PATH/error_map_repins_boards_pins.log 1> $LOGS_PATH/map_repins_boards_pins.log
done