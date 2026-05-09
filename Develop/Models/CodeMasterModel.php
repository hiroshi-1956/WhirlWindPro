<?php
namespace Develop\Models;

class CodeMasterModel extends \Develop\Utils\BaseModel {
    
    /**
     * 指定したグループの有効なコードリストをソート順に取得する
     */
    public function getCodesByGroup($group) {
        $this->logger->debug("CodeMasterModel：：getCodesByGroup() start...");
        
        try {
            $sql = "SELECT code_key, code_name, description FROM code_master WHERE code_group = :group AND is_active = 1 ORDER BY sort_order ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':group', $group, \PDO::PARAM_STR);
            $stmt->execute();
            $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            $this->logger->error("CodeMasterModel：：getCodesByGroup() Error: " . $e->getMessage());
        }
        
        $this->logger->debug("CodeMasterModel：：getCodesByGroup() finish.");
        return $list;
    }
}
