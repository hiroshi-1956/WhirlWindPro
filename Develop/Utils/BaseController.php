<?php

namespace Develop\Utils;

// コントローラー基底抽象クラス
abstract class BaseController {
    protected $logger;
    protected $view;
    
    public function __construct() {
        
        $this->logger = \Framework\Core\Container::get('logger');
        $this->view = new \Framework\Core\View();
    }
}
