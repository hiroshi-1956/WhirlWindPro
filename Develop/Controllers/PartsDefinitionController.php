<?php

namespace Develop\Controllers;

class PartsDefinitionController extends \Develop\Utils\BaseController {
    
    public function initialAction() {
        $this->logger->debug("PartsDefinitionController::initialAction() start...");
        
        \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\PartsDefinition\PartsDefinitionList.view', []);
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        
        $this->logger->debug("PartsDefinitionController::initialAction() end.");
    }
    
    public function newPartsAction() {
        $this->logger->debug("PartsDefinitionController::newPartsAction() start...");
        
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\PartsDefinition\NewParts.view', []);
        
        $this->logger->debug("PartsDefinitionController::newPartsAction() end.");
    }
    
    public function sizeChangeActio() {
        $this->logger->debug("PartsDefinitionController::sizeChangeActio() start...");
        
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
        
        $this->logger->debug("PartsDefinitionController::sizeChangeActio() end.");
    }
    
    public function PartsDefinitionList() {
        $this->logger->debug("PartsDefinitionController::PartsDefinitionList() start...");
        

        
        $this->logger->debug("PartsDefinitionController::PartsDefinitionList() end.");
    }
    
    public function image() {
        $this->logger->debug("PartsDefinitionController::newPartsSizeAction() start...");
        
        $this->logger->debug("PartsDefinitionController::newPartsSizeAction() end.");
    }
    
    public function screenBiggerAction() {
        $this->logger->debug("PartsDefinitionController::screenBigger() start...");
        
        $this->save();
        
        $row_pos　= \Develop\Utils\Request::post('row_pos');
        $col_pos　= \Develop\Utils\Request::post('col_pos');
        $parts_name　= \Develop\Utils\Request::post('parts_name　');
        
        \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\PartsDefinition\frame.view', []);
        
        $this->logger->debug("PartsDefinitionController::screenBigger() end.");
    }
    
    public function save() {
        $this->logger->debug("PartsDefinitionController::save() start...");

        $row_pos　= \Develop\Utils\Request::post('row_pos');
        $col_pos　= \Develop\Utils\Request::post('col_pos');
        $parts_name　= \Develop\Utils\Request::post('parts_name　');
        
        $this->logger->debug("PartsDefinitionController::save() end.");
    }
}