<?php
namespace Develop\Models;

class PartsDefinitionModel extends \Develop\Utils\BaseModel {
    protected $db;
    
    /**
     * 1. db_develop.m_tables から本物の「テーブル一覧」だけを軽量に取得する
     */
    public function getTableStructure() {
        $this->logger->debug("PartsDefinitionModel::getTableStructure() start...");
        
        $m_tables = [];
        $dbInstance = $this->db ?? $this->_db ?? null;
        
        if ($dbInstance !== null) {
            // 本物のdb_developスキーマを指定して取得（table_id も合わせてSELECT）
            $tablesQuery = "SELECT table_id, physical_name, logical_name FROM db_develop.m_tables ORDER BY physical_name ASC";
            
            try {
                $stmt = $dbInstance->query($tablesQuery);
                if ($stmt) {
                    $m_tables = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                }
            } catch (\Exception $e) {
                $this->logger->error("❌ m_tables取得に失敗しました: " . $e->getMessage());
            }
        }
        
        $this->logger->debug(print_r($m_tables, true));
        $this->logger->debug("PartsDefinitionModel::getTableStructure() finish. 件数: " . count($m_tables));
        return $m_tables;
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
                    // 💡【修正】引数で受け取った $tableId を確実にバインドして実行します
                    $stmt->execute([$tableId]);
                    $raw_columns = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    $this->logger->info("PartsDefinitionModel::getColumnsByTable() １５");
                    
                    foreach ($raw_columns as $col) {
                        $cName = !empty($col['col_name']) ? $col['col_name'] : '';
                        $lName = !empty($col['logical_name']) ? $col['logical_name'] : '';
                        
                        $this->logger->info("PartsDefinitionModel::getColumnsByTable() １６");
                        
                        if ($cName !== '') {
                            $columns[] = [
                                'physical_name' => (string)$cName, // View側（JavaScript）が期待する物理名
                                'logical_name'  => (string)$lName  // 日本語の論理名
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
}
