<?php
namespace Develop\Utils;

use Framework\Core\Container;

abstract class BaseModel {
    protected $logger;
    protected $db;
    
    public function __construct() {
        // Containerから直接ロガーを取得
        $this->logger = Container::get('logger');
        $this->db = Container::getDb_develop();
    }
}
