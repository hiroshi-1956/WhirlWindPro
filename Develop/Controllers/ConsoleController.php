<?php
namespace Develop\Controllers;

class ConsoleController extends \Develop\Utils\BaseController {
    
    public function initialAction() {
        $this->logger->debug("Console::initialAction() start...");
        
        // 1. ログインチェック
        $uid = \Develop\Utils\Session::get('id');
        if (! $uid) {
            $base_url = \Framework\Core\Container::get('BASE_URL');
            $screenData = [
                'title' => 'wwProjectシステム',
                'auth_url' => $base_url . '/Develop/Login/authentication',
                'login_id' => '',
                'error_msg' => 'セッションが切れました。ログインし直してください。'
            ];
            \Develop\Utils\Screen::view('\Develop\Views\Login\Login.view', $screenData);
            return;
        }
        
        // 3. 全体の枠組みを表示
        $screenData = [];
        \Develop\Utils\Screen::view('\Develop\Views\Console\Console.view', $screenData);
        
        //$this->AreaInitialAction();
        
        $this->logger->debug("Console::initialAction() finish.");
    }
    
    /**
     * エリア初期化アクション（JSのwindow.onloadから呼ばれる）
     */
    public function AreaInitialAction() {
        $this->logger->debug("Console::AreaInitialAction() start...");
        
        try {
            // 1. データの準備
            $dataA = [
                'Logo_Name' => 'wwProject'
            ];
            
            $loginUserName = \Develop\Utils\Session::get('user_name');
            $dataB = [
                'User_Name' => $loginUserName,
                'Selected_Project_Name' => ''
            ];
            
            $dataF = [
                'Footer_Message' => 'Ready.',
                'Current_Year' => date('Y'),
                'Company_Name' => 'wwProject Team.'
            ];
            
            // 1. 各エリアのViewをセット
            \Develop\Utils\Screen::updateAreaA('\Develop\Views\AreaA\Area_A.view', $dataA);
            \Develop\Utils\Screen::updateAreaB('\Develop\Views\AreaB\Area_B.view', $dataB);
            \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\Area_D_clear.view', []);
            \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
            \Develop\Utils\Screen::updateAreaF('\Develop\Views\AreaF\Area_F.view', $dataF);
            
            // 2. エリアCのViewをセット
            //setSessionValue('active_project', null);
            //setSessionValue('active_function', null);
            
            //$projectCtrl = new ProjectController();
            //$html = $projectCtrl->startProjectNabi();
            
            //\Develop\Utils\Screen::updateAreaC('\Develop\Views\AreaC\Area_C.view', [
            //    'Menu_Html' => $html
            //]);
            
            // 3. JSON形式で全エリアをまとめて返却
            \Develop\Utils\Screen::areaView();
            
            $this->logger->debug("Console::AreaInitialAction() finish.");
        } catch (\Exception $e) {
            $this->logger->error("AreaInitialAction Error: " . $e->getMessage());
            return json_encode([
                'error' => $e->getMessage()
            ]);
        }
    }
}
