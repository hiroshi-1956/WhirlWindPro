<?php

namespace Develop\Models;

class DBDefinitionModel extends \Develop\Utils\BaseModel {
    
    /**
     * 登録済みのテーブル定義一覧を取得する
     * @return array
     */
    public function getTableList(string $project_id): array {
        $this->logger->debug("DBDefinitionModel::getTableList() start...");
        
        try {
            // プロジェクトを格納している開発用DBインスタンスを取得
            $db = \Framework\Core\Container::getDb_develop();
            
            // 登録されているテーブル情報を物理名の順（または作成日順）で全件取得
            $sql = "SELECT
                        project_id,
                        table_id,
                        physical_name,
                        logical_name,
                        table_type,
                        description,
                        created_at,
                        updated_at
                    FROM m_tables
                    WHERE project_id = :project_id
                    ORDER BY physical_name ASC";
            
            // 💡 修正：$db->query() から $db->prepare() に修正
            $stmt = $db->prepare($sql);
            $stmt->execute([':project_id' => $project_id]);
            $tableList = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $this->logger->debug("DBDefinitionModel::getTableList() finish. 取得件数: " . count($tableList));
            return $tableList;
            
        } catch (\Exception $e) {
            $this->logger->error("DBDefinitionModel::getTableList() で例外発生: " . $e->getMessage());
            return []; // エラー時は空配列を返す
        }
    }
    
    public function getTableStructure(string $tableName): array {
        $this->logger->debug("DBDefinitionModel::getTableStructure() start... [Table: {$tableName}]");
        
        $columns = [];
        
        try {
            $projectId = \Develop\Utils\Session::get('project_id');
            // プロジェクト共通のDB接続インスタンスを取得（例: PDOなど）
            $configProd = require "Config/db_{$projectId}.php";
            $pdoProd = new \PDO($configProd['dsn'], $configProd['username'], $configProd['password'], $configProd['options']);
            $pdoProd->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $db = $pdoProd;
            
            $this->logger->info("DBDefinitionModel::getTableStructure() 1");
            
            // 安全対策：英数字とアンダースコア以外を排除（SQLインジェクション対策）
            $safeTableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
            if (empty($safeTableName)) {
                return [];
            }
            $this->logger->info("DBDefinitionModel::getTableStructure() 2");
            
            // MySQLからカラムの詳細情報を一括取得
            $stmt = $db->query("SHOW FULL COLUMNS FROM {$safeTableName}");
            $dbColumns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->logger->info("DBDefinitionModel::getTableStructure() 3");
            
            foreach ($dbColumns as $dbCol) {
                // 型と長さを分離する (例: "varchar(100)" -> type:"varchar", length:"100")
                $rawType = $dbCol['Type'];
                $type = $rawType;
                $length = '';
                
                if (preg_match('/^([a-z]+)\((.+)\)$/', $rawType, $matches)) {
                    $type = $matches[1];
                    $length = $matches[2]?? '';
                }
                
                // ビュー側でそのまま使える綺麗な形にマッピング
                $columns[] = [
                    'physical' => $dbCol['Field'],
                    'logical'  => $dbCol['Comment'] ?? '', // COMMENT（論理名）
                    'type'     => $type,
                    'length'   => $length,
                    'primary'  => ($dbCol['Key'] === 'PRI') ? 'checked' : '',
                    'null'     => ($dbCol['Null'] === 'YES') ? 'checked' : '',
                    'unique'   => ($dbCol['Key'] === 'UNI') ? 'checked' : '',
                    'default'  => ($dbCol['Default'] === null) ? '' : $dbCol['Default'],
                ];
            }
            $this->logger->info(print_r($columns, true));
            
            // 💡 修正：カラム数が少ないテーブルを読み込んだ際、配列インデックス超過でエラーになるため、固定のログ出力をループに変更
            foreach ($columns as $index => $col) {
                $this->logger->info("DBDefinitionModel::getTableStructure() #{$col['physical']} #{$col['logical']} #{$col['type']} #{$col['length']} #{$col['null']} #{$col['unique']} #{$col['default']}");
            }
            $this->logger->info("DBDefinitionModel::getTableStructure() 4");
            
        } catch (\Exception $e) {
            $this->logger->error("DBDefinitionModel::getTableStructure() エラー: " . $e->getMessage());
            // テーブルが存在しない等の場合は空配列を返す
            return [];
        }
        
        $this->logger->debug("DBDefinitionModel::getTableStructure() finish. 件数: " . count($columns));
        return $columns;
    }
    
