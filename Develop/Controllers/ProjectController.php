<?php
namespace Develop\Controllers;

use Develop\Models\ProjectModel;

class ProjectController extends \Develop\Utils\BaseController {
    
    // モデルを保持するプロパティを明示
    protected $projectModel;
    
    public function __construct() {
        parent::__construct();
        // コンストラクタでモデルを生成
        $this->projectModel = new ProjectModel();
    }
    
    public function startProjectNabi() {
        // モデルの正しいメソッド名を呼ぶ
        $projects = $this->projectModel->getProjectList();
        
        // セッション関数（共通関数として定義されている前提）
        $activeId = getSessionValue('active_project');
        $activeFunc = getSessionValue('active_function');
        
        $html = "";
        // $projects が空でないかチェック
        if ($projects && is_array($projects)) {
            foreach ($projects as $p) {
                // ProjectModelのSQLカラム名 'project_name' を使用
                $name = $p['project_name'];
                $isA = ($name === $activeId);
                $mark = $isA ? "○" : "×";
                $activeClass = $isA ? "active" : "";
                
                $html .= "<div class='menu-item {$activeClass}' data-name='{$name}'>{$mark} {$name}</div>";
                
                if ($isA) {
                    // 機能一覧の取得（未実装なら別途作成）
                    $html .= $this->getFunctionListHtml($activeFunc);
                }
            }
        }
        return $html;
    }
}