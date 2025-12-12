<?php
// includes/class_xac_thuc.php
require_once __DIR__.'/ket_noi_db.php';

class Auth {
    private $pdo;
    public function __construct($pdo){ $this->pdo = $pdo; }

    public function findByEmail($email){
        $stmt = $this->pdo->prepare("SELECT * FROM Users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function login($email,$password){
        $user = $this->findByEmail($email);
        if(!$user) return false;
        if(password_verify($password, $user['password'])){
            unset($user['password']);
            $_SESSION['user'] = $user;
            return true;
        }
        return false;
    }

    public function logout(){
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }

    public function register($email,$password,$full_name){
        if($this->findByEmail($email)) return false;
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("INSERT INTO Users (email,password,full_name,is_verified) VALUES(?,?,?,0)");
        $stmt->execute([$email,$hash,$full_name]);
        return $this->pdo->lastInsertId();
    }

    public function updatePasswordByEmail($email,$newpass){
        $hash = password_hash($newpass, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("UPDATE Users SET password = ? WHERE email = ?");
        return $stmt->execute([$hash,$email]);
    }
}