    /**
     * テーブル定義（親）とカラム定義（子）を一括で登録する
     * @param array $tableData
     * @return bool
     */
    public function registerTableAndColumns(array $tableData): bool {
        $this->logger->debug("DBDefinitionModel::registerTableAndColumns() start...");
        
        try {
            // プロジェクトを格納する開発用DBインスタンスを取得
            $db = \Framework\Core\Container::getDb_develop();
            $this->logger->debug("DBDefinitionModel::registerTableAndColumns() 11");
            
            // トランザクション開始
            $db->beginTransaction();
            $this->logger->debug("DBDefinitionModel::registerTableAndColumns() 12");
            
            // ---- 1. m_tables への処理 ----
            // 💡 修正：すでに同じ物理名かつ同じプロジェクトのテーブルがあるか確認し、table_id を取得しておく
            $selectIdSql = "SELECT table_id FROM m_tables WHERE physical_name = :pname AND project_id = :project_id";
            $stmt = $db->prepare($selectIdSql);
            $stmt->execute([
                ':pname' => $tableData['physical_name'],
                ':project_id' => $tableData['project_id'] ?? ''
            ]);
            $existingTable = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            $tableId = null;
            
            if ($existingTable) {
                // 既に存在する場合はその table_id を利用
                $tableId = (int)$existingTable['table_id'];
                
                // 💡 修正：更新条件（WHERE）に project_id を追加
                $updateTableSql = "UPDATE m_tables SET
                    logical_name = :logical_name,
                    table_type = :table_type,
                    description = :description,
                    updated_at = NOW()
                    WHERE table_id = :table_id AND project_id = :project_id";
                $stmt = $db->prepare($updateTableSql);
                $stmt->execute([
                    ':logical_name' => $tableData['logical_name'],
                    ':table_type'    => $tableData['table_type'],
                    ':description'   => $tableData['description'],
                    ':table_id'      => $tableId,
                    ':project_id'    => $tableData['project_id'] ?? ''
                ]);
                $this->logger->debug("DBDefinitionModel::registerTableAndColumns() 13 (Updated table_id: {$tableId})");
            } else {
                // 💡 修正：新規登録の INSERT 文に project_id カラムを追加
                $insertTableSql = "INSERT INTO m_tables (
                    project_id, physical_name, logical_name, table_type, description, created_at, updated_at
                ) VALUES (
                    :project_id, :physical_name, :logical_name, :table_type, :description, NOW(), NOW()
                )";
                $stmt = $db->prepare($insertTableSql);
                $stmt->execute([
                    ':project_id'    => $tableData['project_id'] ?? '',
                    ':physical_name' => $tableData['physical_name'],
                    ':logical_name'  => $tableData['logical_name'],
                    ':table_type'    => $tableData['table_type'],
                    ':description'   => $tableData['description']
                ]);
                
                // 💡 修正：新しく採番された table_id（文字列/数値）を取得（intキャストを外す）
                $tableId = $db->lastInsertId();
                $this->logger->debug("DBDefinitionModel::registerTableAndColumns() 14 (Inserted table_id: {$tableId})");
            }
            
            // ---- 2. m_columns への処理 ----
            // 取得した table_id を使って、古いカラム定義を一旦リセット（削除）
            $deleteColsSql = "DELETE FROM m_columns WHERE table_id = :table_id";
            $this->logger->debug("DBDefinitionModel::registerTableAndColumns() 21");
            $stmt = $db->prepare($deleteColsSql);
            $this->logger->debug("DBDefinitionModel::registerTableAndColumns() 22");
            $stmt->execute([':table_id' => $tableId]);
            $this->logger->debug("DBDefinitionModel::registerTableAndColumns() 23");
            
            // 画像で判明した正しいカラム定義（フィールドリスト）に修正
            $insertColumnSql = "INSERT INTO m_columns (
                table_id, seq_no, physical_name, logical_name,
                data_type, data_length, is_primary, is_nullable, is_unique, default_value, created_at, updated_at
            ) VALUES (
                :table_id, :seq_no, :physical_name, :logical_name,
                :data_type, :data_length, :is_primary, :is_nullable, :is_unique, :default_value, NOW(), NOW()
            )";
            $this->logger->debug("DBDefinitionModel::registerTableAndColumns() 24");
            
