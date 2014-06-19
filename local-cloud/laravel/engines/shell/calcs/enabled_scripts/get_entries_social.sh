#!/bin/bash
ENGINE_TYPE=api_pulls/content
FILENAME="get_entries_social.php twitter 100 2"
LOGNAME="get_entries_social.php-twitter-100-2"

DIR="$( cd "$( dirname "$0" )" && pwd )"
source $DIR/../../path.sh

while true
do
 cd $ENGINES_PATH/$ENGINE_TYPE && /usr/bin/php $FILENAME > $LOGS_PATH/$LOGNAME.log
done
