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
    
    // Viewの共通パス定義
    private const VIEW_REGIST = '\Develop\Views\AreaE\DBDefinition\RegistTable.view';
    
    public function initialAction() {
        $this->logger->debug("DBDefinitionController::initialAction() start...");
        
        $model = new \Develop\Models\DBDefinitionModel();
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\DBDefinition\DBDefinitionList.view', [
            'table_list' => $model->getTableList()
        ]);
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        
        $this->logger->debug("DBDefinitionController::initialAction() end.");
    }
    
    public function newTableAction() {
        $this->logger->debug("DBDefinitionController::newTableAction() start...");
        
        // 💡【修正】初期生成を 8行 から 2行 に変更します
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
            'imported_cols'=> $initialCols
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
        
        $savedCols = $model->getSavedColumns((int)$tableInfo['table_id']);
        
        // 最小下限を「2」に変更
        if (count($savedCols) < 2) {
            while (count($savedCols) < 2) {
                $savedCols[] = [
                    'physical' => '', 'logical' => '', 'type' => 'varchar', 'length' => '',
                    'primary' => '', 'null' => '', 'unique' => '', 'default' => ''
                ];
            }
        }
        
        // 💡【修正箇所】引数の末尾に 'is_edit_mode' => true を追加
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'table_name'        => $tableInfo['physical_name'],
            'logical_name'      => $tableInfo['logical_name'],
            'table_type'        => $tableInfo['table_type'],
            'description'       => $tableInfo['description'],
            'imported_cols'     => $savedCols,
            'import_error_msg'  => '',
            'is_edit_mode'      => true // ★ここを追加！これで一覧からの遷移時に「修正モード」になります
        ]);
        
        $this->logger->debug("DBDefinitionController::editTableAction() finish.");
        \Develop\Utils\Screen::areaView();
    }
    
    public function registerAction() {
        $this->logger->debug("DBDefinitionController::registerAction() start...");
        
        $this->getAllParameters();
        
        // 💡 画面の隠しフィールドから送信されてきた「新規(0)か修正(1)か」のフラグを正確に取得
        $rawString = $_POST['data'] ?? file_get_contents('php://input');
        $params = [];
        parse_str($rawString, $params);
        $isEditFlag = ($params['is_edit_mode_flag'] ?? '0') === '1';
        
        // 入力エラーチェック
        $error = $this->errorCheck(1);
        if (!empty($error)) {
            $this->logger->debug("DBDefinitionController::registerAction() エラー検知により再描画");
            $submittedCols = $this->reconstructSubmittedCols();
            
            \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
                'table_name'       => $this->tableName,
                'logical_name'     => $this->logicalName,
                'table_type'       => $this->tableType,
                'description'      => $this->description,
                'imported_cols'    => $submittedCols,
                'import_error_msg' => $error . 'を入力してください。',
                'is_edit_mode'     => $isEditFlag
            ]);
            \Develop\Utils\Screen::areaView();
            return;
        }
        
        // DB保存用データの組み立て
        $tableData = [
            'physical_name' => $this->tableName,
            'logical_name'  => $this->logicalName,
            'table_type'    => $this->tableType,
            'description'   => $this->description,
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
            $this->logger->info("テーブル [{$this->tableName}] の登録/修正に成功しました。一覧を更新します。");
            $this->initialAction();
        } else {
            // DB書き込み失敗時
            $this->logger->error("データベースへの書き込みに失敗しました。");
            $submittedCols = $this->reconstructSubmittedCols();
            
            \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
                'table_name'       => $this->tableName,
                'logical_name'     => $this->logicalName,
                'table_type'       => $this->tableType,
                'description'      => $this->description,
                'imported_cols'    => $submittedCols,
                'import_error_msg' => 'データベース登録中にエラーが発生しました。ログを確認してください。',
                'is_edit_mode'     => $isEditFlag
            ]);
            \Develop\Utils\Screen::areaView();
        }
        
        $this->logger->debug("DBDefinitionController::registerAction() end.");
    }
    
    public function cancelAction() {
        $this->logger->debug("DBDefinitionController::cancel() start...");
        $this->initialAction();
        $this->logger->debug("DBDefinitionController::cancel() end.");
    }
    
    public function importTableAction() {
        $this->logger->debug("DBDefinitionController::importTableAction() start...");
        
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
                'import_error_msg' => $error . 'を入力してください。'
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
            'imported_cols'=> $columns
        ]);
        
        $this->logger->debug("DBDefinitionController::importTableAction() finish.");
        \Develop\Utils\Screen::areaView();
    }
    
    public function modifyRowsAction() {
        $this->logger->debug("DBDefinitionController::modifyRowsAction() start...");
        
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
                    if (count($submittedCols) <= 2) {
                        break;
                    }
                    array_pop($submittedCols);
                }
            }
            
            if (count($submittedCols) < 2) {
                while (count($submittedCols) < 2) {
                    $submittedCols[] = [
                        'physical' => '', 'logical' => '', 'type' => 'varchar', 'length' => '',
                        'primary' => '', 'null' => '', 'unique' => '', 'default' => ''
                    ];
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
            'is_edit_mode'     => $isEditFlag
        ]);
        \Develop\Utils\Screen::areaView();
        
        $this->logger->debug("DBDefinitionController::modifyRowsAction() finish.");
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
        
        // 💡【修正】固定値「8」を完全に撤廃し、最小下限「2」へと変更します
        $maxIndex = max(2, $rowCount);
        
        for ($i = 0; $i < $maxIndex; $i++) {
            $isPrimary = (($this->col_primary[$i] ?? '0') == '1') ? 'checked="checked"' : '';
            $isNull    = (($this->col_null[$i] ?? '0') == '1') ? 'checked="checked"' : '';
            $isUnique  = (($this->col_unique[$i] ?? '0') == '1') ? 'checked="checked"' : '';
            
            $reconstructed[] = [
                'physical' => $this->col_physical[$i] ?? '',
                'logical'  => $this->col_logical[$i] ?? '',
                'type'     => $this->col_type[$i] ?? 'varchar',
                'length'   => $this->col_length[$i] ?? '',
                'primary'  => $isPrimary,
                'null'     => $isNull,
                'unique'   => $isUnique,
                'default'  => $this->col_default[$i] ?? '',
            ];
        }
        return $reconstructed;
    }
}
