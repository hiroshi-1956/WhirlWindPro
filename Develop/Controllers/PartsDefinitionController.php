<?php

namespace Develop\Controllers;

class PartsDefinitionController extends \Develop\Utils\BaseController {
    
    private const VIEW_REGIST = '\Develop\Views\AreaE\PartsDefinition\RegistParts.view';
    private const SESSION_PROJECT_KEY = 'project_id';
    
    private function getProjectId($requestParams = []) {
        return $requestParams['project_id']
        ?? $_POST['project_id']
        ?? $_GET['project_id']
        ?? $_SESSION[self::SESSION_PROJECT_KEY]
        ?? '';
    }
    
    public function initialAction() {
        $this->logger->debug("DBDefinitionController::initialAction() start...");
        
        $projectId = $this->getProjectId();
        $this->logger->debug("PartsDefinitionController::initialAction() projectId : {$projectId}");
        
        $model = new \Develop\Models\PartsDefinitionModel();
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\PartsDefinition\PartsDefinitionList.view', [
            'parts_list' => $model->getAllPartsList($projectId),
            'project_id' => $projectId
        ]);
        
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        
        $this->logger->debug("DBDefinitionController::initialAction() end.");
    }
    
    public function newPartsAction() {
        $this->logger->debug("PartsDefinitionController::newPartsAction() start...");
        
        $projectId = $this->getProjectId();
        unset($_SESSION['wwProject_main_form_backup']);
        
        $model = new \Develop\Models\PartsDefinitionModel();
        $mTables = $model->getTableStructure($projectId);
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'is_edit'           => false,
            'project_id'        => $projectId,
            'parts_id'          => '',
            'parts_name'        => '',
            'parts_description' => '',
            'm_tables'          => $mTables,
            'grids_config_json' => json_encode([], JSON_UNESCAPED_UNICODE)
        ]);
        
        $this->logger->debug("PartsDefinitionController::newPartsAction() end.");
    }
    
    public function getTableColumnsAction()
    {
        $tableName = $this->getParam('table_name');
        $columns = [];
        return $this->responseJson([
            'result'  => 'success',
            'columns' => $columns
        ]);
    }
    
    public function getColumnsAction($requestParams = [])
    {
        $this->logger->debug("PartsDefinitionController::getColumnsAction() start...");
        
        $tableName = $requestParams['table_name'] ?? $_POST['table_name'] ?? $_REQUEST['table_name'] ?? '';
        
        $partsId   = $requestParams['parts_id']   ?? $_POST['parts_id']   ?? '';
        $partsName = $requestParams['parts_name'] ?? $_POST['parts_name'] ?? '';
        $partsDesc = $requestParams['parts_description'] ?? $_POST['parts_description'] ?? '';
        $partsType = $requestParams['parts_type'] ?? $_POST['parts_type'] ?? 'Single Record Input';
        
        $displayLabel = $requestParams['display_label'] ?? $_POST['display_label'] ?? 'physical';
        $columnFilter = $requestParams['column_filter'] ?? $_POST['column_filter'] ?? 'all';
        $previewTitle = $requestParams['preview_title'] ?? $_POST['preview_title'] ?? '';
        $inputRows    = $requestParams['input_rows']    ?? $_POST['input_rows']    ?? '1';
        
        $projectId = $this->getProjectId($requestParams);
        $model = new \Develop\Models\PartsDefinitionModel();
        
        $columns = [];
        if (!empty($tableName)) {
            $columns = $model->getColumnsByTableId($tableName);
        }
        
        $mTables = $model->getTableStructure($projectId);
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'is_edit'              => false,
            'project_id'           => $projectId,
            'parts_id'             => $partsId,
            'parts_name'           => $partsName,
            'parts_description'    => $partsDesc,
            'parts_type'           => $partsType,
            'table_name'           => $tableName,
            'display_label'        => $displayLabel,
            'column_filter'        => $columnFilter,
            'preview_title'        => $previewTitle,
            'input_rows'           => $inputRows,
            'm_tables'             => $mTables,
            'm_partsinfo_columns'  => $columns,
            'grids_config_json'    => json_encode([], JSON_UNESCAPED_UNICODE)
        ]);
        
        $this->logger->debug("PartsDefinitionController::getColumnsAction() end.");
    }
    
    public function cancelAction() {
        $this->logger->debug("PartsDefinitionController::cancelAction() start...");
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        $this->logger->debug("PartsDefinitionController::cancelAction() end.");
    }
    
    public function editPartsAction() {
        $this->logger->debug("PartsDefinitionController::editPartsAction() start...");
        
        $projectId = $this->getProjectId();
        $partsId   = $_POST['parts_id'] ?? $_REQUEST['parts_id'] ?? '';
        
        if (empty($partsId)) {
            $this->logger->error("PartsDefinitionController::editPartsAction() ❌ parts_id が空です");
            return;
        }
        
        $model = new \Develop\Models\PartsDefinitionModel();
        $targetParts = $model->getPartsById($projectId, $partsId);
        
        if (!$targetParts) {
            $this->logger->error("PartsDefinitionController::editPartsAction() ❌ データが見つかりません。ID: {$partsId}");
            return;
        }
        
        $tableName = $targetParts['table_name'] ?? '';
        $columns = [];
        if (!empty($tableName)) {
            $columns = $model->getColumnsByTableId($tableName);
        }
        $mTables = $model->getTableStructure($projectId);
        
        $checkedColumns = [];
        if (!empty($targetParts['checked_columns_json'])) {
            $rawStr = trim($targetParts['checked_columns_json']);
            $rawArray = explode(',', $rawStr);
            $checkedColumns = array_filter(array_map('trim', $rawArray), 'strlen');
        }
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'is_edit'              => true,
            'project_id'           => $projectId,
            'parts_id'             => $targetParts['parts_id'],
            'parts_name'           => $targetParts['parts_name'],
            'parts_description'    => $targetParts['parts_description'],
            'parts_type'           => $targetParts['parts_type'],
            'table_name'           => $tableName,
            'display_label'        => $targetParts['display_label'] ?? 'physical',
            'column_filter'        => $targetParts['column_filter'] ?? 'all',
            'preview_title'        => $targetParts['preview_title'] ?? '',
            'input_rows'           => $targetParts['input_rows'] ?? '1',
            'input_style'          => $targetParts['input_style'] ?? '',
            'm_tables'             => $mTables,
            'm_partsinfo_columns'  => $columns,
            'checked_columns'      => $checkedColumns,
            'grids_config_json'    => json_encode([], JSON_UNESCAPED_UNICODE)
        ]);
        
        $this->logger->debug("PartsDefinitionController::editPartsAction() end.");
    }
    
    public function saveAction($requestParams = []) {
        $this->logger->debug("PartsDefinitionController::saveAction() start...");
        
        $projectId = $this->getProjectId($requestParams);
        $partsId   = $requestParams['parts_id'] ?? $_POST['parts_id'] ?? '';
        
        // 💡【最重要】'input_rows' を確実に回収できるように配列内へ追加しました
        $data = [
            'parts_name'        => $requestParams['parts_name']        ?? $_POST['parts_name']        ?? '',
            'parts_description' => $requestParams['parts_description'] ?? $_POST['parts_description'] ?? '',
            'parts_type'        => $requestParams['parts_type']        ?? $_POST['parts_type']        ?? '',
            'table_name'        => $requestParams['table_name']        ?? $_POST['table_name']        ?? '',
            'display_label'     => $requestParams['display_label']     ?? $_POST['display_label']     ?? 'physical',
            'column_filter'     => $requestParams['column_filter']     ?? $_POST['column_filter']     ?? 'all',
            'preview_title'     => $requestParams['preview_title']     ?? $_POST['preview_title']     ?? '',
            'input_rows'        => $requestParams['input_rows']        ?? $_POST['input_rows']        ?? '1',
            'selected_columns'  => $requestParams['selected_columns']  ?? $_POST['selected_columns']  ?? [],
            'input_style'       => $requestParams['input_style']       ?? $_POST['input_style']       ?? ''
        ];
        
        $model = new \Develop\Models\PartsDefinitionModel();
        $resultPartsId = $model->saveParts($projectId, $partsId, $data);
        
        if ($resultPartsId !== false) {
            unset($_SESSION['wwProject_main_form_backup']);
            
            \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\PartsDefinition\PartsDefinitionList.view', [
                'parts_list' => $model->getAllPartsList($projectId),
                'project_id' => $projectId
            ]);
            
            \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        } else {
            $this->logger->error("❌ Modelでの保存処理に失敗しました。");
        }
        
        $this->logger->debug("PartsDefinitionController::saveAction() end.");
    }
}
