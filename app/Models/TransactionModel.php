<?php
require_once __DIR__ . '/../Database/Database.php';

class TransactionModel extends Database
{
 public $code;
 public $trans_total;
 public $trans_quan;
 public $cus_id;
 public $trans_pay;
 public $table = "transactions";

 public function insertTransact()
 {
  $sql = "INSERT INTO " . $this->table . " (transac_code, transac_total, transac_quan, cus_id, transac_created_at, transac_updated_at, transac_payment)
  VALUES (:transac_code, :transac_total, :transac_quan, :cus_id, now(), now(), :transac_payment)";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":transac_code", $this->code);
  $stmt->bindParam(":transac_total", $this->trans_total);
  $stmt->bindParam(":transac_quan", $this->trans_quan);
  $stmt->bindParam(":cus_id", $this->cus_id);
  $stmt->bindParam(":transac_payment", $this->trans_pay);
  if ($stmt->execute()) { return true; }
 }

 public function showTransaction()
 {
  $sql = "SELECT s.username, p.prod_name, t.sub_quantity, p.prod_price, t.sub_price, c.cus_fname, c.cus_phone, t.trans_code, t.created_date
  FROM trans_history t
  INNER JOIN product p ON t.prod_id = p.prod_id
  JOIN supplier c ON t.cus_id = c.cus_id
  JOIN user_account s ON t.user_id = s.user_id
  WHERE t.trans_code = :trans_code";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":trans_code", $this->code);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
 }

 public function getCode()
 {
  $sql = "SELECT t.trans_code FROM trans_history t WHERE t.trans_code = :trans_code";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":trans_code", $this->code);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
 }

 public function getCalculation()
 {
  $sql = "SELECT t.transac_payment, (t.transac_payment - t.transac_total) AS total_change, t.transac_total
  FROM transactions t WHERE t.transac_code = :trans_code";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":trans_code", $this->code);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
 }

 public function showAll()
 {
  // Every selected column is grouped or aggregated, so this works with MySQL's ONLY_FULL_GROUP_BY mode
  $sql = "SELECT t.transac_code, MAX(c.cus_fname) AS cus_fname, SUM(t.transac_quan) AS total_quan, MIN(t.transac_created_at) AS transac_created_at
  FROM transactions t INNER JOIN supplier c ON t.cus_id = c.cus_id
  GROUP BY t.transac_code";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
 }

 public function getAllProduct()
 {
  $sql = "SELECT COUNT(*) AS total_count FROM product";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->execute();
  return $stmt->fetch(PDO::FETCH_ASSOC);
 }

 public function getAllTotalTransactions()
 {
  $sql = "SELECT COUNT(*) AS total_count FROM transactions";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->execute();
  return $stmt->fetch(PDO::FETCH_ASSOC);
 }
}
