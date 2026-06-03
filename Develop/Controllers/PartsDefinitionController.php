<?php

namespace Develop\Controllers;

class PartsDefinitionController extends \Develop\Utils\BaseController {
    
    // Viewの共通パス定義
    private const VIEW_REGIST = '\Develop\Views\AreaE\PartsDefinition\NewParts.view';
    
    public function initialAction() {
        $this->logger->debug("PartsDefinitionController::initialAction() start...");
        
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\PartsDefinition\PartsDefinitionList.view', []);
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        
        $this->logger->debug("PartsDefinitionController::initialAction() end.");
    }
    
    public function newPartsAction() {
        $this->logger->debug("PartsDefinitionController::newPartsAction() start...");
        
        // 初期状態は 1 × 1 の枠を表示させるための初期値をセットして呼び出す
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'parts_name'        => '',
            'parts_description' => '',
            'rows'              => 1,
            'cols'              => 1,
            'tilesData'         => json_encode([])
        ]);
        
        $this->logger->debug("PartsDefinitionController::newPartsAction() end.");
    }
    
    public function sizeChangeAction() {
        $this->logger->debug("PartsDefinitionController::sizeChangeAction() start...");
        
        $rawString = $_POST['data'] ?? file_get_contents('php://input');
        $params = [];
        if (!empty($rawString)) {
            parse_str($rawString, $params);
        }
        
        $partsName        = $params['parts_name'] ?? '';
        $partsDescription = $params['parts_description'] ?? '';
        $rows             = isset($params['rows']) ? (int)$params['rows'] : 1;
        $cols             = isset($params['cols']) ? (int)$params['cols'] : 1;
        $tilesData        = $params['tilesData'] ?? '[]';
        
        $this->logger->debug("サイズ変更要求を受信: 縦 {$rows} × 横 {$cols}");
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'parts_name'        => $partsName,
            'parts_description' => $partsDescription,
            'rows'              => $rows,
            'cols'              => $cols,
            'tilesData'         => $tilesData
        ]);
        
        $this->logger->debug("PartsDefinitionController::sizeChangeAction() end.");
        \Develop\Utils\Screen::areaView();
    }
    
    public function PartsDefinitionList() {
        $this->logger->debug("PartsDefinitionController::PartsDefinitionList() start...");
        $this->logger->debug("PartsDefinitionController::PartsDefinitionList() end.");
    }
    
    public function image() {
        $this->logger->debug("PartsDefinitionController::image() start...");
        $this->logger->debug("PartsDefinitionController::image() end.");
    }
    
    public function screenBiggerAction() {
        $this->logger->debug("PartsDefinitionController::screenBiggerAction() start...");
        
        // パラメータを安全に集約
        $requestParams = array_merge($_GET, $_POST);
        if (isset($this->request) && method_exists($this->request, 'getParams')) {
            $requestParams = $this->request->getParams();
        }
        $this->logger->debug("PartsDefinitionController::screenBiggerAction() １");
        
        $row_pos           = trim((string)($requestParams['row_pos'] ?? '1'));
        $col_pos           = trim((string)($requestParams['col_pos'] ?? '1'));
        $parts_name        = trim((string)($requestParams['parts_name'] ?? ''));
        $parts_description = trim((string)($requestParams['parts_description'] ?? ''));
        
        $selected_type     = isset($requestParams['selected_type']) ? trim((string)$requestParams['selected_type']) : '';
        
        // 選択された種別（selected_type）によって起動するViewを動的に出し分けます
        if ($selected_type === 'Single Record 入力') {
            
            $partsModel = new \Develop\Models\PartsDefinitionModel();
            $m_tables   = $partsModel->getTableStructure();
            
            $m_tables_json  = json_encode($m_tables ?? [], JSON_UNESCAPED_UNICODE);
            $m_columns_json = json_encode([], JSON_UNESCAPED_UNICODE); // カラム情報は初期状態では空にする
            
            $targetView = '\Develop\Views\AreaE\PartsDefinition\P_SingleRecordInput.view';
            
            \Develop\Utils\Screen::updateAreaE($targetView, [
                'row_pos'           => $row_pos,
                'col_pos'           => $col_pos,
                'selected_type'     => $selected_type,
                'parts_name'        => $parts_name,
                'parts_description' => $parts_description,
                'm_tables_json'     => $m_tables_json,   // 正しい実テーブルデータ
                'm_columns_json'    => $m_columns_json
            ]);
        } else if ($selected_type === 'Multi Record 入力') {
            $this->logger->debug("PartsDefinitionController::screenBiggerAction() Multi Record 入力");
            
            $partsModel = new \Develop\Models\PartsDefinitionModel();
            $m_tables   = $partsModel->getTableStructure();
            
            $m_tables_json  = json_encode($m_tables ?? [], JSON_UNESCAPED_UNICODE);
            $m_columns_json = json_encode([], JSON_UNESCAPED_UNICODE); // カラム情報は初期状態では空にする
            
            $targetView = '\Develop\Views\AreaE\PartsDefinition\P_SingleRecordInput.view';
            
            \Develop\Utils\Screen::updateAreaE($targetView, [
                'row_pos'           => $row_pos,
                'col_pos'           => $col_pos,
                'selected_type'     => $selected_type,
                'parts_name'        => $parts_name,
                'parts_description' => $parts_description,
                'm_tables_json'     => $m_tables_json,   // 正しい実テーブルデータ
                'm_columns_json'    => $m_columns_json
            ]);
        }
        
        $this->logger->debug("PartsDefinitionController::screenBiggerAction() ６");
        
        \Develop\Utils\Screen::areaView();
        
        $this->logger->debug("PartsDefinitionController::screenBiggerAction() end.");
    }
    
    public function getColumnsAction() {
        $this->logger->debug("PartsDefinitionController::getColumnsAction() start...");
        
        // パラメータを安全に集約
        $requestParams = array_merge($_GET, $_POST);
        if (isset($this->request) && method_exists($this->request, 'getParams')) {
            $requestParams = $this->request->getParams();
        }
        
        // 画面から送られてきた table_id を取得
        $tableId           = trim((string)($requestParams['table_id'] ?? ''));
        $row_pos           = trim((string)($requestParams['row_pos'] ?? '1'));
        $col_pos           = trim((string)($requestParams['col_pos'] ?? '1'));
        $parts_name        = trim((string)($requestParams['parts_name'] ?? ''));
        $parts_description = trim((string)($requestParams['parts_description'] ?? ''));
        $selected_type     = trim((string)($requestParams['selected_type'] ?? ''));
        
        $this->logger->debug("選択されたテーブルID: [{$tableId}] に対するカラム取得要求");
        
        // Modelを呼び出して対象テーブルのカラム一覧を取得
        $partsModel  = new \Develop\Models\PartsDefinitionModel();
        $currentColumns = $partsModel->getColumnsByTable($tableId);
        
        // JSON形式に変換
        $m_tables = $partsModel->getTableStructure(); // テーブル一覧も維持するために再取得
        $m_tables_json  = json_encode($m_tables ?? [], JSON_UNESCAPED_UNICODE);
        $m_columns_json = json_encode($currentColumns ?? [], JSON_UNESCAPED_UNICODE);
        
        // 適切なViewを判定して再レンダリング（最新のカラムデータを乗せる）
        if ($selected_type === 'Multi Record 入力') {
            $targetView = '\Develop\Views\AreaE\PartsDefinition\P_MultiRecordsInput.view';
        } else {
            $targetView = '\Develop\Views\AreaE\PartsDefinition\P_SingleRecordInput.view';
        }
        
        \Develop\Utils\Screen::updateAreaE($targetView, [
            'row_pos'           => $row_pos,
            'col_pos'           => $col_pos,
            'selected_type'     => $selected_type,
            'parts_name'        => $parts_name,
            'parts_description' => $parts_description,
            'm_tables_json'     => $m_tables_json,
            'm_columns_json'    => $m_columns_json // 💡 ここに対象テーブルの本物のカラムが注入されます
        ]);
        
        \Develop\Utils\Screen::areaView();
        $this->logger->debug("PartsDefinitionController::getColumnsAction() end.");
    }
    
    public function save() {
        $this->logger->debug("PartsDefinitionController::save() start...");
        
        $rawString = $_POST['data'] ?? file_get_contents('php://input');
        $params = [];
        if (!empty($rawString)) {
            parse_str($rawString, $params);
        }
        
        $row_pos   = $params['row_pos'] ?? '';
        $col_pos   = $params['col_pos'] ?? '';
        $parts_name = $params['parts_name'] ?? '';
        
        $this->logger->debug("PartsDefinitionController::save() end.");
    }
    
    public function cancelAction() {
        $this->logger->debug("PartsDefinitionController::cancelAction() start...");
        
        \Develop\Utils\Screen::updateAreaE(self::VIEW_REGIST, [
            'parts_name'        => '',
            'parts_description' => '',
            'rows'              => 1,
            'cols'              => 1,
            'tilesData'         => json_encode([])
        ]);
        
        \Develop\Utils\Screen::areaView();
        
        $this->logger->debug("PartsDefinitionController::cancelAction() end.");
    }
}
