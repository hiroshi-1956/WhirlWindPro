<?php

namespace Develop\Controllers;

class ProjectDefinitionController extends \Develop\Utils\BaseController {
    
    public function initialAction() {
        $this->logger->debug("ProjectDefinitionController::initialAction() start...");
        
        try {
            // 💡 ProjectModel からプロジェクト一覧を取得
            $model = new \Develop\Models\ProjectModel();
            $projectList = $model->getProjectList();
            
            // 💡 取得した一覧を View にバインドしてエリアDを更新
            $viewData = [
                'projectList' => $projectList
            ];
            
            \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\ProjectDefinition\ProjectDefinitionList.view', $viewData);
            
        } catch (\Exception $e) {
            $this->logger->error("ProjectDefinitionController::initialAction() エラー: " . $e->getMessage());
            \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\ProjectDefinition\ProjectDefinitionList.view', ['projectList' => []]);
        }
        
        $this->logger->debug("ProjectDefinitionController::initialAction() finish.");
    }
    
    /**
     * プロジェクト選択時に詳細（修正画面）をエリアEに表示する
     */
    public function projectDetailAction() {
        $this->logger->debug("ProjectDefinitionController::projectDetailAction() start...");
        
        $projectId = \Develop\Utils\Request::post('id');
        
        // ProjectModel等から選択されたプロジェクトの全カラムデータを1件取得する
        $model = new \Develop\Models\ProjectModel();
        // ※必要に応じてModel側に1件取得用の「getProjectById」等を作ってください
        $project = $model->getProjectById($projectId);
        
        \Develop\Utils\Screen::updateAreaE(
            '\Develop\Views\AreaE\ProjectDefinition\ProjectDefinitionEdit.view',
            ['project' => $project]
            );
        
        $this->logger->debug("ProjectDefinitionController::projectDetailAction() finish.");
    }
    
    /**
     * プロジェクト定義の修正を実行する
     */
    public function updateProjectAction() {
        $this->logger->debug("ProjectDefinitionController::updateProjectAction() start...");
        
        try {
            // フォームから送られてきた各値を取得
            $project_id   = \Develop\Utils\Request::post('p_id');
            $project_name = \Develop\Utils\Request::post('p_name');
            $dsn          = \Develop\Utils\Request::post('p_dsn');
            $username     = \Develop\Utils\Request::post('p_username');
            $password     = \Develop\Utils\Request::post('p_password');
            $options      = \Develop\Utils\Request::post('p_options');
            
            if (empty($project_id) || empty($project_name)) {
                $this->logger->error("必須項目（プロジェクトIDまたは名前）が不足しています。");
                return;
            }
            
            // 💡 モデルのupdate処理を呼び出し
            $model = new \Develop\Models\ProjectModel();
            $model->updateProject($project_id, $project_name, $dsn, $username, $password, $options);
            
            // 💾 成功時の画面リフレッシュ処理
            // 1. 修正が終わったのでエリアE（修正画面）をクリア
            \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
            
            // 2. プロジェクト名が変更された可能性があるので、エリアDの一覧を再描画
            $projectList = $model->getProjectList();
            \Develop\Utils\Screen::updateAreaD(
                '\Develop\Views\AreaD\ProjectDefinition\ProjectDefinitionList.view',
                ['projectList' => $projectList]
                );
            
            // 3. フッターにメッセージを通知
            \Develop\Utils\Screen::updateAreaF("プロジェクト [{$project_name}] の定義を更新しました");
            
        } catch (\Exception $e) {
            $this->logger->error("ProjectDefinitionController::updateProjectAction() エラー: " . $e->getMessage());
        }
        
        $this->logger->debug("ProjectDefinitionController::updateProjectAction() finish.");
    }
    
    public function cancelEditAction() {
        $this->logger->debug("ProjectDefinitionController::cancelEdit() start...");
    
        try {
            // 💡 キャンセルされたので、エリアEの修正画面をクリア（非表示）にする
            \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
            
            // 下部のフッター（エリアF）にメッセージを表示したい場合は以下を有効にしてください
            \Develop\Utils\Screen::updateAreaF("プロジェクトの修正をキャンセルしました");
            
        } catch (\Exception $e) {
            $this->logger->error("ProjectDefinitionController::cancelEditAction() Error: " . $e->getMessage());
        }
        
        $this->logger->debug("ProjectDefinitionController::cancelEdit() finish.");
    }
}

