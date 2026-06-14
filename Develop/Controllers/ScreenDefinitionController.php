<?php

namespace Develop\Controllers;

class ScreenDefinitionController extends \Develop\Utils\BaseController {
    
    public function initialAction() {
        $this->logger->debug("DBDefinitionController::initialAction() start...");
        
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\ScreenDefinition\ScreenDefinitionList.view', []);
        
        $this->logger->debug("DBDefinitionController::initialAction() end.");
    }
}