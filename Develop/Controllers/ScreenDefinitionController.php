<?php

namespace Develop\Controllers;

class ScreenDefinitionController extends \Develop\Utils\BaseController {
    
    private const VIEW_REGIST = '\Develop\Views\AreaE\ScreenDefinition\RegistScreen.view';
    private const SESSION_PROJECT_KEY = 'project_id';
    
    private function getProjectId($requestParams = []) {
        return $requestParams['project_id']
        ?? $_POST['project_id']
        ?? $_GET['project_id']
        ?? $_SESSION[self::SESSION_PROJECT_KEY]
        ?? '';
    }
    
    public function initialAction() {
        $this->logger->debug("ScreenDefinitionController::initialAction() start...");
        
        $projectId = $this->getProjectId();
        $model = new \Develop\Models\ScreenDefinitionModel();
        
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\ScreenDefinition\ScreenDefinitionList.view', [
            'screen_list' => $model->getAllScreenList($projectId),
            'project_id' => $projectId
        ]);
        
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        
        $this->logger->debug("ScreenDefinitionController::initialAction() end.");
    }
    
    public function newScreenAction() {
        $this->logger->debug("ScreenDefinitionController::newScreenAction() start...");
        
        $projectId = $this->getProjectId();
        unset($_SESSION['wwProject_main_form_backup']);
        
        $partsModel = new \Develop\Models\PartsDefinitionModel();
        $screenParts = $partsModel->getAllPartsList($projectId);
        
        // 【完全同期対応】各パーツに紐づくテーブルのカラム情報をPHP側で事前に一括取得しておく
        $model = new \Develop\Models\ScreenDefinitionModel();
        foreach ($screenParts as &$part) {
            $tableName = $part['table_name'] ?? '';
            if (!empty($projectId) && !empty($tableName)) {
                $part['columns'] = $model->getColumnsByTableName($projectId, $tableName);
            } else {
                $part['columns'] = [];
            }
        }
        unset($part);
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'is_edit'           => false,
            'project_id'        => $projectId,
            'screen_id'         => '',
            'screen_name'       => '',
            'screen_description'=> '',
            'rows_count'        => 3,
            'cols_count'        => 2,
            'grids_config_json' => json_encode([], JSON_UNESCAPED_UNICODE),
            'screen_parts'      => $screenParts
        ]);
        
        $this->logger->debug("ScreenDefinitionController::newScreenAction() end.");
    }
    
    public function editAction($requestParams = []) {
        $this->logger->debug("ScreenDefinitionController::editAction() start...");
        
        $projectId = $this->getProjectId($requestParams);
        $screenId  = $requestParams['screen_id'] ?? $_POST['screen_id'] ?? '';
        
        unset($_SESSION['wwProject_main_form_backup']);
        
        if (empty($screenId)) {
            $this->logger->error("ScreenDefinitionController::editAction() ❌ screen_id が空です");
            return;
        }
        
        $model = new \Develop\Models\ScreenDefinitionModel();
        $screenData = $model->getScreenData($projectId, $screenId);
        
        $partsModel = new \Develop\Models\PartsDefinitionModel();
        $screenParts = $partsModel->getAllPartsList($projectId);
        
        // 【完全同期対応】各パーツに紐づくテーブルのカラム情報をPHP側で事前に一括取得しておく
        foreach ($screenParts as &$part) {
            $tableName = $part['table_name'] ?? '';
            if (!empty($projectId) && !empty($tableName)) {
                $part['columns'] = $model->getColumnsByTableName($projectId, $tableName);
            } else {
                $part['columns'] = [];
            }
        }
        unset($part);
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'is_edit'            => true,
            'project_id'         => $projectId,
            'screen_id'          => $screenData['screen_id'] ?? $screenId,
            'screen_name'        => $screenData['screen_name'] ?? '',
            'screen_description' => $screenData['screen_description'] ?? '',
            'rows_count'         => $screenData['rows_count'] ?? 1,
            'cols_count'         => $screenData['cols_count'] ?? 1,
            'grids_config_json'  => $screenData['grids_config_json'] ?? '{}',
            'screen_parts'       => $screenParts
        ]);
        
        $this->logger->debug("ScreenDefinitionController::editAction() end.");
    }
    
    public function saveAction($requestParams = []) {
        $this->logger->debug("ScreenDefinitionController::saveAction() start...");
        
        $projectId = $this->getProjectId($requestParams);
        $screenId  = $requestParams['screen_id'] ?? $_POST['screen_id'] ?? '';
        
        $data = [
            'screen_name'        => $requestParams['screen_name']        ?? $_POST['screen_name']        ?? '',
            'screen_description' => $requestParams['screen_description'] ?? $_POST['screen_description'] ?? '',
            'rows_count'         => $requestParams['rows_count']         ?? $_POST['rows_count']         ?? 1,
            'cols_count'         => $requestParams['cols_count']         ?? $_POST['cols_count']         ?? 1,
            'grids_config_json'  => $requestParams['grids_config_json']  ?? $_POST['grids_config_json']  ?? '{}'
        ];
        
        $model = new \Develop\Models\ScreenDefinitionModel();
        $resultScreenId = $model->saveScreen($projectId, $screenId, $data);
        
        if ($resultScreenId !== false) {
            unset($_SESSION['wwProject_main_form_backup']);
            
            \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\ScreenDefinition\ScreenDefinitionList.view', [
                'screen_list' => $model->getAllScreenList($projectId),
                'project_id'  => $projectId
            ]);
            
            \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        } else {
            $this->logger->error("❌ Modelでの画面保存処理に失敗しました。");
        }
        
        $this->logger->debug("ScreenDefinitionController::saveAction() end.");
    }
    
    public function cancelAction() {
        $this->logger->debug("ScreenDefinitionController::cancelAction() start...");
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        $this->logger->debug("ScreenDefinitionController::cancelAction() end.");
    }
}