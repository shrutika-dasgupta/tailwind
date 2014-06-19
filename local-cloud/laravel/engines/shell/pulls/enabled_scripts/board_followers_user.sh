#!/bin/bash
ENGINE_TYPE=api_pulls
FILENAME="board_followers_user.php $1"
LOGFILE=board_followers_user.php_1


DIR="$( cd "$( dirname "$0" )" && pwd )"
source $DIR/../../path.sh
source $DIR/../../run_in_loop_arg.sh

