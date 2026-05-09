<?php

namespace Develop\Controllers;

class ProjectDefinitionController extends \Develop\Utils\BaseController {
    
    public function initialAction() {
        $this->logger->debug("DBDefinitionController::initialAction() start...");
        
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\DBDefinition\DBDefinitionList.view', []);
        // \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\Area_D_clear.view', []);
        
        $this->logger->debug("DBDefinitionController::initialAction() end.");
    }
}