<?php

namespace Develop\Utils;

class Request {

    // GETパラメータを取得する
    public static function get($key, $default = '') {
        return $_GET[$key] ?? $default;
    }
    
    // POSTデータを安全に取得する
    public static function post($key, $default = null) {
        
        // 存在しない場合はデフォルト値を返す
        if (!isset($_POST[$key])) {
            return $default;
        }
        
        $value = $_POST[$key];
        
        // 前後の空白を除去（おんぶにだっこ処理）
        if (is_string($value)) {
            $value = trim($value);
        }
        
        return $value;
    }
}
