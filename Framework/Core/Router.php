<?php

namespace Framework\Core;

class Router {
    
    public function run() {
        $logger = Container::get('logger');
        
        // 1. URLを解析（?以降をカットし、末尾のスラッシュを整える）
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/') . '/'; // 末尾を必ずスラッシュありにして解析しやすくする
        
        // 2. モードの特定
        $baseUrl = \Framework\Core\Container::get('BASE_URL');
        $baseUrlPath = parse_url($baseUrl, PHP_URL_PATH);
        
        $subPath = trim(str_replace($baseUrlPath, '', $uri), '/');
        $subPath = str_replace('index.php', '', $subPath);
        $params = explode('/', $subPath);
        
        // 配列の1番目を「モード」として取り出す
        $modeRaw = array_shift($params);
        $mode = strtolower($modeRaw);
        $allowedModes = ['develop', 'product'];
        
        if (!in_array($mode, $allowedModes)) {
            $logger->write("Router Error: Invalid or missing mode '{$mode}'");
            header("HTTP/1.1 404 Not Found");
            echo "<h3>[wwProject System Error]</h3>";
            echo "URLの先頭には <b>Develop</b> または <b>Product</b> を明示してください。";
            exit; // 続行させずに止める
        }
        
        // 名前空間を決定
        $namespace = ucfirst($mode); // 'Develop' または 'Product'
        
        // 残りの配列からコントローラー名を決定
        $controllerName = ucfirst(array_shift($params) ?: 'Login');
        
        // 3. クラス名とメソッド名の組み立て
        $fullClassName = "\\{$namespace}\\Controllers\\{$controllerName}Controller";
        $methodName = (array_shift($params) ?: 'initial') . 'Action';
        
        $logger->debug("Attempting to load: {$fullClassName}::{$methodName}");
        
        // 4. 実行処理（ここが重要！）
        if (class_exists($fullClassName)) {
            $controller = new $fullClassName();
            if (method_exists($controller, $methodName)) {
                // ログに実行を記録して呼び出し
                $logger->debug("Router: Executing {$fullClassName}->{$methodName}");
                call_user_func_array([$controller, $methodName], $params);
            } else {
                $logger->debug("Router Error: Method {$methodName} not found.");
                echo "Method {$methodName} not found.";
            }
        } else {
            $logger->error("Router Error: Controller {$fullClassName} not found.");
            echo "Critical Error: Controller {$fullClassName} not found.";
        }
    }
}
