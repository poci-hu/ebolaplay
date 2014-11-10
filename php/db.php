<?php

class db extends PDO {

    private $server = '';
    private $database = '';
    private $user = '';
    private $pass = '';

    public function __construct() {

        $this->server = 'xyz.forpsi.com';
        $this->database = 'xyz';
        $this->user = 'xyz';
        $this->pass = 'xyz';

        try {

            //create the PDO
            parent::__construct('mysql:host=' . $this->server . ';dbname=' . $this->database, $this->user, $this->pass);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->query("SET NAMES utf8");
        } catch (PDOException $e) {

            echo 'Adatbázis hiba!';
            return false;
        }

        $this->server = '';
        $this->user = '';
        $this->pass = '';
        $this->database = '';
    }

}
