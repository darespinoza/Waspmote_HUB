<?php
    define('HOST_PORT', getenv('DB_HOST_PORT'));
    define('MYSQL_USER', getenv('DB_USER'));
    define('MYSQL_PASS', getenv('DB_PASSWORD'));
    define('MYSQL_DB', getenv('DB_NAME'));

    function connect_to_db(){
        try{
            // Connect to MySQL database
            $conn = mysqli_connect(HOST_PORT, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
            return $conn;
        }catch(Exception $exc){
            die("Unable to connect to database". $exc->getMessage());
        }
    }