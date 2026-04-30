<?php

namespace Develop\Utils;

class Screen {
    // 最終的に出力するHTMLを保持する変数
    private static $outputBuffer = "";
    
    // JSON返却用のエリア情報を保持する変数（★追加が必要）
    private static $storage = ['areas' => []];
    
    // Viewをセットする（Console.viewなどの枠組み用）
    public static function view($path, $data = []) {
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::view() path: {$path}");
        
        self::$outputBuffer = self::getContents($path, $data);
        echo self::$outputBuffer;
    }
    
    // index.phpの最後で呼び出す
    public static function getBuffer() {
        return self::$outputBuffer;
    }
    
    // 内部処理：View読み込みと置換
    private static function getContents($path, $data = []) {
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::getContents() path: {$path}");
        
        $projectRoot = \Framework\Core\Container::get('PROJECT_ROOT');
        $file = $projectRoot . $path;
        
        if (!file_exists($file)) {
            $logger->error("Screen::getContents() Viewファイル欠損: {$file}");
            throw new \Exception("Viewファイルが見つかりません。");
        }
        
        ob_start();
        extract($data);
        include $file;
        $content = ob_get_clean();
        
        // $data が空でないことを確認してからループ
        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $val) {
                if (is_scalar($val)) {
                    $content = str_replace('{$' . $key . '}', (string)$val, $content);
                }
            }
        }
        
        return $content;
    }
    
    // エリアAの更新準備（共通のsetAreaを呼び出す）
    public static function updateAreaA($viewPath, $data = []) { 
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::updateAreaA() {$viewPath}");
        self::logAndSet('a', $viewPath, $data); 
    }
    
    // エリアBの更新準備（共通のsetAreaを呼び出す）
    public static function updateAreaB($viewPath, $data = []) { 
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::updateAreaB() {$viewPath}");
        self::logAndSet('b', $viewPath, $data); 
    }
    
    // エリアCの更新準備（共通のsetAreaを呼び出す）
    public static function updateAreaC($viewPath, $data = []) { 
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::getContents() {$viewPath}");
        self::logAndSet('c', $viewPath, $data); 
    }
    
    // エリアDの更新準備（共通のsetAreaを呼び出す）
    public static function updateAreaD($viewPath, $data = []) { 
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::getContents() {$viewPath}");
        self::logAndSet('d', $viewPath, $data); 
    }
    
    // エリアEの更新準備（共通のsetAreaを呼び出す）
    public static function updateAreaE($viewPath, $data = []) { 
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::getContents() {$viewPath}");
        self::logAndSet('e', $viewPath, $data); 
    }
    
    // エリアFの更新準備（共通のsetAreaを呼び出す）
    public static function updateAreaF($viewPath, $data = []) { 
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::getContents() {$viewPath}");
        self::logAndSet('f', $viewPath, $data); 
    }
    
    // ロギングとセットを共通化
    private static function logAndSet($key, $viewPath, $data) {
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::updateArea" . strtoupper($key) . "() start...");
        
        // getContentsを使ってHTMLを取得（置換ロジックも共通で使える）
        self::$storage['areas'][$key] = self::getContents($viewPath, $data);
        
        $logger->debug("Screen::updateArea" . strtoupper($key) . "() finish.");
    }
    
    // 格納された全エリアをJSONで一括出力
    public static function areaView() {
        header('Content-Type: application/json');
        echo json_encode(self::$storage);
        exit;
    }
}
