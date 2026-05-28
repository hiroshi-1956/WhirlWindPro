<?php

namespace Framework\Core;

class Router {
    
    public function run() {
        $logger = Container::get('logger');
        
        // 1. 生のURLを取得してデコード
        $rawUri = rawurldecode($_SERVER['REQUEST_URI']);
        
        // URLに "FETCH::" が含まれている場合、他の解析をすべてスルーして実行
        if (strpos($rawUri, 'FETCH::') !== false) {
            $logger->debug("Direct Fetch Mode: Start parsing '{$rawUri}'");
            
            // "FETCH::" 以降の文字列を取得 (例: Console/AreaInitialAction)
            $parts = explode('FETCH::', $rawUri);
            $directPath = end($parts);
            
            // クエリパラメータ（?以降）を削除
            $directPath = explode('?', $directPath)[0];
            
            // スラッシュで分割してコントローラーとメソッドを特定
            $segments = explode('/', trim($directPath, '/'));
            
            if (count($segments) >= 2) {
                $controllerBase = ucfirst($segments[0]);
                $methodBase     = $segments[1];
                
                // クラス名とメソッド名を整形
                $fullClassName = "\\Develop\\Controllers\\{$controllerBase}Controller";
                $methodName     = (preg_match('/Action$/', $methodBase)) ? $methodBase : $methodBase . 'Action';
                
                $logger->debug("Direct Fetch Target: {$fullClassName}::{$methodName}");
                
                if (class_exists($fullClassName)) {
                    $controller = new $fullClassName();
                    if (method_exists($controller, $methodName)) {
                        $logger->debug("Direct Fetch: Executing now.");
                        // 実行して終了（exitすることで後の解析ロジックによる書き換えを防ぐ）
                        call_user_func_array([$controller, $methodName], []);
                        exit;
                    } else {
                        $logger->error("Direct Fetch Error: Method '{$methodName}' not found in '{$fullClassName}'");
                    }
                } else {
                    $logger->error("Direct Fetch Error: Class '{$fullClassName}' not found.");
                }
            } else {
                $logger->error("Direct Fetch Error: Invalid path format. Expected 'Controller/Method'");
            }
            
            // Fetchモードに入ったが実行できなかった場合は404を返して終了
            header("HTTP/1.1 404 Not Found");
            echo "Fetch Direct Error: Target not found.";
            exit;
        }
        
        $uri = parse_url($rawUri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') . '/';

        $logger->debug("Router::Router() {$uri}");
        
        $baseUrl = \Framework\Core\Container::get('BASE_URL');
        $baseUrlPath = parse_url($baseUrl, PHP_URL_PATH);
        
        $subPath = trim(str_replace($baseUrlPath, '', $uri), '/');
        $subPath = str_replace('index.php', '', $subPath);
        $params = explode('/', $subPath);
        
        $modeRaw = array_shift($params);
        $mode = strtolower($modeRaw);
        $allowedModes = ['develop', 'product'];
        
        if (!in_array($mode, $allowedModes)) {
            $logger->write("Router Error: Invalid or missing mode '{$mode}'");
            header("HTTP/1.1 404 Not Found");
            echo "<h3>[wwProject System Error]</h3>URLの先頭には Develop または Product を明示してください。";
            exit;
        }
        
        $namespace = ucfirst($mode);
        $controllerName = ucfirst(array_shift($params) ?: 'Login');
        $fullClassName = "\\{$namespace}\\Controllers\\{$controllerName}Controller";
        
        $rawMethod = array_shift($params) ?: 'initial';
        $methodName = (preg_match('/Action$/', $rawMethod)) ? $rawMethod : $rawMethod . 'Action';
        
        $logger->debug("Attempting to load: {$fullClassName}::{$methodName}");
        
        if (class_exists($fullClassName)) {
            $controller = new $fullClassName();
            if (method_exists($controller, $methodName)) {
                $logger->debug("Router: Executing {$fullClassName}->{$methodName}");
                call_user_func_array([$controller, $methodName], $params);
            } else {
                header("HTTP/1.1 404 Not Found");
                echo "Method {$methodName} not found.";
            }
        } else {
            header("HTTP/1.1 404 Not Found");
            echo "Critical Error: Controller {$fullClassName} not found.";
        }
    }
}
