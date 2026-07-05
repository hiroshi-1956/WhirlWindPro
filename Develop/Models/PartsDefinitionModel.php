<?php
namespace Develop\Models;

class PartsDefinitionModel extends \Develop\Utils\BaseModel {
    
    
    protected $db;
    
    /**
     * すべての画面パーツリストを取得する
     */
    public function getAllPartsList($project_id) {
        $this->logger->debug("PartsDefinitionModel::getAllPartsList() start... Project ID: {$project_id}");
        
        try {
            $sql = "SELECT
                        parts_id,
                        parts_name,
                        parts_type,
                        parts_description,
                        table_name,
                        display_label,
                        column_filter,
                        preview_title,
                        input_rows,
                        checked_columns_json,
                        input_style,
                        contents
                    FROM
                        m_screenparts
                    WHERE
                        project_id = :project_id
                    ORDER BY
                        parts_id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':project_id', $project_id, \PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            $this->logger->error("❌ PartsDefinitionModel::getAllPartsList() でエラー: " . $e->getMessage());
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
            
            // 💡 1. 入力値が「数値」か「物理名（文字列）」かを判別し、すべて内部で「数値の table_id」に一本化する
            if (is_numeric($table_name_or_id)) {
                $tableId = (int)$table_name_or_id;
            } else {
                // 💡 文字列（例: developers）が渡された場合、m_tables から table_id を逆引きする
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
            
            // 💡 2. table_idが特定できなかった場合は処理を終了
            if (empty($tableId)) {
                $this->logger->error("❌ テーブルIDの特定に失敗したため、カラム取得をスキップします。Input: {$table_name_or_id}");
                return [];
            }
            
            // 💡 3. 確実かつ安全に存在する table_id を使って m_columns から取得する
            $sql = "SELECT
                        physical_name,
                        logical_name AS logical_name
                    FROM m_columns
                    WHERE table_id = :table_id
                    ORDER BY column_id ASC"; // 元のソート順（column_id ASC）を完全に維持
            
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
     * 画面パーツ情報を保存（新規挿入 / 更新）する
     */
    public function saveParts($project_id, $parts_id, $data) {
        $this->logger->debug("PartsDefinitionModel::saveParts() start... Project ID: {$project_id}, Parts ID: {$parts_id}");
        
        if (empty($project_id)) {
            $this->logger->error("❌ project_idが空のため保存処理を中断しました。");
            return false;
        }
        
        try {
            $checkedColumnsJson = '';
            if (!empty($data['selected_columns'])) {
                if (is_array($data['selected_columns'])) {
                    $checkedColumnsJson = implode(',', $data['selected_columns']);
                } else {
                    $checkedColumnsJson = (string)$data['selected_columns'];
                }
            }
            $checkedColumnsJson = trim($checkedColumnsJson, " \t\n\r\0\x0B,");
            
            $this->logger->debug("PartsDefinitionModel::saveParts() 保存するchecked_columns_jsonの値: [{$checkedColumnsJson}]");
            
            $pType = $data['parts_type'] ?? '';
            $inputRows = null;
            // 💡 条件に 'Text Display' を追加
            if (($pType === 'Multi Record Input' || $pType === 'Multi Record Confirm' || $pType === 'Text Input' || $pType === 'Text Confirm' || $pType === 'Text Display') && !empty($data['input_rows'])) {
                $inputRows = (int)$data['input_rows'];
            }
            
            $inputStyle = $data['input_style'] ?? null;
            $contents   = $data['contents'] ?? null;
            $this->logger->debug("PartsDefinitionModel::saveParts() contents : {$contents}");
            
            if (empty($parts_id) || $parts_id === '0') {
                $sql = "INSERT INTO m_screenparts (
                            project_id,
                            parts_name,
                            parts_description,
                            parts_type,
                            table_name,
                            display_label,
                            preview_title,
                            column_filter,
                            input_rows,
                            checked_columns_json,
                            input_style,
                            contents
                        ) VALUES (
                            :project_id,
                            :parts_name,
                            :parts_description,
                            :parts_type,
                            :table_name,
                            :display_label,
                            :preview_title,
                            :column_filter,
                            :input_rows,
                            :checked_columns_json,
                            :input_style,
                            :contents
                        )";
                $this->logger->debug("PartsDefinitionModel::saveParts() {$sql}");
                $this->logger->debug("PartsDefinitionModel::saveParts() INSERTを実行します。");
                $stmt = $this->db->prepare($sql);
                
                $stmt->bindValue(':project_id', $project_id, \PDO::PARAM_STR);
                $stmt->bindValue(':parts_name', $data['parts_name'], \PDO::PARAM_STR);
                $stmt->bindValue(':parts_description', $data['parts_description'], \PDO::PARAM_STR);
                $stmt->bindValue(':parts_type', $data['parts_type'], \PDO::PARAM_STR);
                $tName = (!isset($data['table_name']) || $data['table_name'] === '') ? null : $data['table_name'];
                $stmt->bindValue(':table_name', $tName, $tName === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
                $stmt->bindValue(':display_label', $data['display_label'], \PDO::PARAM_STR);
                $stmt->bindValue(':preview_title', $data['preview_title'], \PDO::PARAM_STR);
                $stmt->bindValue(':column_filter', $data['column_filter'], \PDO::PARAM_STR);
                $stmt->bindValue(':input_rows', $inputRows, $inputRows === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
                $stmt->bindValue(':checked_columns_json', $checkedColumnsJson, \PDO::PARAM_STR);
                $stmt->bindValue(':input_style', $inputStyle, $inputStyle === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
                $stmt->bindValue(':contents', $contents, $contents === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
                
                $stmt->execute();
                $parts_id = $this->db->lastInsertId();
                
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
                            checked_columns_json = :checked_columns_json,
                            input_style = :input_style,
                            contents = :contents
                        WHERE
                            project_id = :project_id AND parts_id = :parts_id";
                
                $this->logger->debug("PartsDefinitionModel::saveParts() UPDATEを実行します。Parts ID: {$parts_id}");
                $stmt = $this->db->prepare($sql);
                
                $stmt->bindValue(':parts_name', $data['parts_name'], \PDO::PARAM_STR);
                $stmt->bindValue(':parts_description', $data['parts_description'], \PDO::PARAM_STR);
                $stmt->bindValue(':parts_type', $data['parts_type'], \PDO::PARAM_STR);
                $tName = (!isset($data['table_name']) || $data['table_name'] === '') ? null : $data['table_name'];
                $stmt->bindValue(':table_name', $tName, $tName === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
                $stmt->bindValue(':display_label', $data['display_label'], \PDO::PARAM_STR);
                $stmt->bindValue(':preview_title', $data['preview_title'], \PDO::PARAM_STR);
                $stmt->bindValue(':column_filter', $data['column_filter'], \PDO::PARAM_STR);
                $stmt->bindValue(':input_rows', $inputRows, $inputRows === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
                $stmt->bindValue(':checked_columns_json', $checkedColumnsJson, \PDO::PARAM_STR);
                $stmt->bindValue(':input_style', $inputStyle, $inputStyle === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
                $stmt->bindValue(':contents', $contents, $contents === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
                
                $stmt->bindValue(':project_id', $project_id, \PDO::PARAM_STR);
                $stmt->bindValue(':parts_id', $parts_id, \PDO::PARAM_STR);
                
                $stmt->execute();
            }
            
            return $parts_id;
        } catch (\Exception $e) {
            $this->logger->error("❌ PartsDefinitionModel::saveParts() でエラーが発生しました: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * IDを指定して特定の画面パーツ情報を1件取得する
     */
    public function getPartsById($projectId, $partsId) {
        // 最初に提示いただいたオリジナルのロジックを100%完全に復元
        $allParts = $this->getAllPartsList($projectId);
        foreach ($allParts as $parts) {
            if ($parts['parts_id'] == $partsId) {
                return $parts;
            }
        }
        return null;
    }
}
