<?php

namespace Framework\Core;

class Autoloader {
    /**
     * オートローダーを登録する
     * PHPに対して**「ファイルが見つからなくてパニック（エラー）になる前に、一旦このメソッドに相談してくれ！」**と予約を入れるための命令
     */
    public static function register() {
        spl_autoload_register([__CLASS__, 'load']);
    }
    
    /**
     * クラス名からファイルを特定して読み込む
     * @param string $className 名前空間を含むクラス名
     */
    public static function load($className) {
        // 1. 名前空間のバックスラッシュ(\)を、OSのディレクトリ区切り文字(/)に変換
        // 例: Develop\Controllers\LoginController -> Develop/Controllers/LoginController
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        
        // 2. プロジェクトのルートディレクトリからの絶対パスを組み立てる
        // このAutoloader.phpが Framework/Core にある前提で、3階層上がルート
        $baseDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
        $file = $baseDir . $path . '.php';
        
        // 3. ファイルが存在すれば読み込む
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        
        return false;
    }
}