<?php

namespace Develop\Utils;

class Session {
    
    public static function start() {
        $logger = new \Framework\Core\Logger();
        $logger->debug("Session::start()");
        
        session_destroy();
        session_start();
    }
    
    // セッションに値を書き込む
    public static function set($key, $value) {
        // セッションが開始されていなければ開始する
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[$key] = $value;
    }
    
    // 一時的なメッセージを保存する
    public static function setFlash($key, $message) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION["flash_{$key}"] = $message;
    }
    
    // セッションから値を取得する
    public static function get($key, $default = null) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return $_SESSION[$key] ?? $default;
    }
    
    // ログイン成功時の処理を一括で行う
    public static function loginSuccess($data) {
        // 1. セッションIDを新しくしてセキュリティを確保（古いIDを破棄）
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);
        
        // 2. 既存の setArray を使って情報を一括保存
        self::setArray($data);
    }
    
    // 配列を一括でセッションに保存する（おんぶにだっこ機能）
    public static function setArray($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                self::set($key, $value);
            }
        }
    }
    
    // メッセージを取得して、その場で消去する（おんぶにだっこ機能）
    public static function getFlash($key) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $message = $_SESSION["flash_{$key}"] ?? null;
        
        // // 取得したらすぐに削除して、次回の表示を防ぐ
        if (isset($_SESSION["flash_{$key}"])) {
            unset($_SESSION["flash_{$key}"]);
        }
        
        return $message;
    }
    
    public static function destroy() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
                );
        }
        session_destroy();
    }
}
