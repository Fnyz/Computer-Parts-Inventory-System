<?php
require_once __DIR__ . '/../Database/Database.php';

class Sale extends Database
{
 public $prod_id;
 public $cus_id;
 public $sub_price;
 public $total_quan;
 public $trans_code;
 public $table = "trans_history";
 public $id;
 public $user_id;

 public function insertData()
 {
  $sql = "INSERT INTO " . $this->table . " (prod_id, cus_id, trans_code, sub_price, sub_quantity, created_date, user_id)
  VALUES (:prod_id, :cus_id, :trans_code, :sub_price, :sub_quantity, now(), :user_id)";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":prod_id", $this->prod_id);
  $stmt->bindParam(":cus_id", $this->cus_id);
  $stmt->bindParam(":trans_code", $this->trans_code);
  $stmt->bindParam(":sub_price", $this->sub_price);
  $stmt->bindParam(":sub_quantity", $this->total_quan);
  $stmt->bindParam(":user_id", $this->user_id);
  if ($stmt->execute()) { return true; }
 }

 public function showTrans()
 {
  $sql = "SELECT * FROM trans_history t INNER JOIN product p ON t.prod_id = p.prod_id WHERE trans_code = :trans_code";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":trans_code", $this->trans_code);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
 }

 public function userGet()
 {
  $sql = "SELECT * FROM user_account WHERE user_id = :user_id";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":user_id", $this->user_id);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
 }

 public function totalPrice()
 {
  $sql = "SELECT SUM(sub_price) AS total_price, SUM(t.sub_quantity) AS total_quan FROM trans_history t INNER JOIN product p ON t.prod_id = p.prod_id WHERE trans_code = :trans_code";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":trans_code", $this->trans_code);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
 }

 public function deleteData()
 {
  $sql = "DELETE FROM trans_history WHERE trans_id = :trans_id";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":trans_id", $this->id);
  if ($stmt->execute()) { return true; }
 }

 public function updateProduct()
 {
  $sql = "UPDATE product SET prod_quan = prod_quan - :prod_quan, prod_price = :prod_price WHERE prod_id = :prod_id";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":prod_quan", $this->total_quan);
  $stmt->bindParam(":prod_price", $this->sub_price);
  $stmt->bindParam(":prod_id", $this->prod_id);
  return (bool)$stmt->execute();
 }

 public function updateToZero()
 {
  $sql = "UPDATE product SET prod_quan = NULL, prod_price = NULL WHERE prod_id = :prod_id";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":prod_id", $this->prod_id);
  return (bool)$stmt->execute();
 }

 public function searchDuplicate()
 {
  $sql = "SELECT * FROM trans_history WHERE prod_id = :prod_id AND trans_code = :trans_code";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":prod_id", $this->prod_id);
  $stmt->bindParam(":trans_code", $this->trans_code);
  $stmt->execute();
  return $stmt->rowCount();
 }
}
