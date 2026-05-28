<?php

namespace Develop\Controllers;

use Develop\Models\CodeMasterModel;

class ProjectController extends \Develop\Utils\BaseController {
    
    /**
     * 初回表示用：メインボディとフッターを組み合わせて返す
     */
    public function startProjectNabi() {
        $this->logger->debug("ProjectController::startProjectNabi() start...");
        
        $html = '<div class="navi-list-body">';
        $html .= $this->getProjectListHtml();
        $html .= '</div>';
        
        // フッター（ProjectLock）を結合
        $html .= $this->getNaviFooterHtml();
        
        $this->logger->debug("ProjectController::startProjectNabi() finish.");
        return $html;
    }
   
    private function getProjectListHtml() {
        $this->logger->debug("ProjectController::getProjectListHtml() start...");
        
        $model = new \Develop\Models\ProjectModel();
        $projectList = $model->getProjectList();
        
        $codeMaster = new CodeMasterModel();
        $functionList = $codeMaster->getCodesByGroup('PROJECT_MENU');
        
        $projectId = \Develop\Utils\Session::get('project_id');
        $functionId = \Develop\Utils\Session::get('function_id');
        
        // ★ProjectLockの状態を取得
        $isLocked = \Develop\Utils\Session::get('project_lock_status') ?? false;
        // ロック中ならグレーアウトし、クリックイベントを無効化するスタイル
        $disabledStyle = $isLocked ? 'opacity: 0.6; cursor: not-allowed; pointer-events: none;' : 'cursor: pointer;';
        
        $html = '<div class="project-list-container">';
            
        // 【新規プロジェクト登録】ボタン
        $html .= sprintf(
            '<div class="menu-item add-project-btn" data-name="PROJECT_ADD" onclick="bridge({code: \'Project/registProject\'})" style="%s">' .
            '<span class="add-icon">+</span>新規プロジェクト登録' .
            '</div>',
            $disabledStyle
            );
        
        if (is_array($projectList) && count($projectList) > 0) {
            foreach ($projectList as $project) {
                $currentProjectId = $project['project_id'];
                
                // ★修正ポイント：ロック中なら onclick を出力しない、そうでなければ bridge を出力
                $onClickAttr = $isLocked ? '' : sprintf('onclick="bridge({code: \'Project/projectSelect\', id: \'%s\'})"', htmlspecialchars($currentProjectId, ENT_QUOTES));
                
                $html .= sprintf(
                    '<div class="menu-item" %s style="%s">%s</div>',
                    $onClickAttr,
                    $disabledStyle,
                    htmlspecialchars($project['project_name'], ENT_QUOTES)
                    );
                
                if ($currentProjectId === $projectId) {
                    foreach ($functionList as $function) {
                        $isSelected = ($function['code_key'] === $functionId) ? ' active' : '';
                        
                        // 機能メニューも同様にロック制御
                        $onFuncClick = $isLocked ? '' : sprintf('onclick="bridge({code: \'Project/functionSelect\', id: \'%s\'})"', htmlspecialchars($function['code_key'], ENT_QUOTES));
                        
                        $onClickCode = !empty($function['description'])
                        ? sprintf("bridge({code: '%s'})", htmlspecialchars($function['description'], ENT_QUOTES))
                        : sprintf("bridge({code: 'Project/functionSelect', id: '%s'})", htmlspecialchars($function['code_key'], ENT_QUOTES));
                        
                        $onFuncClick = $isLocked ? '' : 'onclick="' . $onClickCode . '"';
                        
                        $html .= sprintf(
                            '<div class="menu-item%s" %s style="padding: 4px 30px; font-size: 0.85em; background: #34495e; min-height: auto; line-height: 1.6; %s">%s</div>',
                            $isSelected,
                            $onFuncClick,
                            $disabledStyle,
                            htmlspecialchars($function['code_name'], ENT_QUOTES)
                            );
                    }
                }
            }
        } else {
            $html .= '<div style="padding:20px; font-size:12px; color:#95a5a6;">プロジェクトがありません</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    public function projectSelectAction() {
        $this->logger->debug("ProjectController::projectSelectAction() start...");

        $projectId = \Develop\Utils\Request::post('id');
        $activeProjectId = \Develop\Utils\Session::get('project_id');
        
        // トグル処理
        if ($projectId === $activeProjectId) {
            $projectId = null;
        }
        
        \Develop\Utils\Session::set('project_id', $projectId);
        
        // プロジェクト名の取得
        $projectMap = \Develop\Utils\Session::get('project_map');
        $projectName = ($projectId && isset($projectMap[$projectId])) ? $projectMap[$projectId] : '';
        \Develop\Utils\Session::set('project_name', $projectName);
        
        // エリアB更新
        $loginUserName = \Develop\Utils\Session::get('user_name');
        $displayProject = $projectName ? $projectName . ' ( ' . $projectId . ' )' : '';
        $dataB = [ 'User_Name' => $loginUserName, 'Selected_Project_Name' => $displayProject ];
        \Develop\Utils\Screen::updateAreaB('\Develop\Views\AreaB\Area_B.view', $dataB);
        
        // エリアC更新：リストボディとフッターの両方をセットにする
        $html = '<div class="navi-list-body">';
        $html .= $this->getProjectListHtml();
        $html .= '</div>';
        $html .= $this->getNaviFooterHtml();
        \Develop\Utils\Screen::updateAreaC('\Develop\Views\AreaC\Area_C.view', [ 'Area_C_Html' => $html ]);
            
        $this->logger->debug("ProjectController::projectSelectAction() finish.");
    }
    
    private function getNaviFooterHtml() {
        $this->logger->debug("ProjectController::getNaviFooterHtml() start...");
        
        // セッションから現在のロック状態を取得（なければfalse）
        $isLocked = \Develop\Utils\Session::get('project_lock_status') ?? false;
        $lockChecked = $isLocked ? 'checked' : '';
        $lockActive = $isLocked ? ' active' : '';
        
        $html = sprintf(
            '<div class="navi-footer">' .
            '<div class="menu-item project-lock-item%s" style="border:none;">' .
            '<input type="checkbox" class="menu-check" id="check_lock" %s ' .
            ' onclick="bridge({code: \'Project/lock\', locked: this.checked})"> ' .
            '<label for="check_lock" style="cursor:pointer; color:#fff; margin-left:10px; flex:1;">ProjectLock</label>' .
            '</div>' .
            '</div>',
            $lockActive,
            $lockChecked
            );
            
        $this->logger->debug("ProjectController::getNaviFooterHtml() finish.");
        
        return $html;
    }
    
    /**
     * プロジェクトロック状態の切り替えアクション
     * JavaScriptからは {code: 'Project/lock'} として呼び出されることを想定
     */
    public function lockAction() {
        $this->logger->debug("ProjectController::lockAction() start...");
        
        try {
            // 1. JavaScript (this.checked) から届いた true/false を取得
            // bridge経由のPOSTデータを取り込み
            $lockedParam = \Develop\Utils\Request::post('locked');
            $isLocked = ($lockedParam === 'true');
            
            $this->logger->debug("ProjectController::lockAction() Request Locked: " . ($isLocked ? 'true' : 'false'));
            
            // 2. セッションに現在のロック状態を保存
            // これにより、他のメソッド（getProjectListHtmlなど）がこの値を参照できるようになる
            \Develop\Utils\Session::set('project_lock_status', $isLocked);
            
            // 3. エリアC（ナビゲーション全体）を再描画
            // startProjectNabiを呼ぶことで、リストボディとフッターの両方が最新状態で生成される
            $html = $this->startProjectNabi();
            
            // Viewを指定してエリアCを更新
            \Develop\Utils\Screen::updateAreaC('\Develop\Views\AreaC\Area_C.view', [ 'Area_C_Html' => $html ]);
            
        } catch (\Exception $e) {
            $this->logger->error("ProjectController::lockAction() Error: " . $e->getMessage());
        }
        
        $this->logger->debug("ProjectController::lockAction() finish.");
    }
    
    public function registProjectAction() {
        $this->logger->debug("ProjectController::registProjectAction() start...");
        
        \Develop\Utils\Session::set('project_lock_status', true);
        $html = $this->startProjectNabi();
        \Develop\Utils\Screen::updateAreaC('\Develop\Views\AreaC\Area_C.view', [ 'Area_C_Html' => $html ]);
        
        $view_data = [ 'title' => '新規プロジェクト登録' ];
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaDE\Area_DE_Form.view', $view_data);
        
        $this->updateAreaF('新規プロジェクト登録');
        
        $this->logger->debug("ProjectController::registProjectAction() finish.");
    }
    
    /**
     * フォームからの保存リクエストを処理する
     */
    public function saveProjectAction() {
        $this->logger->debug("ProjectController::saveProjectAction() start...");
        
        // View側のJavaScriptで定義したキー名と一致させます
        $project_id   = \Develop\Utils\Request::post('p_id');
        $project_name = \Develop\Utils\Request::post('p_name');
        $description  = \Develop\Utils\Request::post('p_desc');
        
        $this->logger->debug("取り込み確認：ID={$project_id}, Name={$project_name}, Desc={$description}");
        
        if (!empty($project_name)) {
            // DB保存処理へ
            $model = new \Develop\Models\ProjectModel();
            $model->registProject($project_id, $project_name, $description);
            
            // 保存成功後にロック解除
            \Develop\Utils\Session::set('project_lock_status', false);
            
            // 画面クリーンアップ
            \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\Area_D_clear.view', []);
            \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
            
            $this->updateAreaF("プロジェクト [{$project_name}] を登録しました");
            
            // メニュー再描画
            $html = $this->startProjectNabi();
            \Develop\Utils\Screen::updateAreaC('\Develop\Views\AreaC\Area_C.view', [ 'Area_C_Html' => $html ]);
        } else {
            $this->logger->error("プロジェクト名が空のため保存をスキップしました。");
        }
        
        $this->logger->debug("ProjectController::saveProjectAction() finish.");
    }

    public function cancelProjectAction() {
        $this->logger->debug("ProjectController::cancelProjectAction() start...");

        \Develop\Utils\Session::set('project_lock_status', false);
        
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\Area_D_clear.view', []);
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);

        $this->updateAreaF("プロジェクト登録はキャンセルされました");
        
        $projectCtrl = new \Develop\Controllers\ProjectController();
        $html = $projectCtrl->startProjectNabi();
        \Develop\Utils\Screen::updateAreaC('\Develop\Views\AreaC\Area_C.view', [ 'Area_C_Html' => $html ]);
        $this->logger->debug("ProjectController::cancelProjectAction() finish.");
    }
    
    public function updateAreaF($message) {
        $this->logger->debug("ProjectController::updateAreaF() start...");
        
        \Develop\Utils\Session::set('Footer_Message', $message);
        $dataF = [ 'Footer_Message' => \Develop\Utils\Session::get('Footer_Message'), 'Current_Year' => '2026', 'Company_Name' => 'WhirlWindPro Team.' ];
        \Develop\Utils\Screen::updateAreaF('\Develop\Views\AreaF\Area_F.view', $dataF);
    
        $this->logger->debug("ProjectController::updateAreaF() finish.");
    }
}
