<?php
class Database
{
    private $server = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "inventorymanagementsystem";
    private $conn;

    public function getConnect()
    {
        $this->conn = null;

        try {
            $dbServer = "mysql:host=" . $this->server . ";dbname=" . $this->dbname;
            $this->conn = new PDO($dbServer, $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Connection Unsuccessfull!: " . $e->getMessage();
        }

        return $this->conn;
    }
}
