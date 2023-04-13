<?php
session_start();
if (is_file('../config.php')) {
    require_once '../config.php';
}
$db_driver = DB_DRIVER;
$host = DB_HOSTNAME;
$dbname = DB_DATABASE;
$user = DB_USERNAME;
$pass = DB_PASSWORD;
$port = DB_PORT;
$charset = 'utf8mb4';
$e1 = '';
try { //driver from config
    $dsn = '' . $db_driver . ":host=$host;dbname=$dbname;charset=$charset;port=$port";
    $link = new \PDO($dsn, $user, $pass);
} catch (\PDOException$e) {
    $e1 = $e->getMessage();
    try { //std driver
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset;port=$port";
        $link = new \PDO($dsn, $user, $pass);
    } catch (\PDOException$e) {
        throw new \PDOException($e->getMessage(), (int) $e->getCode());
    }
}
$GLOBALS['link'] = $link;
$GLOBALS['dbname'] = $dbname;
$GLOBALS['prefix'] = DB_PREFIX;
