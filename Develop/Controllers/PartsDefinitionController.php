<?php

namespace Develop\Controllers;

class PartsDefinitionController extends \Develop\Utils\BaseController {
    
    public function initialAction() {
        $this->logger->debug("DBDefinitionController::initialAction() start...");
        
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\DBDefinition\PartsDefinitionList.view', []);
        
        $this->logger->debug("DBDefinitionController::initialAction() end.");
    }
}