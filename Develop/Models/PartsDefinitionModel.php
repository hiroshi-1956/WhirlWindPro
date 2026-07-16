<?php
namespace Develop\Models;

class PartsDefinitionModel extends \Develop\Utils\BaseModel {
    
    protected $db;
    
    /**
     * すべての画面パーツリストを取得する
     */
    public function getAllPartsList($project_id) {
        try {
            $sql = "SELECT
                    parts_id, parts_name, parts_type, parts_description, table_name,
                    display_label, column_filter, preview_title, input_rows,
                    -- ✅【MultiFree用追加】
                    line_counts, search_area, information_area,
                    checked_columns_json, input_style, contents, style_condition, style_column
                FROM m_screenparts
                WHERE project_id = :project_id
                ORDER BY parts_id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':project_id', $project_id, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            $this->logger->error("❌ getAllPartsListに失敗しました: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 画面パーツ情報を保存・更新する
     */
    public function saveParts($project_id, $parts_id, $data) {
        $pType = $data['parts_type'] ?? '';
        
        // 入力行数のキャスト判定に MultiFree を追加
        $inputRows = null;
        if (($pType === 'Multi Record Input' || $pType === 'Multi Record Confirm' || $pType === 'Text Input' || $pType === 'Text Confirm' || $pType === 'Text Display' || $pType === 'MultiFree') && !empty($data['input_rows'])) {
            $inputRows = (int)$data['input_rows'];
        }
        
        // ✅【MultiFree用】追加カラム用の値を配列から展開・制御
        $lineCounts    = ($pType === 'MultiFree') ? (int)($data['line_counts'] ?? 1) : null;
        $searchArea    = ($pType === 'MultiFree') ? ($data['search_area'] ?? '無') : null;
        $infoArea      = ($pType === 'MultiFree') ? ($data['information_area'] ?? '無') : null;
        
        // selected_columns をJSON形式に変換して保存する準備
        $selectedColumns = $data['selected_columns'] ?? [];
        $checkedColumnsJson = json_encode($selectedColumns, JSON_UNESCAPED_UNICODE);
        
        try {
            if (empty($parts_id) || $parts_id === '0') {
                $sql = "INSERT INTO m_screenparts (
                        project_id, parts_name, parts_description, parts_type, table_name,
                        display_label, preview_title, column_filter, input_rows,
                        line_counts, search_area, information_area,
                        checked_columns_json, input_style, contents, style_condition, style_column
                    ) VALUES (
                        :project_id, :parts_name, :parts_description, :parts_type, :table_name,
                        :display_label, :preview_title, :column_filter, :input_rows,
                        :line_counts, :search_area, :information_area,
                        :checked_columns_json, :input_style, :contents, :style_condition, :style_column
                    )";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':project_id', $project_id, \PDO::PARAM_STR);
            } else {
                $sql = "UPDATE m_screenparts SET
                        parts_name = :parts_name,
                        parts_description = :parts_description,
                        parts_type = :parts_type,
                        table_name = :table_name,
                        display_label = :display_label,
                        preview_title = :preview_title,
                        column_filter = :column_filter,
                        input_rows = :input_rows,
                        line_counts = :line_counts,
                        search_area = :search_area,
                        information_area = :information_area,
                        checked_columns_json = :checked_columns_json,
                        input_style = :input_style,
                        contents = :contents,
                        style_condition = :style_condition,
                        style_column = :style_column
                    WHERE project_id = :project_id AND parts_id = :parts_id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':project_id', $project_id, \PDO::PARAM_STR);
                $stmt->bindValue(':parts_id', $parts_id, \PDO::PARAM_INT);
            }
            
            // ✅ 既存カラムも含めて漏れなく全てバインドする
            $stmt->bindValue(':parts_name', $data['parts_name'] ?? '', \PDO::PARAM_STR);
            $stmt->bindValue(':parts_description', $data['parts_description'] ?? '', \PDO::PARAM_STR);
            $stmt->bindValue(':parts_type', $pType, \PDO::PARAM_STR);
            $stmt->bindValue(':table_name', $data['table_name'] ?? '', \PDO::PARAM_STR);
            $stmt->bindValue(':display_label', $data['display_label'] ?? 'physical', \PDO::PARAM_STR);
            $stmt->bindValue(':preview_title', $data['preview_title'] ?? '', \PDO::PARAM_STR);
            $stmt->bindValue(':column_filter', $data['column_filter'] ?? 'all', \PDO::PARAM_STR);
            $stmt->bindValue(':input_rows', $inputRows, $inputRows === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
            
            // ✅【MultiFree用バインド】
            $stmt->bindValue(':line_counts', $lineCounts, $lineCounts === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
            $stmt->bindValue(':search_area', $searchArea, $searchArea === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
            $stmt->bindValue(':information_area', $infoArea, $infoArea === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
            
            // ✅ その他の既存設定値バインド
            $stmt->bindValue(':checked_columns_json', $checkedColumnsJson, \PDO::PARAM_STR);
            $stmt->bindValue(':input_style', $data['input_style'] ?? '', \PDO::PARAM_STR);
            $stmt->bindValue(':contents', $data['contents'] ?? '', \PDO::PARAM_STR);
            $stmt->bindValue(':style_condition', $data['style_condition'] ?? '', \PDO::PARAM_STR);
            $stmt->bindValue(':style_column', $data['style_column'] ?? '', \PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (\Exception $e) {
            $this->logger->error("❌ savePartsに失敗しました: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * テーブル構造（一覧）を取得する
     */
    public function getTableStructure($project_id = '') {
        $this->logger->debug("PartsDefinitionModel::getTableStructure() start... Project ID: {$project_id}");
        
        try {
            $sql = "SELECT
                        table_id,
                        physical_name,
                        logical_name
                    FROM m_tables
                    WHERE project_id = :project_id
                    ORDER BY physical_name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':project_id', $project_id, \PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            $this->logger->error("❌ m_tables取得に失敗しました: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * テーブル名またはIDからカラム一覧を取得する
     */
    public function getColumnsByTableId($table_name_or_id) {
        $this->logger->debug("PartsDefinitionModel::getColumnsByTableId() start... Input: {$table_name_or_id}");
        
        if (empty($table_name_or_id)) return [];
        
        try {
            $tableId = null;
            
            if (is_numeric($table_name_or_id)) {
                $tableId = (int)$table_name_or_id;
            } else {
                $sqlTable = "SELECT table_id FROM m_tables WHERE physical_name = :p_name LIMIT 1";
                $stmtTable = $this->db->prepare($sqlTable);
                $stmtTable->bindValue(':p_name', $table_name_or_id, \PDO::PARAM_STR);
                $stmtTable->execute();
                $res = $stmtTable->fetch(\PDO::FETCH_ASSOC);
                
                if (!empty($res['table_id'])) {
                    $tableId = (int)$res['table_id'];
                    $this->logger->debug("逆引き成功: 物理名 {$table_name_or_id} -> table_id : {$tableId}");
                }
            }
            
            if (empty($tableId)) {
                $this->logger->error("❌ テーブルIDの特定に失敗したため、カラム取得をスキップします。Input: {$table_name_or_id}");
                return [];
            }
            
            $sql = "SELECT
                        physical_name,
                        logical_name AS logical_name
                    FROM m_columns
                    WHERE table_id = :table_id
                    ORDER BY column_id ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':table_id', $tableId, \PDO::PARAM_INT);
            
            $stmt->execute();
            $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->logger->debug("PartsDefinitionModel::getColumnsByTableId() finish. 件数: " . count($columns));
            
            return $columns ?: [];
        } catch (\Exception $e) {
            $this->logger->error("❌ m_columnsからのカラム取得に失敗しました: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * IDを指定して特定の画面パーツ情報を1件取得する
     */
    public function getPartsById($projectId, $partsId) {
        $allParts = $this->getAllPartsList($projectId);
        foreach ($allParts as $parts) {
            if ($parts['parts_id'] == $partsId) {
                return $parts;
            }
        }
        return null;
    }
}
