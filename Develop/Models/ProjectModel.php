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

    public function registProject($project_id, $project_name, $dsn, $username, $password, $options) {
        $this->logger->debug("ProjectModel::registProject() start...");
        
        try {
            // 💡 変更：テーブル名を画像に合わせる（例として `m_projects` と仮定。現状の getProjectList が db_projects を見ているならそちらと統一してください）
            $targetTable = "db_projects";
            
            $sql = "SELECT MAX(sort_order) AS max_sort FROM {$targetTable}";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $newval = (int)$result['max_sort'] + 10;
            
            // 💡 変更：画像のカラム定義（project_id, sort_order, project_name, dsn, username, password, options）に修正
            $sql = "INSERT INTO {$targetTable} (
                        project_id,
                        sort_order,
                        project_name,
                        dsn,
                        username,
                        password,
                        options
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            // 💡 変更：プレースホルダーにバインドする配列の順番を上記SQLと一致させる
            $stmt->execute([
                $project_id,
                $newval,
                $project_name,
                $dsn,
                $username,
                $password,
                $options
            ]);
            
            $this->createDSN($project_id, $dsn, $username, $password, $options);
            
            $this->logger->debug("ProjectModel::registProject() DB登録成功");
            
        } catch (\Exception $e) {
            $this->logger->error("ProjectModel::registProject() Error: " . $e->getMessage());
            throw $e; // Controller側にエラーを伝える
        }
    }
    
    /**
     * プロジェクト専用のDB設定ファイルをconfigディレクトリ内に自動生成する
     * @param string $project_id
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param string $options
     * @return bool
     */
    public function createDSN(string $project_id, string $dsn, string $username, string $password, string $options): bool {
        $this->logger->debug("ProjectModel::createDSN() start... ProjectID: " . $project_id);
        
        try {
            // 📁 1. 保存先パスの決定（プロジェクトのルート配下の config/ ディレクトリを指すように調整してください）
            // ※ここでは仮にこのModelファイルから見た相対パス、または共通パス定数などを想定しています
            $configDir = __DIR__ . '/../../config'; // 環境に合わせて 'config' ディレクトリへのパスを正確に記述してください
            $filePath = $configDir . '/db_' . $project_id . '.php';
            
            // ディレクトリが存在しない場合は自動作成
            if (!is_dir($configDir)) {
                mkdir($configDir, 0755, true);
            }
            
            // 📋 2. 書き込むPHPコード（配列の文字列）を生成
            // 接続オプションは画面から空文字列やプレーンなテキストで来る可能性があるため、トリム処理等を入れると安全です
            $optionsString = empty($options) ? '[]' : trim($options);
            
            // ヒアドキュメント（<<<EOD）を使って、そのまま読み込めるPHPの配列定義を作成します
            $phpCode = <<<EOD
<?php
/**
 * db_{$project_id}.php
 * このファイルは新規プロジェクト登録時に自動生成されました。
 */
return [
    'dsn'      => '{$dsn}',
    'username' => '{$username}',
    'password' => '{$password}',
    'options'  => {$optionsString}
];
EOD;
            
            // 💾 3. ファイルへの書き込みを実行
            $result = file_put_contents($filePath, $phpCode);
            
            if ($result === false) {
                $this->logger->error("ProjectModel::createDSN() ファイルの書き込みに失敗しました: " . $filePath);
                return false;
            }
            
            $this->logger->debug("ProjectModel::createDSN() ファイル生成成功: " . $filePath);
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error("ProjectModel::createDSN() で例外発生: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 指定されたプロジェクトIDの情報を1件取得する
     * @param string $project_id
     * @return array|null
     */
    public function getProjectById(string $project_id): ?array {
        $this->logger->debug("ProjectModel::getProjectById() start... ProjectID: {$project_id}");
        
        try {
            $targetTable = "db_projects";
            
            // 💡 画像のカラム構造に合わせて必要な情報をSELECT
            $sql = "SELECT
                        project_id,
                        project_name,
                        dsn,
                        username,
                        password,
                        options
                    FROM {$targetTable}
                    WHERE project_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$project_id]);
            $project = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $project ?: null;
            
        } catch (\Exception $e) {
            $this->logger->error("ProjectModel::getProjectById() Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * プロジェクト定義を更新し、付随するDSN設定ファイルを再生成する
     */
    public function updateProject($project_id, $project_name, $dsn, $username, $password, $options) {
        $this->logger->debug("ProjectModel::updateProject() start... ProjectID: {$project_id}");
        
        try {
            $targetTable = "db_projects";
            
            // 💡 1. データベース上の情報をUPDATE
            $sql = "UPDATE {$targetTable} SET
                        project_name = ?,
                        dsn = ?,
                        username = ?,
                        password = ?,
                        options = ?,
                        updated_at = NOW()
                    WHERE project_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $project_name,
                $dsn,
                $username,
                $password,
                $options,
                $project_id // WHERE句の条件
            ]);
            
            $this->logger->debug("ProjectModel::updateProject() DBレコード更新完了");
            
            // 💡 2. すでに作成してある createDSN メソッドに値を流し込み、config/db_*.php を最新状態に上書き
            $this->createDSN($project_id, $dsn, $username, $password, $options);
            
        } catch (\Exception $e) {
            $this->logger->error("ProjectModel::updateProject() Error: " . $e->getMessage());
            throw $e;
        }
    }
}