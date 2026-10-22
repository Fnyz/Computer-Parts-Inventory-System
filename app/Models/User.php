<?php
require_once __DIR__ . '/../Database/Database.php';

class User extends Database
{
 public $userType;
 public $fname;
 public $lname;
 public $phone;
 public $username;
 public $pass;
 public $id;
 public $table = "user_account";

 public function insertUser()
 {
  $sql = "INSERT INTO " . $this->table . " (user_fname, user_lname, user_phone, username, user_pass, userType, user_created_at, user_updated_at)
  VALUES (:user_fname, :user_lname, :user_phone, :username, :user_pass, :userType, now(), now())";
  $stmt = $this->getConnect()->prepare($sql);
  $hashedPass = password_hash($this->pass, PASSWORD_DEFAULT);
  $stmt->bindParam(":user_fname", $this->fname);
  $stmt->bindParam(":user_lname", $this->lname);
  $stmt->bindParam(":user_phone", $this->phone);
  $stmt->bindParam(":userType", $this->userType);
  $stmt->bindParam(":username", $this->username);
  $stmt->bindParam(":user_pass", $hashedPass);
  if ($stmt->execute()) { return true; }
 }

 public function showUser()
 {
  $sql = "SELECT * FROM " . $this->table;
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
 }

 public function showUserAdmin()
 {
  $sql = "SELECT * FROM " . $this->table . " WHERE userType = 'Admin'";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
 }

 public function getSingleUser()
 {
  $sql = "SELECT * FROM user_account WHERE user_id = :user_id";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindParam(":user_id", $this->id);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
 }

 public function countUsers()
 {
  $stmt = $this->getConnect()->query("SELECT COUNT(*) FROM " . $this->table);
  return (int)$stmt->fetchColumn();
 }

 public function usernameExists($username, $excludeId = 0)
 {
  $sql = "SELECT COUNT(*) FROM " . $this->table . " WHERE username = :username AND user_id <> :user_id";
  $stmt = $this->getConnect()->prepare($sql);
  $stmt->bindValue(":username", $username);
  $stmt->bindValue(":user_id", (int)$excludeId, PDO::PARAM_INT);
  $stmt->execute();
  return (int)$stmt->fetchColumn() > 0;
 }

 public function updateUser()
 {
  // An empty password field means "keep the current password"
  $changePass = $this->pass !== null && $this->pass !== '';
  $fields = "user_fname = :user_fname, user_lname = :user_lname, username = :username, user_updated_at = now()";
  if ($changePass) {
   $fields .= ", user_pass = :user_pass";
  }
  $stmt = $this->getConnect()->prepare("UPDATE " . $this->table . " SET " . $fields . " WHERE user_id = :user_id");
  $stmt->bindParam(":user_id", $this->id);
  $stmt->bindParam(":user_fname", $this->fname);
  $stmt->bindParam(":user_lname", $this->lname);
  $stmt->bindParam(":username", $this->username);
  if ($changePass) {
   $hashedPass = password_hash($this->pass, PASSWORD_DEFAULT);
   $stmt->bindParam(":user_pass", $hashedPass);
  }
  if ($stmt->execute()) { return true; }
 }
}
