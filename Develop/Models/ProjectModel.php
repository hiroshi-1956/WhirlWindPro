<?php

namespace Develop\Models;

class ProjectModel extends \Develop\Utils\BaseModel {

    public function getProjectList() {
        $this->logger->debug("ProjectModel::getProjectList() start...");
        
        $projectList = [];
        try {
            $sql = "SELECT project_id, project_name FROM db_projects ORDER BY sort_order DESC";
            $stmt = $this->db->query($sql);
            $projectList = $stmt->fetchAll();
         
            $projectMap = [];
            if (is_array($projectList)) {
                foreach ($projectList as $project) {
                    $projectMap[$project['project_id']] = $project['project_name'];
                }
            }
            \Develop\Utils\Session::set('project_map', $projectMap);
            
            //$this->logger->info("☆☆☆ projectList : " . print_r($projectList, true));
            //$this->logger->info("☆☆☆ projectMap : " . print_r($projectMap, true));
            
        } catch (\Exception $e) {
            $this->logger->error("ProjectModel：：getProjectList() Error: " . $e->getMessage());
        }
            
        $this->logger->debug("ProjectModel::getProjectList() finish.");
        return $projectList;
    }   

    public function registProject($project_id, $project_name, $description) {
        $this->logger->debug("ProjectModel::registProject() start...");
        
        try {
            $sql = "SELECT MAX(sort_order) AS max_sort FROM projects";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $newval = $result['max_sort'] + 10;
            
            // 画像のカラム名と一致させる：project_id, project_name, description, sort_order
            $sql = "INSERT INTO projects (
                                            project_id,
                                            project_name,
                                            description,
                                            sort_order
                                        ) VALUES (?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $project_id,
                $project_name,
                $description,
                $newval
            ]);
            
            $this->logger->debug("ProjectModel::registProject() DB登録成功");
            
        } catch (\Exception $e) {
            $this->logger->error("ProjectModel::registProject() Error: " . $e->getMessage());
            throw $e; // Controller側にエラーを伝える
        }
    }
}