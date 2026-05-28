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
        $logger->debug("Screen::view() path: {$path} start...");
        
        self::$outputBuffer = self::getContents($path, $data);
        echo self::$outputBuffer;

        $logger->debug("Screen::view() path: {$path} finish.");
    }
    
    // index.phpの最後で呼び出す
    public static function getBuffer() {
        return self::$outputBuffer;
    }
    
    // 内部処理：View読み込みと置換
    private static function getContents($path, $data = []) {
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::getContents() start...");
        
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
        
        $logger->debug("Screen::getContents() finish.");
        return $content;
    }
    
    // エリアAの更新準備（共通のsetAreaを呼び出す）
    public static function updateAreaA($viewPath, $data = []) { 
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::updateAreaA() path: {$viewPath} start...");
        $logger->debug("Screen::areaView() sending JSON: " . json_encode(array_keys(self::$storage)));
        
        self::logAndSet('a', $viewPath, $data); 
        
        $logger->debug("Screen::updateAreaA() finish.");
    }
    
    // エリアBの更新準備（共通のsetAreaを呼び出す）
    public static function updateAreaB($viewPath, $data = []) { 
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::updateAreaB() path: {$viewPath} start...");
        
        self::logAndSet('b', $viewPath, $data); 
        
        $logger->debug("Screen::updateAreaB() finish.");
    }
    
    // エリアCの更新準備（共通のsetAreaを呼び出す）
    public static function updateAreaC($viewPath, $data = []) { 
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::updateAreaC() {$viewPath} start...");
        
        self::logAndSet('c', $viewPath, $data); 
        
        $logger->debug("Screen::updateAreaC() finish.");
    }
    
    // エリアDの更新準備（共通のsetAreaを呼び出す）
    public static function updateAreaD($viewPath, $data = []) { 
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::updateAreaD() path: {$viewPath} start...");
        
        self::logAndSet('d', $viewPath, $data); 
        
        $logger->debug("Screen::updateAreaD() finish.");
    }
    
    // エリアEの更新準備（共通のsetAreaを呼び出す）
//     public static function updateAreaE($viewPath, $data = []) { 
//         $logger = new \Framework\Core\Logger();
//         $logger->debug("Screen::updateAreaE() path: {$viewPath} start...");
        
//         self::logAndSet('e', $viewPath, $data); 
        
//         $logger->debug("Screen::updateAreaE() finish.");
//     }
    public static function updateAreaE($viewPath, $data = []) {
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::updateAreaE() [iframe起動版] path: {$viewPath} start...");
        
        // 通常通りHTMLコンテンツを取得
        $originalHtml = self::getContents($viewPath, $data);
        
        // ダブルクォーテーションの衝突防止エスケープ処理
        $escapedHtml = htmlspecialchars($originalHtml, ENT_QUOTES, 'UTF-8');
        
        // iframeで包んだHTMLを生成（高さはシステムのエリアEに合わせて適宜微調整してください）
        $iframeHtml = '<iframe srcdoc="' . $escapedHtml . '" style="width: 100%; height: 100%; min-height: 700px; border: none; margin: 0; padding: 0; overflow: auto;"></iframe>';
        
        // ストレージにセット
        self::$storage['areas']['e'] = $iframeHtml;
        
        $logger->debug("Screen::updateAreaE() finish.");
    }
    
    // エリアFの更新準備（共通のsetAreaを呼び出す）
    public static function updateAreaF($viewPath, $data = []) { 
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::updateAreaF() path: {$viewPath} start...");

        self::logAndSet('f', $viewPath, $data); 

        $logger->debug("Screen::updateAreaF() finish.");
    }
    
    // ロギングとセットを共通化
    private static function logAndSet($key, $viewPath, $data) {
        // getContentsを使ってHTMLを取得（置換ロジックも共通で使える）
        self::$storage['areas'][$key] = self::getContents($viewPath, $data);
    }
    
    // 格納された全エリアをJSONで一括出力
    public static function areaView() {
        $logger = new \Framework\Core\Logger();
        $logger->debug("Screen::areaView() start...");
        
        header('Content-Type: application/json');
        echo json_encode(self::$storage);
        $logger->debug("Screen::areaView() finish.");
        exit;
    }
}
