<?php

namespace Develop\Controllers;

class ScreenDefinitionController extends \Develop\Utils\BaseController {
    
    // 💡 画面をRegistParts一本に完全統合（VIEW_UPDATEは廃止）
    private const VIEW_REGIST = '\Develop\Views\AreaE\PartsDefinition\RegistParts.view';
    
    // 💡 セッションに保存されているプロジェクトIDのキー名（環境に合わせて変更してください）
    private const SESSION_PROJECT_KEY = 'project_id';
    
    /**
     * 💡 【共通処理】現在選択されているプロジェクトIDを安全に取得する
     */
    private function getProjectId($requestParams = []) {
        return $requestParams['project_id']
        ?? $_POST['project_id']
        ?? $_GET['project_id']
        ?? $_SESSION[self::SESSION_PROJECT_KEY]
        ?? '';
    }
    
    public function initialAction() {
        $this->logger->debug("PartsDefinitionController::initialAction() start...");
        
        //$this->logger->debug("SESSION_DATA: " . print_r($_SESSION, true));
        
        $projectId = $this->getProjectId(); // 🔥 プロジェクトID取得
        $model = new \Develop\Models\ScreenDefinitionModel();
        $this->logger->debug("PartsDefinitionController::initialAction() projectId : {$projectId}");
        
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\PartsDefinition\PartsDefinitionList.view', [
            'parts_list' => $model->getAllPartsList($projectId), // 🔥 プロジェクトIDを渡す
            'project_id' => $projectId
        ]);
        
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        
        $this->logger->debug("PartsDefinitionController::initialAction() end.");
    }
    
    public function index() {
        $this->logger->debug("PartsDefinitionController::index() start...");
        
        $projectId = $this->getProjectId(); // 🔥 プロジェクトID取得
        
        // 1. モデルを呼び出して一覧データを取得
        $partsModel = new \Develop\Models\PartsDefinitionModel();
        $partsData = $partsModel->getAllPartsList($projectId); // 🔥 プロジェクトIDを渡す
        
        // 2. 画面へ渡すためのデータをセット
        $viewData = [
            'parts_list' => $partsData,
            'project_id' => $projectId
        ];
        
        // 3. 画面（PartsDefinitionList.view）を表示
        $this->render('PartsDefinitionList', $viewData);
        
        $this->logger->debug("PartsDefinitionController::index() finish.");
    }
    
    public function newPartsAction() {
        $this->logger->debug("PartsDefinitionController::newPartsAction() start...");
        
        $projectId = $this->getProjectId(); // 🔥 プロジェクトID取得
        
        // 新規登録時はセッションのバックアップをクリア
        unset($_SESSION['wwProject_main_form_backup']);
        
        // 新規登録画面（RegistParts.view）を呼び出す
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'is_edit'           => false,
            'project_id'        => $projectId, // 🔥 Viewに引き渡す
            'parts_id'          => '',
            'parts_name'        => '',
            'parts_description' => '',
            'rows_count'        => 1,
            'cols_count'        => 1,
            'grids_config_json' => json_encode([], JSON_UNESCAPED_UNICODE)
        ]);
        
        $this->logger->debug("PartsDefinitionController::newPartsAction() end.");
    }
    
    public function sizeChangeAction() {
        $this->logger->debug("PartsDefinitionController::sizeChangeAction() [セッション完全救済版] start...");
        
        $rawString = $_POST['data'] ?? file_get_contents('php://input');
        $params = [];
        if (!empty($rawString)) {
            parse_str($rawString, $params);
        }
        
        // 1. メインのセッションバックアップをロード
        $sessionData = $_SESSION['wwProject_main_form_backup'] ?? '[]';
        $formData = json_decode($sessionData, true) ?: [];
        
        // 💡 【超重要】他のセッションからもデータを救済・融合する
        $otherKeys = ['wwProject_edit_form_data', 'parts_data', 'form_data'];
        foreach ($otherKeys as $oKey) {
            if (isset($_SESSION[$oKey])) {
                $oData = json_decode($_SESSION[$oKey], true) ?: (is_array($_SESSION[$oKey]) ? $_SESSION[$oKey] : []);
                if (is_array($oData) && !empty($oData['grids_config'])) {
                    if (!isset($formData['grids_config'])) {
                        $formData['grids_config'] = [];
                    }
                    // 存在する設定をメインのセッションへマージ
                    foreach ($oData['grids_config'] as $gKey => $gVal) {
                        if (!empty($gVal)) {
                            $formData['grids_config'][$gKey] = array_merge($formData['grids_config'][$gKey] ?? [], $gVal);
                        }
                    }
                }
            }
        }
        
        // 2. 修正モード判定
        $isEdit = false;
        if (isset($params['is_edit_mode_flag']) && $params['is_edit_mode_flag'] === '1') {
            $isEdit = true;
        } elseif (!empty($formData['parts_id'])) {
            $isEdit = true;
        }
        
        // 3. 基本パラメータのサルベージ
        $projectId        = $this->getProjectId($params); // 🔥 プロジェクトID取得
        $partsId          = !empty($params['parts_id'])          ? $params['parts_id']          : ($formData['parts_id'] ?? '');
        $partsName        = !empty($params['parts_name'])        ? $params['parts_name']        : ($formData['parts_name'] ?? '');
        $partsDescription = !empty($params['parts_description']) ? $params['parts_description'] : ($formData['parts_description'] ?? '');
        $rows             = isset($params['rows']) ? (int)$params['rows'] : (isset($formData['rows']) ? (int)$formData['rows'] : 1);
        $cols             = isset($params['cols']) ? (int)$params['cols'] : (isset($formData['cols']) ? (int)$formData['cols'] : 1);
        
        $this->logger->debug("詳細画面・サイズ変更からの復帰: ID={$partsId}, ProjectID={$projectId}, Mode=" . ($isEdit ? "修正" : "新規"));
        
        $formData['project_id']        = $projectId; // 🔥 セッションにも保存
        $formData['parts_id']          = $partsId;
        $formData['parts_name']        = $partsName;
        $formData['parts_description'] = $partsDescription;
        $formData['rows']              = $rows;
        $formData['cols']              = $cols;
        
        if (!isset($formData['grids_config'])) {
            $formData['grids_config'] = [];
        }
        
        // 🔥【追加】詳細画面から届いた最新のJSONデータを最優先でマージする
        if (!empty($params['grids_config_json'])) {
            $incomingConfig = json_decode($params['grids_config_json'], true);
            if (is_array($incomingConfig)) {
                foreach ($incomingConfig as $gKey => $gVal) {
                    if (!empty($gVal)) {
                        $cleanVal = isset($gVal['grids_config']) ? $gVal['grids_config'] : $gVal;
                        $formData['grids_config'][$gKey] = $cleanVal;
                    }
                }
            }
        }
        
        // 4. もし詳細画面から直接パラメータが届いている場合も漏らさずキャッチ
        $rowPos = $params['row_pos'] ?? '';
        $colPos = $params['col_pos'] ?? '';
        if (!empty($rowPos) && !empty($colPos)) {
            $posLabel = '[' . $rowPos . ',' . $colPos . ']';
            if (!isset($formData['grids_config'][$posLabel])) {
                $formData['grids_config'][$posLabel] = [];
            }
            if (isset($params['table_id']))           $formData['grids_config'][$posLabel]['table_id'] = $params['table_id'];
            if (isset($params['table_name_text']))   $formData['grids_config'][$posLabel]['table_name_text'] = $params['table_name_text'];
            if (isset($params['preview_title']))     $formData['grids_config'][$posLabel]['preview_title'] = $params['preview_title'];
            if (isset($params['display_label']))     $formData['grids_config'][$posLabel]['display_label'] = $params['display_label'];
            if (isset($params['column_filter']))     $formData['grids_config'][$posLabel]['column_filter'] = $params['column_filter'];
            if (isset($params['checked_map'])) {
                $formData['grids_config'][$posLabel]['checked_map'] = is_array($params['checked_map']) ? $params['checked_map'] : (json_decode($params['checked_map'], true) ?: []);
            }
            if (isset($params['selected_type']))     $formData['grids_config'][$posLabel]['layout_type'] = $params['selected_type'];
        }
        
        $gridsConfig = $formData['grids_config'] ?? [];
        
        // すべての関連セッションキーへ、最新の統合データを同期する
        $_SESSION['wwProject_main_form_backup'] = json_encode($formData, JSON_UNESCAPED_UNICODE);
        
        $targetSessionKeys = ['wwProject_main_form_backup', 'wwProject_edit_form_data', 'parts_data', 'form_data'];
        foreach ($targetSessionKeys as $sKey) {
            if (isset($_SESSION[$sKey])) {
                $sData = json_decode($_SESSION[$sKey], true) ?: (is_array($_SESSION[$sKey]) ? $_SESSION[$sKey] : []);
                if (is_array($sData)) {
                    $sData['project_id']        = $projectId; // 🔥 同期
                    $sData['parts_name']        = $partsName;
                    $sData['parts_description'] = $partsDescription;
                    $sData['grids_config']      = $gridsConfig;
                    if (is_string($_SESSION[$sKey])) {
                        $_SESSION[$sKey] = json_encode($sData, JSON_UNESCAPED_UNICODE);
                    } else {
                        $_SESSION[$sKey] = $sData;
                    }
                }
            }
        }
        
        // 5. 統合ビュー（self::VIEW_REGIST）を呼び出し、確定したデータを引き渡す
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'is_edit'           => $isEdit,
            'project_id'        => $projectId, // 🔥 追加
            'parts_id'          => $partsId,
            'parts_name'        => $partsName,
            'parts_description' => $partsDescription,
            'rows_count'        => $rows,
            'cols_count'        => $cols,
            'grids_config_json' => json_encode($gridsConfig, JSON_UNESCAPED_UNICODE)
        ]);
        
        $this->logger->debug("PartsDefinitionController::sizeChangeAction() end.");
        \Develop\Utils\Screen::areaView();
    }
    
    public function PartsDefinitionList() { }
    public function image() { }
    
    public function screenBiggerAction() {
        $this->logger->debug("PartsDefinitionController::screenBiggerAction() start...");
        
        $requestParams = array_merge($_GET, $_POST);
        if (isset($this->request) && method_exists($this->request, 'getParams')) {
            $requestParams = $this->request->getParams();
        }
        
        $row_pos           = trim((string)($requestParams['row_pos'] ?? '1'));
        $col_pos           = trim((string)($requestParams['col_pos'] ?? '1'));
        $selected_type     = isset($requestParams['selected_type']) ? trim((string)$requestParams['selected_type']) : '';
        $tilesData         = json_decode($requestParams['tilesData'] ?? '[]', true) ?: [];
        
        // 先にセッションの既存バックアップをロード
        $sessionData = $_SESSION['wwProject_main_form_backup'] ?? '[]';
        $formData = json_decode($sessionData, true) ?: [];
        
        // 【超重要】リクエストにIDや名前がなくても、セッションに既存値があれば絶対にそれを死守する
        // 💡 修正ポイント①：安全に project_id を取得
        $projectId         = $this->getProjectId($requestParams);
        $parts_id          = !empty($requestParams['parts_id'])          ? trim((string)$requestParams['parts_id'])          : ($formData['parts_id'] ?? '');
        $parts_name        = !empty($requestParams['parts_name'])        ? trim((string)$requestParams['parts_name'])        : ($formData['parts_name'] ?? '');
        $parts_description = !empty($requestParams['parts_description']) ? trim((string)$requestParams['parts_description']) : ($formData['parts_description'] ?? '');
        $rows              = isset($requestParams['rows']) ? (int)$requestParams['rows'] : ($formData['rows'] ?? 1);
        $cols              = isset($requestParams['cols']) ? (int)$requestParams['cols'] : ($formData['cols'] ?? 1);
        
        $formData['project_id']        = $projectId; // 💡 修正ポイント②：セッションデータにも project_id を保存
        $formData['parts_id']          = $parts_id;
        $formData['parts_name']        = $parts_name;
        $formData['parts_description'] = $parts_description;
        $formData['rows']              = $rows;
        $formData['cols']              = $cols;
        
        if (!isset($formData['grids_config'])) {
            $formData['grids_config'] = [];
        }
        
        // ========================================================
        // 💡 修正ポイント③：【追加】メイン画面の入力内容（グリッドJSON）が届いている場合はここで救済
        // ========================================================
        $incomingJson = $requestParams['grids_config_json'] ?? null;
        if (!empty($incomingJson)) {
            $incomingConfig = json_decode($incomingJson, true);
            if (is_array($incomingConfig)) {
                foreach ($incomingConfig as $gKey => $gVal) {
                    if (!empty($gVal)) {
                        $cleanVal = isset($gVal['grids_config']) ? $gVal['grids_config'] : $gVal;
                        $formData['grids_config'][$gKey] = array_merge($formData['grids_config'][$gKey] ?? [], $cleanVal);
                    }
                }
            }
        }
        
        foreach ($tilesData as $tile) {
            $pos = $tile['pos'] ?? '';
            if (empty($pos)) continue;
            if (!isset($formData['grids_config'][$pos])) {
                $formData['grids_config'][$pos] = [];
            }
            $formData['grids_config'][$pos]['layout_type'] = $tile['type'] ?? '';
        }
        
        $posLabel = '[' . $row_pos . ',' . $col_pos . ']';
        if (!isset($formData['grids_config'][$posLabel])) {
            $formData['grids_config'][$posLabel] = [];
        }
        $formData['grids_config'][$posLabel]['layout_type'] = $selected_type;
        
        $_SESSION['wwProject_main_form_backup'] = json_encode($formData, JSON_UNESCAPED_UNICODE);
        
        // 💡 修正ポイント④：これから開くマスにすでに保存されていた詳細設定（テーブル等）をロード
        $targetGridConfig = $formData['grids_config'][$posLabel] ?? [];
        $savedTableId     = $targetGridConfig['table_id'] ?? '';
        
        if ($selected_type === 'Single Record 入力' || $selected_type === 'Multi Record 入力') {
            $partsModel = new \Develop\Models\PartsDefinitionModel();
            
            // 💡 修正ポイント⑤：モデルの引数に $projectId を確実に渡してテーブル一覧を取得！
            $m_tables   = $partsModel->getTableStructure($projectId);
            
            // 調査用ログ（もし空ならログに 0 件と出ます）
            $this->logger->debug("screenBiggerAction 取得テーブル件数: " . count($m_tables ?? []));
            
            $m_tables_json  = json_encode($m_tables ?? [], JSON_UNESCAPED_UNICODE);
            
            // 💡 修正ポイント⑥：すでにテーブルが選ばれていたら、そのカラム（項目一覧）もあらかじめロード
            $m_columns = [];
            if (!empty($savedTableId)) {
                $m_columns = $partsModel->getColumnsByTable($savedTableId) ?: [];
            }
            $m_columns_json = json_encode($m_columns ?? [], JSON_UNESCAPED_UNICODE);
            
            // 修正モードの判定ロジックを確実に確定させる
            $isEditMode = (!empty($parts_id) || (isset($requestParams['is_edit_mode_flag']) && $requestParams['is_edit_mode_flag'] === '1'));
            
            $targetView = ($selected_type === 'Multi Record 入力')
            ? '\Develop\Views\AreaE\PartsDefinition\P_MultiRecordsInput.view'
                : '\Develop\Views\AreaE\PartsDefinition\P_SingleRecordInput.view';
                
                $this->logger->debug("詳細画面遷移ログ: 遷移先={$targetView}, parts_id={$parts_id}, is_edit=" . ($isEditMode ? "true" : "false"));
                
                \Develop\Utils\Screen::updateAreaE($targetView, [
                    'row_pos'           => $row_pos,
                    'col_pos'           => $col_pos,
                    'selected_type'     => $selected_type,
                    'parts_name'        => $parts_name,
                    'parts_description' => $parts_description,
                    'm_tables_json'     => $m_tables_json,
                    'm_columns_json'    => $m_columns_json,
                    'is_edit'           => $isEditMode,        // 詳細画面の $is_edit に渡る
                    'parts_id'          => $parts_id,          // 詳細画面の $parts_id に渡る
                    'project_id'        => $projectId,         // 💡 プロジェクトIDをViewへ渡す
                    
                    // 💡 修正ポイント⑦：【超重要】保存済みの詳細データをView側へそのまま引き渡す
                    'table_id'          => $savedTableId,
                    'table_name_text'   => $targetGridConfig['table_name_text'] ?? '',
                    'preview_title'     => $targetGridConfig['preview_title'] ?? '',
                    'display_label'     => $targetGridConfig['display_label'] ?? '',
                    'column_filter'     => $targetGridConfig['column_filter'] ?? '',
                    'checked_map_json'  => json_encode($targetGridConfig['checked_map'] ?? [], JSON_UNESCAPED_UNICODE)
                ]);
        }
        
        \Develop\Utils\Screen::areaView();
        $this->logger->debug("PartsDefinitionController::screenBiggerAction() end.");
    }
    
    public function getColumnsAction() {
        $this->logger->debug("PartsDefinitionController::getColumnsAction() start...");
        
        $requestParams = array_merge($_GET, $_POST);
        if (isset($this->request) && method_exists($this->request, 'getParams')) {
            $requestParams = $this->request->getParams();
        }
        
        $projectId         = $this->getProjectId($requestParams); // 🔥 プロジェクトID取得
        $tableId           = trim((string)($requestParams['table_id'] ?? ''));
        $row_pos           = trim((string)($requestParams['row_pos'] ?? '1'));
        $col_pos           = trim((string)($requestParams['col_pos'] ?? '1'));
        $parts_name        = trim((string)($requestParams['parts_name'] ?? ''));
        $parts_description = trim((string)($requestParams['parts_description'] ?? ''));
        $selected_type     = trim((string)($requestParams['selected_type'] ?? ''));
        
        $partsModel  = new \Develop\Models\PartsDefinitionModel();
        $currentColumns = $partsModel->getColumnsByTable($tableId);
        
        $m_tables = $partsModel->getTableStructure($projectId); // 🔥 引数に $projectId を追加！
        $m_tables_json  = json_encode($m_tables ?? [], JSON_UNESCAPED_UNICODE);
        $m_columns_json = json_encode($currentColumns ?? [], JSON_UNESCAPED_UNICODE);
        
        $targetView = ($selected_type === 'Multi Record 入力')
        ? '\Develop\Views\AreaE\PartsDefinition\P_MultiRecordsInput.view'
            : '\Develop\Views\AreaE\PartsDefinition\P_SingleRecordInput.view';
            
            \Develop\Utils\Screen::updateAreaE($targetView, [
                'row_pos'           => $row_pos,
                'col_pos'           => $col_pos,
                'selected_type'     => $selected_type,
                'parts_name'        => $parts_name,
                'parts_description' => $parts_description,
                'm_tables_json'     => $m_tables_json,
                'm_columns_json'    => $m_columns_json,
                'project_id'        => $projectId // 🔥 追加
            ]);
            
            \Develop\Utils\Screen::areaView();
            $this->logger->debug("PartsDefinitionController::getColumnsAction() end.");
    }
    
    public function save() {
        $this->logger->debug("PartsDefinitionController::save() [修正モード統合防御版] start...");
        
        $requestParams = array_merge($_GET, $_POST);
        
        // 1. サーバー側セッションから直前のフォーム状態（バックアップ）をロード
        $sessionData = $_SESSION['wwProject_main_form_backup'] ?? '[]';
        $formData = json_decode($sessionData, true) ?: [];
        
        // 2. 修正モードの判定ロジック
        $isEditModeFlag = false;
        if (isset($requestParams['is_edit_mode_flag']) && ($requestParams['is_edit_mode_flag'] === '1' || $requestParams['is_edit_mode_flag'] === true)) {
            $isEditModeFlag = true;
        } elseif (isset($formData['parts_id']) && !empty($formData['parts_id'])) {
            $isEditModeFlag = true;
        } elseif (isset($requestParams['parts_id']) && !empty($requestParams['parts_id'])) {
            $isEditModeFlag = true;
        }
        
        $this->logger->debug("save() 判定結果: Mode=" . ($isEditModeFlag ? "【修正(Update)】" : "【新規(Regist)】"));
        
        // 3. 詳細画面側で設定された内容を該当座標の配列へ格納
        $row_pos = $requestParams['row_pos'] ?? '1';
        $col_pos = $requestParams['col_pos'] ?? '1';
        $posLabel = '[' . $row_pos . ',' . $col_pos . ']';
        
        if (!isset($formData['grids_config'][$posLabel])) {
            $formData['grids_config'][$posLabel] = [];
        }
        
        if (isset($requestParams['table_id'])) {
            $formData['grids_config'][$posLabel]['table_id'] = $requestParams['table_id'];
        }
        if (isset($requestParams['table_name_text'])) {
            $formData['grids_config'][$posLabel]['table_name_text'] = $requestParams['table_name_text'];
        }
        if (isset($requestParams['preview_title'])) {
            $formData['grids_config'][$posLabel]['preview_title'] = $requestParams['preview_title'];
        }
        if (isset($requestParams['display_label'])) {
            $formData['grids_config'][$posLabel]['display_label'] = $requestParams['display_label'];
        }
        if (isset($requestParams['column_filter'])) {
            $formData['grids_config'][$posLabel]['column_filter'] = $requestParams['column_filter'];
        }
        if (isset($requestParams['checked_map'])) {
            $formData['grids_config'][$posLabel]['checked_map'] = json_decode($requestParams['checked_map'], true) ?: [];
        }
        
        if ($isEditModeFlag && empty($formData['parts_id'])) {
            $formData['parts_id'] = $requestParams['parts_id'] ?? '';
        }
        
        // 4. Viewへ引き渡す変数のサルベージ
        $projectId        = $this->getProjectId($requestParams); // 🔥 プロジェクトID取得
        $partsId          = !empty($requestParams['parts_id'])          ? $requestParams['parts_id']          : ($formData['parts_id'] ?? '');
        $partsName        = !empty($requestParams['parts_name'])        ? $requestParams['parts_name']        : ($formData['parts_name'] ?? '');
        $partsDescription = !empty($requestParams['parts_description']) ? $requestParams['parts_description'] : ($formData['parts_description'] ?? '');
        $rows             = !empty($requestParams['rows'])              ? $requestParams['rows']              : ($formData['rows'] ?? 1);
        $cols             = !empty($requestParams['cols'])              ? $requestParams['cols']              : ($formData['cols'] ?? 1);
        
        $formData['project_id'] = $projectId; // 🔥 保持
        $_SESSION['wwProject_main_form_backup'] = json_encode($formData, JSON_UNESCAPED_UNICODE);
        
        $gridsConfig = $formData['grids_config'] ?? [];
        
        $this->logger->debug("save() 描画実行: parts_id={$partsId}, project_id={$projectId}");
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'is_edit'           => $isEditModeFlag,
            'project_id'        => $projectId, // 🔥 追加
            'parts_id'          => $partsId,
            'parts_name'        => $partsName,
            'parts_description' => $partsDescription,
            'rows_count'        => (int)$rows,
            'cols_count'        => (int)$cols,
            'grids_config_json' => json_encode($gridsConfig, JSON_UNESCAPED_UNICODE)
        ]);
        
        \Develop\Utils\Screen::areaView();
        $this->logger->debug("PartsDefinitionController::save() end.");
    }
    
    public function cancelAction() {
        $this->logger->debug("PartsDefinitionController::cancelAction() start...");
        unset($_SESSION['wwProject_main_form_backup']);
        
        $projectId = $this->getProjectId(); // 🔥 プロジェクトID取得
        $model = new \Develop\Models\PartsDefinitionModel();
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\PartsDefinition\PartsDefinitionList.view', [
            'parts_list' => $model->getAllPartsList($projectId) // 🔥 引数に $projectId を追加！
        ]);
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        
        \Develop\Utils\Screen::areaView();
        $this->logger->debug("PartsDefinitionController::cancelAction() end.");
    }
    
    public function registerAction() {
        $this->logger->debug("PartsDefinitionController::registerAction() start...");
        
        $backupSessionData = $_SESSION['wwProject_main_form_backup'] ?? [];
        if (is_string($backupSessionData)) {
            $backupSessionData = json_decode($backupSessionData, true) ?: [];
        }
        
        $hasRequest = isset($this->request) && is_object($this->request);
        
        $gridsJsonParam = ($hasRequest ? $this->request->getParam('grids_config_json') : null)
        ?? $_POST['grids_config_json']
        ?? $rawPostParams['grids_config_json']
        ?? null;
        
        $gridsConfig = [];
        if (!empty($gridsJsonParam)) {
            $gridsConfig = json_decode($gridsJsonParam, true) ?: [];
        }
        if (empty($gridsConfig)) {
            $gridsConfig = $backupSessionData['grids_config'] ?? $backupSessionData['grids'] ?? [];
        }
        
        $rows = ($hasRequest ? $this->request->getParam('rows') : null)
        ?? $_POST['rows']
        ?? $rawPostParams['rows']
        ?? $backupSessionData['rows']
        ?? $backupSessionData['rows_count']
        ?? null;
        
        $cols = ($hasRequest ? $this->request->getParam('cols') : null)
        ?? $_POST['cols']
        ?? $rawPostParams['cols']
        ?? $backupSessionData['cols']
        ?? $backupSessionData['cols_count']
        ?? null;
        
        if (empty($rows) || empty($cols)) {
            $maxRow = 1;
            $maxCol = 1;
            if (!empty($gridsConfig)) {
                foreach (array_keys($gridsConfig) as $posKey) {
                    if (preg_match('/\[(\d+),(\d+)\]/', $posKey, $matches)) {
                        $maxRow = max($maxRow, (int)$matches[1]);
                        $maxCol = max($maxCol, (int)$matches[2]);
                    }
                }
            }
            $rows = $rows ?? $maxRow;
            $cols = $cols ?? $maxCol;
        }
        
        $projectId = $this->getProjectId(); // 🔥 プロジェクトID取得
        $partsId   = ($hasRequest ? $this->request->getParam('parts_id') : null) ?? $_POST['parts_id'] ?? $rawPostParams['parts_id'] ?? null;
        $partsName = ($hasRequest ? $this->request->getParam('parts_name') : null) ?? $_POST['parts_name'] ?? $rawPostParams['parts_name'] ?? null;
        $partsDesc = ($hasRequest ? $this->request->getParam('parts_description') : null) ?? $_POST['parts_description'] ?? $rawPostParams['parts_description'] ?? null;
        $isEdit    = (($hasRequest ? $this->request->getParam('is_edit_mode_flag') : null) ?? $_POST['is_edit_mode_flag'] ?? $rawPostParams['is_edit_mode_flag'] ?? '0') === '1';
        
        $mainData = [
            'parts_name'        => $partsName,
            'parts_description' => $partsDesc,
            'rows'              => $rows,
            'cols'              => $cols
        ];
        
        $tilesData = [];
        if (!empty($gridsConfig)) {
            foreach ($gridsConfig as $posKey => $conf) {
                $targetConf = (isset($conf['grids_config']) && is_array($conf['grids_config'])) ? $conf['grids_config'] : $conf;
                $layoutType = $targetConf['layout_type'] ?? '';
                
                if (!empty($layoutType)) {
                    $tilesData[] = [
                        'pos'  => $posKey,
                        'type' => $layoutType
                    ];
                }
            }
        }
        
        $model = new \Develop\Models\PartsDefinitionModel();
        
        if ($isEdit && !empty($partsId)) {
            // 💡 修正（UPDATE）処理ルート
            $result = $model->updateParts($partsId, $projectId, $mainData, $tilesData, $gridsConfig); // 🔥 引数に $projectId を追加！
            if ($result) {
                $this->logger->info("✅ パーツ修正成功 ID: " . $partsId . " (Project: {$projectId})");
                unset($_SESSION['wwProject_main_form_backup']);
                $this->redirect("PartsDefinition/list?msg=update_success");
                return;
            } else {
                $this->logger->error("❌ パーツ修正に失敗しました。 ID: " . $partsId);
            }
        } else {
            // 💡 新規登録（INSERT）処理ルート
            $newPartsId = $model->registerParts($projectId, $mainData, $tilesData, $gridsConfig); // 🔥 引数に $projectId を追加！
            if ($newPartsId) {
                $this->logger->info("✅ パーツ新規登録成功 新ID: " . $newPartsId . " (Project: {$projectId})");
                unset($_SESSION['wwProject_main_form_backup']);
                $this->redirect("PartsDefinition/list?msg=register_success");
                return;
            } else {
                $this->logger->error("❌ パーツ新規登録に失敗しました。");
            }
        }
        
        $this->logger->debug("PartsDefinitionController::registerAction() エラーのため画面を再表示します。");
        $this->view->assign('is_edit', $isEdit ? '1' : '0');
        $this->view->assign('project_id', $projectId); // 🔥 追加
        $this->view->assign('parts_id', $partsId);
        $this->view->assign('parts_name', $partsName);
        $this->view->assign('parts_description', $partsDesc);
        $this->view->assign('rows', $rows);
        $this->view->assign('cols', $cols);
        $this->view->assign('grids_config_json', json_encode($gridsConfig, JSON_UNESCAPED_UNICODE));
        
        $this->view->render("RegistParts");
    }
    
    public function editPartsAction() {
        $this->logger->debug("PartsDefinitionController::editParts() start...");
        
        $rawString = $_POST['data'] ?? file_get_contents('php://input');
        $params = [];
        if (!empty($rawString)) {
            parse_str($rawString, $params);
        }
        $partsId = $params['partsId'] ?? null;
        $projectId = $this->getProjectId($params); // 🔥 プロジェクトID取得
        
        if (empty($partsId)) {
            $this->logger->error("PartsDefinitionController::editParts() partsId が指定されていません。");
            return;
        }
        
        $partsModel = new \Develop\Models\PartsDefinitionModel();
        $mainData   = $partsModel->getPartsById($partsId, $projectId); // 🔥 引数に $projectId を追加！
        $infoData   = $partsModel->getPartsInfoByPartsId($partsId);
        
        if (!$mainData) {
            $this->logger->error("PartsDefinitionController::editParts() パーツ情報が見つかりません。 [ID: {$partsId}, Project: {$projectId}]");
            return;
        }
        
        $gridsConfig = [];
        foreach ($infoData as $info) {
            $posLabel = '[' . $info['row_pos'] . ',' . $info['col_pos'] . ']';
            
            $checkedMap = [];
            if (!empty($info['checked_columns_json'])) {
                $checkedMap = json_decode($info['checked_columns_json'], true);
            }
            
            $gridsConfig[$posLabel] = [
                'layout_type'     => $info['layout_type'] ?? '',
                'table_id'        => $info['table_id'] ?? null,
                'table_name_text' => $info['table_name_text'] ?? null,
                'preview_title'   => $info['preview_title'] ?? null,
                'display_label'   => $info['display_label'] ?? null,
                'column_filter'   => $info['column_filter'] ?? null,
                'checked_map'     => $checkedMap
            ];
        }
        
        $formData = [
            'project_id'        => $projectId, // 🔥 保持
            'parts_id'          => $mainData['parts_id'] ?? '',
            'parts_name'        => $mainData['parts_name'] ?? '',
            'parts_description' => $mainData['parts_description'] ?? '',
            'rows'              => $mainData['rows_count'] ?? 1,
            'cols'              => $mainData['cols_count'] ?? 1,
            'grids_config'      => $gridsConfig
        ];
        $_SESSION['wwProject_main_form_backup'] = json_encode($formData, JSON_UNESCAPED_UNICODE);
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'is_edit'           => true,
            'project_id'        => $projectId, // 🔥 追加
            'parts_id'          => $mainData['parts_id'] ?? '',
            'parts_name'        => $mainData['parts_name'] ?? '',
            'parts_description' => $mainData['parts_description'] ?? '',
            'rows_count'        => $mainData['rows_count'] ?? 1,
            'cols_count'        => $mainData['cols_count'] ?? 1,
            'grids_config_json' => json_encode($gridsConfig, JSON_UNESCAPED_UNICODE)
        ]);
        
        $this->logger->debug("PartsDefinitionController::editParts() finish.");
        \Develop\Utils\Screen::areaView();
    }
}
