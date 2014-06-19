#!/bin/bash

source ../../path.sh

while true
do
 cd $ENGINES_PATH/api_pulls && /usr/bin/php keyword_search_boards.php >$LOGS_PATH/keyword_search_boards.log 2>> $LOGS_PATH/error_keyword_search_boards.log 1> $LOGS_PATH/keyword_search_boards.log
sleep 1
done
