<?php
namespace Develop\Models;

class PartsDefinitionModel extends \Develop\Utils\BaseModel {
    protected $db;
    
    /**
     * 💡 【修正】選択中のプロジェクトに属する画面パーツ（m_parts）の一覧を取得する
     * @param string|int $project_id プロジェクトID
     * @return array パーツ一覧配列
     */
    public function getAllPartsList($project_id) {
        $this->logger->debug("PartsDefinitionModel::getAllPartsList() start... Project ID: " . $project_id);
        
        if (empty($project_id)) return [];
        
        try {
            // 📁 WHERE project_id = :project_id を追加して他プロジェクトと分離
            $sql = "SELECT parts_id, parts_name, parts_description, rows_count, cols_count
                    FROM m_parts
                    WHERE project_id = :project_id
                    ORDER BY parts_id DESC";
            $this->logger->debug("PartsDefinitionModel::getAllPartsList() １");
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':project_id' => $project_id]);
            $this->logger->debug("PartsDefinitionModel::getAllPartsList() ２");
            
            $parts_List = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->logger->debug("PartsDefinitionModel::getAllPartsList() ３");
            
            $this->logger->debug("PartsDefinitionModel::getAllPartsList() finish. 件数: " . count($parts_List));
            return $parts_List;
        } catch (\Exception $e) {
            if (isset($this->logger) && method_exists($this->logger, 'error')) {
                $this->logger->error("PartsDefinitionModel::getAllPartsList エラー: " . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * 💡 【修正】選択中のプロジェクトに紐づく「テーブル一覧」を軽量に取得する
     * @param string|int $project_id プロジェクトID
     * @return array テーブル一覧配列
     */
    public function getTableStructure($project_id = '') {
        $this->logger->debug("PartsDefinitionModel::getTableStructure() start... Project ID: {$project_id}");
        try {
            // 追加された project_id カラムで絞り込むSQLに修正
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
     * 2. 指定されたテーブルID（table_id）に紐づくカラムのみをピンポイント取得（不整合修正版）
     */
    public function getColumnsByTable($tableId) {
        $this->logger->debug("PartsDefinitionModel::getColumnsByTable() start for Table ID: " . $tableId);
        
        if (empty($tableId)) return [];
        
        $columns = [];
        $dbInstance = $this->db ?? $this->_db ?? null;
        $this->logger->info("PartsDefinitionModel::getColumnsByTable() １１");
        
        if ($dbInstance !== null) {
            $this->logger->info("PartsDefinitionModel::getColumnsByTable() １２");
            
            $columnsQuery = "SELECT physical_name AS col_name, logical_name FROM db_develop.m_columns WHERE table_id = ? ORDER BY column_id ASC";
            $this->logger->info("PartsDefinitionModel::getColumnsByTable() SQL: " . $columnsQuery);
            
            $this->logger->info("PartsDefinitionModel::getColumnsByTable() １３");
            
            try {
                $stmt = $dbInstance->prepare($columnsQuery);
                $this->logger->info("PartsDefinitionModel::getColumnsByTable() １４");
                if ($stmt) {
                    $stmt->execute([$tableId]);
                    $raw_columns = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    $this->logger->info("PartsDefinitionModel::getColumnsByTable() １５");
                    
                    foreach ($raw_columns as $col) {
                        $cName = !empty($col['col_name']) ? $col['col_name'] : '';
                        $lName = !empty($col['logical_name']) ? $col['logical_name'] : '';
                        
                        $this->logger->info("PartsDefinitionModel::getColumnsByTable() １６");
                        
                        if ($cName !== '') {
                            $columns[] = [
                                'physical_name' => (string)$cName,
                                'logical_name'  => (string)$lName
                            ];
                        }
                        $this->logger->info("PartsDefinitionModel::getColumnsByTable() １７");
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error("❌ m_columns取得に失敗しました: " . $e->getMessage());
            }
        }
        
        $this->logger->debug("PartsDefinitionModel::getColumnsByTable() finish. カラム数: " . count($columns));
        return $columns;
    }
    
    /**
     * 💡 【修正】画面パーツ情報をプロジェクトIDを紐づけてインサートする
     *
     * @param string|int $project_id プロジェクトID
     * @param array $mainData 親テーブル(m_parts)用の基本データ
     * @param array $tilesData メイン画面の全マス目配置配列
     * @param array $gridsConfig セッションから取得した各マスの詳細設定配列
     * @return int|bool 成功時は採番されたparts_id、失敗時はfalse
     */
    public function registerParts($project_id, $mainData, $tilesData, $gridsConfig) {
        $this->logger->debug("PartsDefinitionModel::registerParts() start... Project ID: " . $project_id);
        
        try {
            // トランザクション開始
            $this->db->beginTransaction();
            
            // 📁 インサート対象に project_id カラムを追加
            $sqlParts = "INSERT INTO m_parts (project_id, parts_name, parts_description, rows_count, cols_count)
                         VALUES (:project_id, :parts_name, :parts_description, :rows_count, :cols_count)";
            
            $stmtParts = $this->db->prepare($sqlParts);
            $stmtParts->execute([
                ':project_id'        => $project_id,
                ':parts_name'        => $mainData['parts_name'] ?? '',
                ':parts_description' => $mainData['parts_description'] ?? '',
                ':rows_count'        => intval($mainData['rows'] ?? 1),
                ':cols_count'        => intval($mainData['cols'] ?? 1)
            ]);
            
            // 最新の parts_id を取得
            $partsId = $this->db->lastInsertId();
            
            $sqlInfo = "INSERT INTO m_parts_info (
                            parts_id, row_pos, col_pos, layout_type,
                            table_id, table_name_text, preview_title, display_label, column_filter, checked_columns_json
                        ) VALUES (
                            :parts_id, :row_pos, :col_pos, :layout_type,
                            :table_id, :table_name_text, :preview_title, :display_label, :column_filter, :checked_columns_json
                        )";
            $stmtInfo = $this->db->prepare($sqlInfo);
            
            foreach ($tilesData as $tile) {
                $posLabel = $tile['pos'] ?? '';
                $layoutType = $tile['type'] ?? '';
                
                if (empty($layoutType)) continue;
                
                preg_match('/\[(\d+),(\d+)\]/', $posLabel, $matches);
                $rowPos = isset($matches[1]) ? intval($matches[1]) : 1;
                $colPos = isset($matches[2]) ? intval($matches[2]) : 1;
                
                $config = $gridsConfig[$posLabel] ?? null;
                
                $tableId       = null;
                $tableNameText = null;
                $previewTitle  = null;
                $displayLabel  = 'physical';
                $columnFilter  = 'all';
                $checkedJson   = null;
                
                if ($config) {
                    $tableId       = !empty($config['table_id']) ? intval($config['table_id']) : null;
                    $tableNameText = $config['table_name_text'] ?? null;
                    $previewTitle  = $config['preview_title'] ?? null;
                    $displayLabel  = $config['display_label'] ?? 'physical';
                    $columnFilter  = $config['column_filter'] ?? 'all';
                    $checkedJson   = isset($config['checked_map']) ? json_encode($config['checked_map'], JSON_UNESCAPED_UNICODE) : null;
                }
                
                $stmtInfo->execute([
                    ':parts_id'             => $partsId,
                    ':row_pos'              => $rowPos,
                    ':col_pos'              => $colPos,
                    ':layout_type'          => $layoutType,
                    ':table_id'             => $tableId,
                    ':table_name_text'      => $tableNameText,
                    ':preview_title'        => $previewTitle,
                    ':display_label'        => $displayLabel,
                    ':column_filter'        => $columnFilter,
                    ':checked_columns_json' => $checkedJson
                ]);
            }
            
            $this->db->commit();
            $this->logger->debug("PartsDefinitionModel::registerParts() finish. Saved ID: " . $partsId);
            return $partsId;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            if (isset($this->logger) && method_exists($this->logger, 'error')) {
                $this->logger->error("PartsDefinitionModel::registerParts 登録エラー: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * 💡 【修正】パーツIDと現在のプロジェクトIDを厳密に指定して、親テーブルの情報を安全に取得する
     * @param int $partsId パーツID
     * @param string|int $project_id プロジェクトID
     * @return array|false 成功時は配列、不一致・失敗時はfalse
     */
    public function getPartsById($partsId, $project_id) {
        $this->logger->debug("PartsDefinitionModel::getPartsById() start... Parts ID: " . $partsId . " Project ID: " . $project_id);
        
        try {
            // 📁 連番が他プロジェクトと被っても誤認を絶対に防ぐよう project_id を条件化
            $sql = "SELECT parts_id, parts_name, parts_description, rows_count, cols_count
                    FROM m_parts
                    WHERE parts_id = :parts_id
                      AND project_id = :project_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':parts_id'   => intval($partsId),
                ':project_id' => $project_id
            ]);
            
            $this->logger->debug("PartsDefinitionModel::getPartsById() finish.");
            return $stmt->fetch(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            if (isset($this->logger) && method_exists($this->logger, 'error')) {
                $this->logger->error("PartsDefinitionModel::getPartsById エラー: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * 💡 2. パーツIDを指定して、子テーブル (m_parts_info) の詳細配置情報をすべて取得する
     */
    public function getPartsInfoByPartsId($partsId) {
        $this->logger->debug("PartsDefinitionModel::getPartsInfoByPartsId() start...");
        
        try {
            $sql = "SELECT parts_info_id, parts_id, row_pos, col_pos, layout_type,
                           table_id, table_name_text, preview_title, display_label, column_filter, checked_columns_json
                    FROM m_parts_info
                    WHERE parts_id = :parts_id
                    ORDER BY row_pos ASC, col_pos ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':parts_id' => intval($partsId)]);
            
            $this->logger->debug("PartsDefinitionModel::getPartsInfoByPartsId() finish.");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            if (isset($this->logger) && method_exists($this->logger, 'error')) {
                $this->logger->error("PartsDefinitionModel::getPartsInfoByPartsId エラー: " . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * 💡 【修正】プロジェクトIDでの制限を考慮して、安全に一括修正（UPDATE）する
     *
     * @param int $partsId 修正対象のパーツID
     * @param string|int $project_id プロジェクトID
     * @param array $mainData 親テーブル(m_parts)用の基本データ
     * @param array $tilesData メイン画面の全マス目配置配列
     * @param array $gridsConfig 各マスの詳細設定配列
     * @return bool 成功時はtrue、失敗時はfalse
     */
    public function updateParts($partsId, $project_id, $mainData, $tilesData, $gridsConfig) {
        $this->logger->debug("PartsDefinitionModel::updateParts() start for ID: " . $partsId . " Project ID: " . $project_id);
        
        try {
            $this->db->beginTransaction();
            
            // 📁 他のプロジェクトのデータを誤って書き換えないよう WHERE 句を強固にガード
            $sqlParts = "UPDATE m_parts
                         SET parts_name = :parts_name,
                             parts_description = :parts_description,
                             rows_count = :rows_count,
                             cols_count = :cols_count,
                             updated_at = NOW()
                         WHERE parts_id = :parts_id
                           AND project_id = :project_id";
            
            $stmtParts = $this->db->prepare($sqlParts);
            $stmtParts->execute([
                ':parts_id'          => intval($partsId),
                ':project_id'        => $project_id,
                ':parts_name'        => $mainData['parts_name'] ?? '',
                ':parts_description' => $mainData['parts_description'] ?? '',
                ':rows_count'        => intval($mainData['rows'] ?? 1),
                ':cols_count'        => intval($mainData['cols'] ?? 1)
            ]);
            
            // 2. 子テーブル (m_parts_info) の古い詳細配置データを物理削除
            // (m_parts_info は parts_id で紐づいているため、親が上記UPDATE文のproject_idでガードされていれば安全です)
            $sqlDeleteInfo = "DELETE FROM m_parts_info WHERE parts_id = :parts_id";
            $stmtDelete = $this->db->prepare($sqlDeleteInfo);
            $stmtDelete->execute([':parts_id' => intval($partsId)]);
            
            // 3. 子テーブル (m_parts_info) へ最新の設定を再インサート
            $sqlInfo = "INSERT INTO m_parts_info (
                            parts_id, row_pos, col_pos, layout_type,
                            table_id, table_name_text, preview_title, display_label, column_filter, checked_columns_json,
                            created_at, updated_at
                        ) VALUES (
                            :parts_id, :row_pos, :col_pos, :layout_type,
                            :table_id, :table_name_text, :preview_title, :display_label, :column_filter, :checked_columns_json,
                            NOW(), NOW()
                        )";
            $stmtInfo = $this->db->prepare($sqlInfo);
            
            foreach ($tilesData as $tile) {
                $posLabel = $tile['pos'] ?? '';
                $layoutType = $tile['type'] ?? '';
                
                if (empty($layoutType)) continue;
                
                preg_match('/\[(\d+),(\d+)\]/', $posLabel, $matches);
                $rowPos = isset($matches[1]) ? intval($matches[1]) : 1;
                $colPos = isset($matches[2]) ? intval($matches[2]) : 1;
                
                $config = $gridsConfig[$posLabel] ?? null;
                
                $tableId       = null;
                $tableNameText = null;
                $previewTitle  = null;
                $displayLabel  = 'physical';
                $columnFilter  = 'all';
                $checkedJson   = null;
                
                if ($config) {
                    $tableId       = !empty($config['table_id']) ? intval($config['table_id']) : null;
                    $tableNameText = $config['table_name_text'] ?? null;
                    $previewTitle  = $config['preview_title'] ?? null;
                    $displayLabel  = $config['display_label'] ?? 'physical';
                    $columnFilter  = $config['column_filter'] ?? 'all';
                    $checkedJson   = isset($config['checked_map']) ? json_encode($config['checked_map'], JSON_UNESCAPED_UNICODE) : null;
                }
                
                $stmtInfo->execute([
                    ':parts_id'             => intval($partsId),
                    ':row_pos'              => $rowPos,
                    ':col_pos'              => $colPos,
                    ':layout_type'          => $layoutType,
                    ':table_id'             => $tableId,
                    ':table_name_text'      => $tableNameText,
                    ':preview_title'        => $previewTitle,
                    ':display_label'        => $displayLabel,
                    ':column_filter'        => $columnFilter,
                    ':checked_columns_json' => $checkedJson
                ]);
            }
            
            $this->db->commit();
            $this->logger->debug("PartsDefinitionModel::updateParts() finish. 正常に更新されました。");
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            if (isset($this->logger) && method_exists($this->logger, 'error')) {
                $this->logger->error("PartsDefinitionModel::updateParts 修正エラー: " . $e->getMessage());
            }
            return false;
        }
    }
}
