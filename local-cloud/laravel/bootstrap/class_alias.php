<?php

    /*
         * Sometimes we use the alias customer because
         * shit happens yo
         */
    class_alias('User', 'Customer', true);
    class_alias('UserException', 'CustomerException', true);