<?php

class Database
{
    private $conn;

    function __construct()
    {
        $this->conn = new mysqli('localhost', 'root', '', 'restaurant', 3306);
        $this->conn->set_charset("utf8");
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
