<?php

namespace Develop\Controllers;

class DesignDefinitionController extends \Develop\Utils\BaseController {
    
    public function initialAction() {
        $this->logger->debug("DBDefinitionController::initialAction() start...");
        
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\DBDefinition\DesignDefinitionList.view', []);
        // \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\Area_D_clear.view', []);
        
        $this->logger->debug("DBDefinitionController::initialAction() end.");
    }
}