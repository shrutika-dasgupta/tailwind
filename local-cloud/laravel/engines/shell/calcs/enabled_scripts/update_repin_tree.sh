#!/bin/bash

source ../../path.sh

while true
do
 cd $ENGINES_PATH/api_pulls && /usr/bin/php update_repin_tree.php >$LOGS_PATH/update_repin_tree.log 2>> $LOGS_PATH/error_update_repin_tree.log 1> $LOGS_PATH/update_repin_tree.log
done