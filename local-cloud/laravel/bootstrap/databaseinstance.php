<?php


/*
 * Singleton connection for DB
 *
 * @authors Will
 */

class DatabaseInstance
{

    private static $dbo, $conn;

    /**
     * Return the database object
     *
     * Example use:
     *
     * $DBH = DatabaseInstance::DBO();
     * $results = $DBH->query('select * from table')->fetchAll();
     *
     * @author Will
     */

    public static function DBO()
    {

        if (!self::$dbo) {
            try{
                self::$dbo = new PDO(
                    'mysql:host=' . Config::get('database.connections.mysql.host').
                    ';dbname=' . Config::get('database.connections.mysql.database') .
                    ';port=3306' .
                    ';charset=utf8',
                    Config::get('database.connections.mysql.username'),
                    Config::get('database.connections.mysql.password')
                );
                self::$dbo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$dbo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

            } catch (PDOException $e){


                /**
                 * this needs to be moved to the global file
                 * in the app/bootstrap dir
                 */

                $error_codes_to_check = array(2005, 2003);

                $db_error_code = (int) $e->getCode();

                // This is the sqlstate when we can't
                // connect to database
                if (in_array($db_error_code, $error_codes_to_check)){
                    $storage_path = ROOT_PATH.'../storage';
                    $name = "dbfail" . date("-F-j-Y") . ".log";

                    $file_path = $storage_path . "/logs/$name";
                    if (!file_exists($file_path)){

                        $message_body = 'There is an issue with the db connection';
                        Mail::send('shared.emails.templates.blank',
                                   array(
                                        'main_body' => $message_body
                                   ),

                            function ($message) {

                                $message->from('yesh+dbalert@tailwindapp.com', 'DB Fail Alert');
                                $message->to('coredev@tailwindapp.com', 'Tailwind Core Development Team');

                                $message->subject("DB connection has failed");

                            }

                        );
                    }

                    // Creating and saving the log file
                    // for DB error
                    fopen($file_path, 'a+');
                    file_put_contents($file_path, print_r($e, true));
                    }
                }
            }

        return self::$dbo;
    }

    /**
     * Depreciated functions for mysql connect
     * included for legacy code
     *
     * Example use
     *
     * $conn = DatabaseInstance::mysql_connect();
     * $results = mysql_query('select * from table',$conn);
     *
     * @author Will
     *
     */

    public static function mysql_connect()
    {

        if (!self::$conn) {

           @self::$conn = mysql_connect(
                Config::get('database.connections.mysql.host'),
                Config::get('database.connections.mysql.username'),
                Config::get('database.connections.mysql.password')
            );

            mysql_select_db(
                Config::get('database.connections.mysql.database'),
                self::$conn
            );

            mysql_set_charset(
                Config::get('database.connections.mysql.charset'),
                self::$conn
            );
        }

        return self::$conn;

    }

}

class DatabaseInstanceException extends Exception {}
