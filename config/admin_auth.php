<?php
class AdminAuth {
    const ADMIN_USERNAME = 'admin';
    const ADMIN_PASSWORD = 'admin123'; // Mude esta senha!
    const ADMIN_SESSION_KEY = 'admin_logged_in';
    
    public static function login($username, $password) {
        if ($username === self::ADMIN_USERNAME && $password === self::ADMIN_PASSWORD) {
            $_SESSION[self::ADMIN_SESSION_KEY] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_login_time'] = time();
            return true;
        }
        return false;
    }
    
    public static function logout() {
        unset($_SESSION[self::ADMIN_SESSION_KEY]);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_login_time']);
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION[self::ADMIN_SESSION_KEY]) && $_SESSION[self::ADMIN_SESSION_KEY] === true;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: admin_login.php');
            exit;
        }
    }
    
    public static function getAdminInfo() {
        if (self::isLoggedIn()) {
            return [
                'username' => $_SESSION['admin_username'] ?? 'admin',
                'login_time' => $_SESSION['admin_login_time'] ?? time()
            ];
        }
        return null;
    }
}
?>
