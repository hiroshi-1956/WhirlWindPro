<?php
namespace Develop\Models;

class ScreenDefinitionModel extends \Develop\Utils\BaseModel {
    
    public function getAllScreenList($project_id) {
        $this->logger->debug("ScreenDefinitionModel::getAllScreenList() start... Project ID: " . $project_id);
        if (empty($project_id)) return [];
        
        try {
            $sql = "SELECT screen_id, screen_name, screen_description, rows_count, cols_count, grids_config_json
                    FROM m_screen
                    WHERE project_id = :project_id
                    ORDER BY screen_id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':project_id' => $project_id]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            $this->logger->error("ScreenDefinitionModel::getAllScreenList エラー: " . $e->getMessage());
            return [];
        }
    }
    
    public function getScreenData($project_id, $screen_id) {
        $this->logger->debug("ScreenDefinitionModel::getScreenData() start... project_id: {$project_id}, screen_id: {$screen_id}");
        if (empty($project_id) || empty($screen_id)) return [];
        
        try {
            $sql = "SELECT screen_id, screen_name, screen_description, rows_count, cols_count, grids_config_json
                    FROM m_screen
                    WHERE project_id = :project_id AND screen_id = :screen_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':project_id' => $project_id,
                ':screen_id'  => $screen_id
            ]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            $this->logger->error("ScreenDefinitionModel::getScreenData エラー: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 指定されたテーブルのカラム定義を同期的に取得する
     */
    public function getColumnsByTableName($project_id, $table_name) {
        $this->logger->debug("ScreenDefinitionModel::getColumnsByTableName() start... project_id: {$project_id}, table_name: {$table_name}");
        if (empty($project_id) || empty($table_name)) return [];
        
        try {
            $sql = "SELECT c.physical_name, c.logical_name, c.data_type, c.data_length
                    FROM m_columns c
                    JOIN m_tables t ON c.table_id = t.table_id
                    WHERE t.project_id = :project_id AND t.physical_name = :table_name
                    ORDER BY c.seq_no ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':project_id' => $project_id,
                ':table_name' => $table_name
            ]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $this->logger->debug("🔍 取得カラム数: " . count($result) . "件");
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("ScreenDefinitionModel::getColumnsByTableName エラー: " . $e->getMessage());
            return [];
        }
    }
}