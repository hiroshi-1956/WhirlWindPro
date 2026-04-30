<?php

namespace Develop\Models;

class ProjectModel extends \Develop\Utils\BaseModel {

    public function getProjectList() {
        $this->logger->debug("ProjectModel::getProjectList() start...");
        
        $sql = "SELECT project_id, project_name FROM projects ORDER BY sort_order ASC";
        
        // 1. query($sql) で実行し、得られたステートメントに対して fetchAll() を呼ぶ
        $stmt = $this->db->query($sql);
        $projectList = $stmt->fetchAll();
        
        $this->logger->debug("ProjectModel::getProjectList() finish.");
        return $projectList;
    }   
}