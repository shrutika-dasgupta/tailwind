#!/bin/bash

source ../../path.sh

while true
do
 cd $ENGINES_PATH/api_pulls && /usr/bin/php keyword_search_board_pins.php >$LOGS_PATH/keyword_search_board_pins.log 2>> $LOGS_PATH/error_keyword_search_board_pins.log 1> $LOGS_PATH/keyword_search_board_pins.log
sleep 1
done