            $stmt = $db->prepare($insertColumnSql);
            $this->logger->debug("DBDefinitionModel::registerTableAndColumns() 25");
            
            foreach ($tableData['columns'] as $col) {
                $stmt->execute([
                    ':table_id'     => $tableId,
                    ':seq_no'       => $col['seq_no'],
                    ':physical_name'=> $col['physical_name'],
                    ':logical_name' => $col['logical_name'],
                    ':data_type'    => $col['data_type'],
                    ':data_length'  => $col['data_length'],
                    ':is_primary'   => $col['is_primary'],
                    ':is_nullable'  => $col['is_nullable'],
                    ':is_unique'    => $col['is_unique'],
                    ':default_value'=> $col['default_value']
                ]);
            }
            $this->logger->debug("DBDefinitionModel::registerTableAndColumns() 26");
            
            // すべて成功したらコミット
            $db->commit();
            $this->logger->debug("DBDefinitionModel::registerTableAndColumns() 27");
            $this->logger->debug("DBDefinitionModel::registerTableAndColumns() commit success.");
            return true;
            
        } catch (\Exception $e) {
            // エラー時はロールバック
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->logger->error("DBDefinitionModel::registerTableAndColumns() で例外発生: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 指定されたテーブルの基本情報を取得する
     * @param string $tableName
     * @return array|null
     */
    public function getTableInfo(string $tableName): ?array {
        $this->logger->debug("DBDefinitionModel::getTableInfo() start... [Table: {$tableName}]");
        try {
            $db = \Framework\Core\Container::getDb_develop();
            $sql = "SELECT table_id, physical_name, logical_name, table_type, description
                    FROM m_tables WHERE physical_name = :pname";
            $stmt = $db->prepare($sql);
            $stmt->execute([':pname' => $tableName]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->error("DBDefinitionModel::getTableInfo() で例外発生: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 指定された table_id に紐づくカラム定義一覧を seq_no 順に取得する
     * @param mixed $tableId
     * @return array
     */
    // 💡 修正：table_id が文字列のケースを考慮し int 型制限を排除
    public function getSavedColumns($tableId): array {
        $this->logger->debug("DBDefinitionModel::getSavedColumns() start... [TableID: {$tableId}]");
        try {
            $db = \Framework\Core\Container::getDb_develop();
            $sql = "SELECT physical_name, logical_name, data_type, data_length,
                           is_primary, is_nullable, is_unique, default_value
                    FROM m_columns
                    WHERE table_id = :table_id
                    ORDER BY seq_no ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute([':table_id' => $tableId]);
            $dbCols = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $columns = [];
            foreach ($dbCols as $col) {
                // View（NewTable.view）のHTML表現（checked="checked"）に合わせてマッピング
                $columns[] = [
                    'physical' => $col['physical_name'],
                    'logical'  => $col['logical_name'] ?? '',
                    'type'     => $col['data_type'],
                    'length'   => $col['data_length'] !== null ? (string)$col['data_length'] : '',
                    'primary'  => ((int)$col['is_primary'] === 1) ? 'checked="checked"' : '',
                    'null'     => ((int)$col['is_nullable'] === 1) ? 'checked="checked"' : '',
                    'unique'   => ((int)$col['is_unique'] === 1) ? 'checked="checked"' : '',
                    'default'  => $col['default_value'] ?? '',
                ];
            }
            return $columns;
        } catch (\Exception $e) {
            $this->logger->error("DBDefinitionModel::getSavedColumns() で例外発生: " . $e->getMessage());
            return [];
        }
    }
}
