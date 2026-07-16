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
        $this->logger->debug("PartsDefinitionController::initialAction() start...");
        
        $projectId = $this->getProjectId();
        $this->logger->debug("PartsDefinitionController::initialAction() projectId : {$projectId}");
        
        $model = new \Develop\Models\PartsDefinitionModel();
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\PartsDefinition\PartsDefinitionList.view', [
            'parts_list' => $model->getAllPartsList($projectId),
            'project_id' => $projectId
        ]);
        
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        
        $this->logger->debug("PartsDefinitionController::initialAction() end.");
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
        $columns = [];
        return $this->responseJson([
            'result'  => 'success',
            'columns' => $columns
        ]);
    }
    
    /**
     * テーブル切り替え時などにカラム一覧を取得して画面を再描画する
     */
    public function getColumnsAction($requestParams = [])
    {
        $projectId = $this->getProjectId($requestParams);
        $partsId   = $requestParams['parts_id']   ?? $_POST['parts_id']   ?? '';
        $partsName = $requestParams['parts_name'] ?? $_POST['parts_name'] ?? '';
        $partsDesc = $requestParams['parts_description'] ?? $_POST['parts_description'] ?? '';
        $partsType = $requestParams['parts_type'] ?? $_POST['parts_type'] ?? '';
        $tableName = $requestParams['table_name'] ?? $_POST['table_name'] ?? '';
        
        $displayLabel = $requestParams['display_label'] ?? $_POST['display_label'] ?? 'physical';
        $columnFilter = $requestParams['column_filter'] ?? $_POST['column_filter'] ?? 'all';
        $previewTitle = $requestParams['preview_title'] ?? $_POST['preview_title'] ?? '';
        $inputRows    = $requestParams['input_rows']    ?? $_POST['input_rows']    ?? '1';
        
        // ✅【MultiFree用追加】
        $lineCounts      = $requestParams['line_counts']      ?? $_POST['line_counts']      ?? '1';
        $searchArea      = $requestParams['search_area']      ?? $_POST['search_area']      ?? '無';
        $informationArea = $requestParams['information_area'] ?? $_POST['information_area'] ?? '無';
        
        $inputStyle     = $requestParams['input_style']     ?? $_POST['input_style']     ?? '';
        $contents       = $requestParams['contents']        ?? $_POST['contents']        ?? '';
        $styleCondition = $requestParams['style_condition'] ?? $_POST['style_condition'] ?? '';
        $styleColumn    = $requestParams['style_column']    ?? $_POST['style_column']    ?? '';
        
        $model = new \Develop\Models\PartsDefinitionModel();
        $mTables = $model->getTableStructure($projectId);
        
        // 選択されたテーブルからカラム一覧を取得
        $columns = [];
        if (!empty($tableName)) {
            $columns = $model->getColumnsByTableId($tableName);
        }
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'is_edit'              => (!empty($partsId) && $partsId !== '0'),
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
            
            'line_counts'          => $lineCounts,
            'search_area'          => $searchArea,
            'information_area'     => $informationArea,
            
            'input_style'          => $inputStyle,
            'contents'             => $contents,
            'style_condition'      => $styleCondition,
            'style_column'         => $styleColumn,
            'm_tables'             => $mTables,
            'm_partsinfo_columns'  => $columns,
            'grids_config_json'    => json_encode([], JSON_UNESCAPED_UNICODE)
        ]);
    }
    
    /**
     * 既存の画面パーツの編集画面を開く
     */
    public function editPartsAction($requestParams = []) {
        $projectId = $this->getProjectId($requestParams);
        $partsId   = $requestParams['parts_id'] ?? $_POST['parts_id'] ?? '';
        
        $model = new \Develop\Models\PartsDefinitionModel();
        $targetParts = $model->getPartsById($projectId, $partsId);
        
        if (!$targetParts) {
            $this->logger->error("❌ 編集対象のパーツが見つかりません。ID: {$partsId}");
            return $this->initialAction();
        }
        
        $mTables = $model->getTableStructure($projectId);
        
        // 登録されているテーブル名からカラム一覧を取得
        $columns = [];
        if (!empty($targetParts['table_name'])) {
            $columns = $model->getColumnsByTableId($targetParts['table_name']);
        }
        
        // 保存されていたJSONカラムのデコード
        $checkedColumns = [];
        if (!empty($targetParts['checked_columns_json'])) {
            $checkedColumns = json_decode($targetParts['checked_columns_json'], true) ?: [];
        }
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'is_edit'              => true,
            'project_id'           => $projectId,
            'parts_id'             => $targetParts['parts_id'],
            'parts_name'           => $targetParts['parts_name'],
            'parts_description'    => $targetParts['parts_description'],
            'parts_type'           => $targetParts['parts_type'],
            'table_name'           => $targetParts['table_name'],
            'display_label'        => $targetParts['display_label'] ?? 'physical',
            'column_filter'        => $targetParts['column_filter'] ?? 'all',
            'preview_title'        => $targetParts['preview_title'] ?? '',
            'input_rows'           => $targetParts['input_rows'] ?? '1',
            
            'line_counts'          => $targetParts['line_counts'] ?? '1',
            'search_area'          => $targetParts['search_area'] ?? '無',
            'information_area'     => $targetParts['information_area'] ?? '無',
            
            'input_style'          => $targetParts['input_style'] ?? '',
            'contents'             => $targetParts['contents'] ?? '',
            'style_condition'      => $targetParts['style_condition'] ?? '',
            'style_column'         => $targetParts['style_column'] ?? '',
            'm_tables'             => $mTables,
            'm_partsinfo_columns'  => $columns,
            'checked_columns'      => $checkedColumns,
            'grids_config_json'    => json_encode([], JSON_UNESCAPED_UNICODE)
        ]);
    }
    
    /**
     * 画面パーツ情報をDBに保存する
     */
    public function saveAction($requestParams = []) {
        $this->logger->debug("PartsDefinitionController::saveAction() start...");
        
        $projectId = $this->getProjectId($requestParams);
        $partsId   = $requestParams['parts_id'] ?? $_POST['parts_id'] ?? '';
        
        $data = [
            'parts_name'        => $requestParams['parts_name']        ?? $_POST['parts_name']        ?? '',
            'parts_description' => $requestParams['parts_description'] ?? $_POST['parts_description'] ?? '',
            'parts_type'        => $requestParams['parts_type']        ?? $_POST['parts_type']        ?? '',
            'table_name'        => $requestParams['table_name']        ?? $_POST['table_name']        ?? '',
            'display_label'     => $requestParams['display_label']     ?? $_POST['display_label']     ?? 'physical',
            'column_filter'     => $requestParams['column_filter']     ?? $_POST['column_filter']     ?? 'all',
            'preview_title'     => $requestParams['preview_title']     ?? $_POST['preview_title']     ?? '',
            'input_rows'        => $requestParams['input_rows']        ?? $_POST['input_rows']        ?? '1',
            
            'line_counts'        => $requestParams['line_counts']        ?? $_POST['line_counts']        ?? '1',
            'search_area'        => $requestParams['search_area']        ?? $_POST['search_area']        ?? '無',
            'information_area'   => $requestParams['information_area']   ?? $_POST['information_area']   ?? '無',
            
            'selected_columns'  => $requestParams['selected_columns']  ?? $_POST['selected_columns']  ?? [],
            'input_style'       => $requestParams['input_style']       ?? $_POST['input_style']       ?? '',
            'contents'          => $requestParams['contents']          ?? $_POST['contents']          ?? '',
            'style_condition'   => $requestParams['style_condition']   ?? $_POST['style_condition']   ?? '',
            'style_column'      => $requestParams['style_column']      ?? $_POST['style_column']      ?? ''
        ];
        
        try {
            $model = new \Develop\Models\PartsDefinitionModel();
            $model->saveParts($projectId, $partsId, $data);
            
            // 保存成功後はリスト一覧を再描画して右側エリアをクリア
            $this->initialAction();
        } catch (\Exception $e) {
            $this->logger->error("❌ Parts保存処理でエラーが発生しました: " . $e->getMessage());
        }
    }
    
    public function cancelAction() {
        $this->logger->debug("PartsDefinitionController::cancelAction() start...");
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        $this->logger->debug("PartsDefinitionController::cancelAction() end.");
    }
}
