<?php

namespace Develop\Controllers;

class DBDefinitionController extends \Develop\Utils\BaseController {
    
    private $tableName = '';
    private $logicalName = '';
    private $tableType = '';
    private $description = '';
    
    private $col_physical = [];
    private $col_logical = [];
    private $col_type = [];
    private $col_length = [];
    private $col_primary = [];
    private $col_null = [];
    private $col_unique = [];
    private $col_default = [];
    
    private const VIEW_REGIST = '\Develop\Views\AreaE\DBDefinition\RegistTable.view';
    private const SESSION_PROJECT_KEY = 'project_id';
    
    public function initialAction() {
        $this->logger->debug("DBDefinitionController::initialAction() start...");
        
        $project_id = $_SESSION[self::SESSION_PROJECT_KEY];
        $model = new \Develop\Models\DBDefinitionModel();
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\DBDefinition\DBDefinitionList.view', [
            'table_list' => $model->getTableList($project_id)
        ]);
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        
        $this->logger->debug("DBDefinitionController::initialAction() end.");
    }
    
    public function newTableAction() {
        $this->logger->debug("DBDefinitionController::newTableAction() start...");
        
        $initialCols = [];
        for ($i = 0; $i < 2; $i++) {
            $initialCols[] = [
                'physical' => '', 'logical' => '', 'type' => 'varchar', 'length' => '',
                'primary' => '', 'null' => '', 'unique' => '', 'default' => ''
            ];
        }
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'import_error_msg' => '',
            'table_name'   => '',
            'logical_name' => '',
            'table_type'   => 'table',
            'description'  => '',
            'imported_cols'=> $initialCols,
            'view_info'    => null,
            'all_tables_map' => $this->getTargetTablesMap() // 💡追加
        ]);
        
        $this->logger->debug("DBDefinitionController::newTableAction() end.");
    }
    
    public function editTableAction() {
        $this->logger->debug("DBDefinitionController::editTableAction() start...");
        
        $rawString = $_POST['data'] ?? file_get_contents('php://input');
        $params = [];
        parse_str($rawString, $params);
        $targetTableName = $params['tableName'] ?? '';
        
        if (empty($targetTableName)) {
            $this->logger->error("DBDefinitionController::editTableAction() 対象テーブル名が空です。");
            return;
        }
        
        $model = new \Develop\Models\DBDefinitionModel();
        $tableInfo = $model->getTableInfo($targetTableName);
        if (!$tableInfo) {
            $this->logger->error("DBDefinitionController::editTableAction() テーブル情報が見つかりません。 [{$targetTableName}]");
            return;
        }
        
        $savedCols = $model->getSavedColumns($tableInfo['table_id']);
        
        if (count($savedCols) < 2) {
            while (count($savedCols) < 2) {
                $savedCols[] = [
                    'physical' => '', 'logical' => '', 'type' => 'varchar', 'length' => '',
                    'primary' => '', 'null' => '', 'unique' => '', 'default' => ''
                ];
            }
        }
        
        $viewInfo = null;
        $descriptionDisplay = $tableInfo['description'];
        if ($tableInfo['table_type'] === 'view' && !empty($tableInfo['description'])) {
            $decoded = json_decode($tableInfo['description'], true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['is_view_meta'])) {
                $viewInfo = $decoded;
                $descriptionDisplay = $decoded['description'] ?? '';
            }
        }
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'table_name'        => $tableInfo['physical_name'],
            'logical_name'      => $tableInfo['logical_name'],
            'table_type'        => $tableInfo['table_type'],
            'description'       => $descriptionDisplay,
            'imported_cols'     => $savedCols,
            'import_error_msg'  => '',
            'is_edit_mode'      => true,
            'view_info'         => $viewInfo,
            'all_tables_map'    => $this->getTargetTablesMap() // 💡追加
        ]);
        
        $this->logger->debug("DBDefinitionController::editTableAction() finish.");
        \Develop\Utils\Screen::areaView();
    }
    
    public function registerAction() {
        $this->logger->debug("DBDefinitionController::registerAction() start...");
        
        $this->getAllParameters();
        
        $rawString = $_POST['data'] ?? file_get_contents('php://input');
        $params = [];
        parse_str($rawString, $params);
        $isEditFlag = ($params['is_edit_mode_flag'] ?? '0') === '1';
        
        $error = $this->errorCheck(1);
        if (!empty($error)) {
            $submittedCols = $this->reconstructSubmittedCols();
            \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
                'table_name'       => $this->tableName,
                'logical_name'     => $this->logicalName,
                'table_type'       => $this->tableType,
                'description'      => $this->description,
                'imported_cols'    => $submittedCols,
                'import_error_msg' => $error . 'を入力してください。',
                'is_edit_mode'     => $isEditFlag,
                'view_info'        => ($this->tableType === 'view') ? $this->reconstructViewInfo($params) : null,
                'all_tables_map'   => $this->getTargetTablesMap() // 💡追加
            ]);
            \Develop\Utils\Screen::areaView();
            return;
        }
        
        $finalDescription = $this->description;
        if ($this->tableType === 'view') {
            $viewMeta = $this->reconstructViewInfo($params);
            $finalDescription = json_encode($viewMeta, JSON_UNESCAPED_UNICODE);
        }
        
        $tableData = [
            'project_id'    => $_SESSION[self::SESSION_PROJECT_KEY] ?? '',
            'physical_name' => $this->tableName,
            'logical_name'  => $this->logicalName,
            'table_type'    => $this->tableType,
            'description'   => $finalDescription,
            'columns'       => []
        ];
        
        foreach ($this->col_physical as $index => $physicalName) {
            if (empty(trim($physicalName))) {
                continue;
            }
            
            $tableData['columns'][] = [
                'seq_no'        => count($tableData['columns']) + 1,
                'physical_name' => trim($physicalName),
                'logical_name'  => trim($this->col_logical[$index] ?? ''),
                'data_type'     => $this->col_type[$index] ?? 'varchar',
                'data_length'   => !empty($this->col_length[$index]) ? (int)$this->col_length[$index] : null,
                'is_primary'    => (($this->col_primary[$index] ?? '0') === '1') ? 1 : 0,
                'is_nullable'   => (($this->col_null[$index] ?? '0') === '1') ? 1 : 0,
                'is_unique'     => (($this->col_unique[$index] ?? '0') === '1') ? 1 : 0,
                'default_value' => (($this->col_default[$index] ?? '') !== '') ? $this->col_default[$index] : null
            ];
        }
        
        $model = new \Develop\Models\DBDefinitionModel();
        $success = $model->registerTableAndColumns($tableData);
        
        if ($success) {
            $this->initialAction();
        } else {
            $submittedCols = $this->reconstructSubmittedCols();
            \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
                'table_name'       => $this->tableName,
                'logical_name'     => $this->logicalName,
                'table_type'       => $this->tableType,
                'description'      => $this->description,
                'imported_cols'    => $submittedCols,
                'import_error_msg' => 'データベース登録中にエラーが発生しました。',
                'is_edit_mode'     => $isEditFlag,
                'view_info'        => ($this->tableType === 'view') ? $this->reconstructViewInfo($params) : null,
                'all_tables_map'   => $this->getTargetTablesMap() // 💡追加
            ]);
            \Develop\Utils\Screen::areaView();
        }
    }
    
    public function cancelAction() {
        $this->initialAction();
    }
    
    public function importTableAction() {
        $this->getAllParameters();
        $error = $this->errorCheck(2);
        if (!empty($error)) {
            $submittedCols = $this->reconstructSubmittedCols();
            \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
                'table_name'       => $this->tableName,
                'logical_name'     => $this->logicalName,
                'table_type'       => $this->tableType,
                'description'      => $this->description,
                'imported_cols'    => $submittedCols,
                'import_error_msg' => $error . 'を入力してください。',
                'view_info'        => null,
                'all_tables_map'   => $this->getTargetTablesMap() // 💡追加
            ]);
            \Develop\Utils\Screen::areaView();
            return;
        }
        
        $model = new \Develop\Models\DBDefinitionModel();
        $columns = $model->getTableStructure($this->tableName);
        
        foreach ($columns as $key => $col) {
            $columns[$key]['primary'] = ($col['primary'] === 'checked') ? 'checked="checked"' : '';
            $columns[$key]['null']    = ($col['null'] === 'checked')    ? 'checked="checked"' : '';
            $columns[$key]['unique']  = ($col['unique'] === 'checked')  ? 'checked="checked"' : '';
        }
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'import_error_msg' => '',
            'table_name'   => $this->tableName,
            'logical_name' => $this->logicalName,
            'table_type'   => $this->tableType,
            'description'  => $this->description,
            'imported_cols'=> $columns,
            'view_info'    => null,
            'all_tables_map'=> $this->getTargetTablesMap() // 💡追加
        ]);
        \Develop\Utils\Screen::areaView();
    }
    
    public function modifyRowsAction() {
        $this->getAllParameters();
        
        $rawString = $_POST['data'] ?? file_get_contents('php://input');
        $params = [];
        parse_str($rawString, $params);
        
        $actionType  = $params['row_action_type'] ?? '';
        $changeCount = isset($params['row_action_count']) ? (int)$params['row_action_count'] : 0;
        $isEditFlag  = ($params['is_edit_mode_flag'] ?? '0') === '1';
        
        $submittedCols = $this->reconstructSubmittedCols();
        
        if ($changeCount > 0) {
            if ($actionType === 'add') {
                for ($i = 0; $i < $changeCount; $i++) {
                    $submittedCols[] = [
                        'physical' => '', 'logical' => '', 'type' => 'varchar', 'length' => '',
                        'primary' => '', 'null' => '', 'unique' => '', 'default' => ''
                    ];
                }
            } elseif ($actionType === 'delete') {
                for ($i = 0; $i < $changeCount; $i++) {
                    if (count($submittedCols) <= 2) break;
                    array_pop($submittedCols);
                }
            }
        }
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'table_name'       => $this->tableName,
            'logical_name'     => $this->logicalName,
            'table_type'       => $this->tableType,
            'description'      => $this->description,
            'imported_cols'    => $submittedCols,
            'import_error_msg' => '',
            'is_edit_mode'     => $isEditFlag,
            'view_info'        => ($this->tableType === 'view') ? $this->reconstructViewInfo($params) : null,
            'all_tables_map'   => $this->getTargetTablesMap() // 💡追加
        ]);
        \Develop\Utils\Screen::areaView();
    }
    
    public function getAllParameters() {
        $rawString = $_POST['data'] ?? file_get_contents('php://input');
        $params = [];
        parse_str($rawString, $params);
        
        $this->tableName   = $params['table_name'] ?? '';
        $this->logicalName = $params['logical_name'] ?? '';
        $this->tableType   = $params['table_type'] ?? 'table';
        $this->description = $params['description'] ?? '';
        
        $this->col_physical = $params['col_physical'] ?? [];
        $this->col_logical  = $params['col_logical'] ?? [];
        $this->col_type     = $params['col_type'] ?? [];
        $this->col_length   = $params['col_length'] ?? [];
        $this->col_primary  = $params['col_primary'] ?? [];
        $this->col_null     = $params['col_null'] ?? [];
        $this->col_unique   = $params['col_unique'] ?? [];
        $this->col_default  = $params['col_default'] ?? [];
    }
    
    public function errorCheck(int $errorNo) {
        if ($errorNo === 1 || $errorNo === 2) {
            if (empty($this->tableName)) return "テーブル名称";
            if ($errorNo === 1) {
                if (empty($this->logicalName)) return "日本語名称";
            }
        }
        return '';
    }
    
    private function reconstructSubmittedCols(): array {
        $reconstructed = [];
        $rowCount = count($this->col_physical);
        $maxIndex = max(2, $rowCount);
        
        for ($i = 0; $i < $maxIndex; $i++) {
            $reconstructed[] = [
                'physical' => $this->col_physical[$i] ?? '',
                'logical'  => $this->col_logical[$i] ?? '',
                'type'     => $this->col_type[$i] ?? 'varchar',
                'length'   => $this->col_length[$i] ?? '',
                'primary'  => (($this->col_primary[$i] ?? '0') == '1') ? 'checked="checked"' : '',
                'null'     => (($this->col_null[$i] ?? '0') == '1') ? 'checked="checked"' : '',
                'unique'   => (($this->col_unique[$i] ?? '0') == '1') ? 'checked="checked"' : '',
                'default'  => $this->col_default[$i] ?? '',
            ];
        }
        return $reconstructed;
    }
    
    private function getTargetTablesMap(): array {
        $project_id = $_SESSION[self::SESSION_PROJECT_KEY] ?? '';
        if (empty($project_id)) return [];

        $model = new \Develop\Models\DBDefinitionModel();
        $tableList = $model->getTableList($project_id);
        $map = [];

        foreach ($tableList as $table) {
            $pName = $table['physical_name'];
            $cols = [];
            
            // 1. 登録済みのカラム情報をモデルから取得
            $savedCols = $model->getSavedColumns($table['table_id']);
            foreach ($savedCols as $c) {
                if (!empty($c['physical'])) {
                    $cols[] = [
                        'physical' => $c['physical'],
                        'logical'  => $c['logical'] ?? '',
                        'type'     => $c['type'] ?? 'varchar' // 💡 既存の型をセット
                    ];
                }
            }
            
            // 2. 登録データがまだ無ければ実際の物理スキーマから取得
            if (empty($cols)) {
                $structures = $model->getTableStructure($pName);
                foreach ($structures as $s) {
                    if (!empty($s['physical'])) {
                        $cols[] = [
                            'physical' => $s['physical'],
                            'logical'  => $s['logical'] ?? '',
                            'type'     => $s['type'] ?? 'varchar' // 💡 既存の型をセット
                        ];
                    }
                }
            }
            
            $map[$pName] = $cols;
        }

        return $map;
    }
    
    private function reconstructViewInfo(array $params): array {
        return [
            'is_view_meta' => true,
            'join_type'    => $params['join_type'] ?? 'INNER JOIN',
            'view_table_a' => $params['view_table_a'] ?? '',
            'view_table_b' => $params['view_table_b'] ?? '',
            'keys_a'       => $params['view_keys_a_meta'] ?? '',
            'keys_b'       => $params['view_keys_b_meta'] ?? '',
            'select_cols'  => $params['view_select_cols_meta'] ?? ''
        ];
    }
}

