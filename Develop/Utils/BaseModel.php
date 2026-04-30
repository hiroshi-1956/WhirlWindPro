<?php
namespace Develop\Utils;

abstract class BaseModel {
    protected $logger;
    protected $db;
    
    public function __construct() {
        // Containerから直接ロガーを取得
        $this->logger = \Framework\Core\Container::get('logger');
        $this->db = \Framework\Core\Container::getDb_develop();
    }
}
