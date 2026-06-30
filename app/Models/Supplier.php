<?php
require_once __DIR__ . '/../Database/Database.php';

class Supplier extends Database
{
 public $fname;
 public $lname;
 public $phone;
 public $id;
 public $table = "supplier";

 public function insertItem()
 {
  $sql = "INSERT INTO " . $this->table . " (cus_fname, cus_lname, cus_phone, cus_created_at, cus_updated_at)
  VALUES (:cus_fname, :cus_lname, :cus_phone, now(), now())";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":cus_fname", $this->fname);
  $stmt->bindParam(":cus_lname", $this->lname);
  $stmt->bindParam(":cus_phone", $this->phone);
  if ($stmt->execute()) { return true; }
 }

 public function showAll()
 {
  $sql = "SELECT * FROM " . $this->table;
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
 }

 public function getSingle()
 {
  $sql = "SELECT * FROM supplier WHERE cus_id = :cus_id";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":cus_id", $this->id);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
 }

 public function update()
 {
  $sql = "UPDATE " . $this->table . " SET cus_fname = :cus_fname, cus_lname = :cus_lname, cus_phone = :cus_phone, cus_updated_at = now() WHERE cus_id = :cus_id";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":cus_id", $this->id);
  $stmt->bindParam(":cus_fname", $this->fname);
  $stmt->bindParam(":cus_lname", $this->lname);
  $stmt->bindParam(":cus_phone", $this->phone);
  if ($stmt->execute()) { return true; }
 }
}
