<?php

namespace Develop\Controllers;

class DBDefinitionController extends \Develop\Utils\BaseController {
    
    public function initialAction() {
        $this->logger->debug("DBDefinitionController::initialAction() start...");
        
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\DBDefinition\DBDefinitionList.view', []);
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        
        $this->logger->debug("DBDefinitionController::initialAction() end.");
    }
    
    public function newTableAction() {
        $this->logger->debug("DBDefinitionController::initialAction() start...");
        
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\DBDefinition\NewTable.view', []);
        // \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\Area_D_clear.view', []);
        
        $this->logger->debug("DBDefinitionController::initialAction() end.");
    }
}