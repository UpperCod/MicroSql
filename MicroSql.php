<?php

namespace MicroSql;

require "src/Query.php";


class Connect{
    public $db = false;
    public $prefix = '';
    function __construct( $DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME ,$DB_PREFIX = "" , $DB_ENGINER = "mysql"){
        $this->db  =  new \PDO(
            "{$DB_ENGINER}:host={$DB_HOST};dbname={$DB_NAME}",
            $DB_USER, 
            $DB_PASSWORD
        );
        $this->prefix = $DB_PREFIX;
    }
    
    function __get($table){
        return new Query([
            "table"  => $table,
            "db"     => $this->db,
            "prefix" => $this->prefix
        ]);
    }
}