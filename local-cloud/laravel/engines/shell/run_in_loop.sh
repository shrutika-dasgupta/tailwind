while true
do
 cd $ENGINES_PATH/$ENGINE_TYPE && /usr/bin/php $FILENAME > $LOGS_PATH/$FILENAME.log
done
