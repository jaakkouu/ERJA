<?php
    session_start();
    if($_SERVER['HTTP_HOST'] == "localhost"){
        define("DB_HOST", "localhost");
        define("DB_NAME", "erja");
        define("DB_USER", "root");
        define("DB_PASS", "uusitalorock");
        define("SITE_URL", "http://localhost/");
    } else {
        define("DB_HOST", "jaakkouuerja.mysql.db");
        define("DB_NAME", "jaakkouuerja");
        define("DB_USER", "jaakkouuerja");
        define("DB_PASS", "Rambo123");
        define("SITE_URL", "https://erja.jaakkouusitalo.fi/");
    }
    function initDb(){
        $dbhost = DB_HOST;
        $dbname = DB_NAME;
        $dbuser = DB_USER;
        $dbpass = DB_PASS;
        try {
            $connection = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=UTF8", $dbuser, $dbpass);
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $connection;
        } catch (PDOException $e){
            echo "Database connection error: ".$e->getMessage();
        }

    }