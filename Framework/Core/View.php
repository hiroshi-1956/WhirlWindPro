<?php

namespace Framework\Core;

/**
 * View Class (V1.51)
 * 1つのインスタンスで1つのHTML成果物を管理・生成するステートフルなViewエンジン。
 * 文字列置換ベースのため、PHPの実行タイミングに縛られない柔軟な操作が可能。
 */
class View {
    private $html = ""; // 内部に保存されるHTML（ビルド中の状態）
    private $vars = []; // assign/renderされた変数の保持
    
    /**
     * 1. startView
     * 引数で渡されたHTML文字列をそのまま内部に保存する。
     * * @param string $htmlContent 直接扱うHTMLソース
     * @return string 現在のHTML
     */
    public function startView($htmlContent) {
        $this->html = $htmlContent;
        return $this->html;
    }
    
    /**
     * 2. importView
     * 指定されたパスのファイルを読み込んで内部に保存する。
     * パスは呼び出し側（Controller等）が責任を持ってフルパスを指定する。
     * * @param string $absolutePath テンプレートファイルへの絶対パス
     * @return string 現在のHTML
     */
    public function importView($absolutePath) {
        // 拡張子 .view の補完
        $path = (strpos($absolutePath, '.view') === false) ? $absolutePath . '.view' : $absolutePath;
        
        if (!file_exists($path)) {
            $this->html = "View file not found: {$path}";
        } else {
            $this->html = file_get_contents($path);
        }
        return $this->html;
    }
    
    /**
     * 3. assign
     * 内部HTMLに含まれる {$key} を指定した値で置換する。
     * * @param string $key 変数名
     * @param mixed $value 置換する値
     * @return string 置換後のHTML
     */
    public function assign($key, $value) {
        $this->vars[$key] = $value;
        // {$変数名} という形式を物理的に置き換え
        $this->html = str_replace("{\${$key}}", $value, $this->html);
        return $this->html;
    }
    
    /**
     * 4. render
     * 連想配列を受け取り、一括で変数置換を実行する。
     * * @param array $data ['変数名' => '値', ...]
     * @return string 全置換後のHTML
     */
    public function render($data = []) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $this->assign($key, $value);
            }
        }
        return $this->html;
    }
    
    /**
     * 5. display
     * 内部に保存・構築された最終的なHTMLを画面に出力する。
     */
    public function display() {
        
        //$logger = Container::get('logger');
        //$logger->debug("View::display() {$this->html}");
        
        echo $this->html;
    }
    
    /**
     * 6. export
     * 構築されたHTMLを物理ファイルとして保存する（PDF生成用やキャッシュ用）。
     * * @param string $savePath 保存先のフルパス
     * @return string 保存されたHTML
     */
    public function export($savePath) {
        $dir = dirname($savePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($savePath, $this->html);
        return $this->html;
    }
    
    /**
     * getHtml
     * 現在内部に保持しているHTML文字列を取得する。
     * * @return string
     */
    public function getHtml() {
        return $this->html;
    }
    
    public function fetch($path) {
        // 1. ファイルを読み込んで内部状態(html)を更新
        $this->importView($path);
        
        // 2. すでに assign されている変数があれば一括置換を適用
        if (!empty($this->vars)) {
            foreach ($this->vars as $key => $value) {
                // assign() メソッドと同じ置換ルール {$変数名} を適用
                $this->html = str_replace("{\${$key}}", $value, $this->html);
            }
        }
        
        // 3. 解析済みのHTMLを返す
        return $this->html;
    }
}

