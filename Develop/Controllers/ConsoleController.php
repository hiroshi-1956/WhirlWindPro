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
        
        // 4. Console 立ち上げ時の初期セット。
        \Develop\Utils\Session::set('Footer_Message', 'consode start...');
        
        $this->logger->debug("ConsoleController::initialAction() finish.");
    }
    
    /**
     * エリア初期化アクション
     */
    public function areaInitialAction() {
        $this->logger->debug("ConsoleController::AreaInitialAction() start...");
        
        try {
            // Request::post を使用
            $projectId = \Develop\Utils\Request::post('project_id');
            
            if ($projectId) {
                // プロジェクトが選択された場合のみ更新
                \Develop\Utils\Session::set('active_project', $projectId);
                // 名称は ProjectController 内でDBから取得したものを優先するため、ここではIDを入れない
                \Develop\Utils\Session::set('Selected_Project_Name', '');
            } else {
                // 初期表示（window.onload）の時だけリセットしたい場合はここを有効に
                // \Develop\Utils\Session::set('active_project', null);
            }
            \Develop\Utils\Session::set('active_function', null);
            
            // 共通データの準備（既存通り）
            $loginUserName = \Develop\Utils\Session::get('user_name');
            $footerMessage = \Develop\Utils\Session::get('Footer_Message');
            
            $dataA = [ 'Logo_Name' => 'wwProject' ];
            $dataB = [ 'User_Name' => $loginUserName, 'Selected_Project_Name' => '' ];
            $dataF = [ 'Footer_Message' => $footerMessage, 'Current_Year' => '2026', 'Company_Name' => 'WhirlWindPro Team.' ];
            
            // 各エリアのView更新（既存通り）
            \Develop\Utils\Screen::updateAreaA('\Develop\Views\AreaA\Area_A.view', $dataA);
            \Develop\Utils\Screen::updateAreaB('\Develop\Views\AreaB\Area_B.view', $dataB);
            \Develop\Utils\Screen::updateAreaD('\Develop\Views\AreaD\Area_D_clear.view', []);
            \Develop\Utils\Screen::updateAreaE('\Develop\Views\AreaE\Area_E_clear.view', []);
            \Develop\Utils\Screen::updateAreaF('\Develop\Views\AreaF\Area_F.view', $dataF);
            
            // エリアCの再描画
            $projectCtrl = new ProjectController();
            $html = $projectCtrl->startProjectNabi();
            \Develop\Utils\Screen::updateAreaC('\Develop\Views\AreaC\Area_C.view', [ 'Area_C_Html' => $html ]);
            
            $this->logger->debug("ConsoleController::AreaInitialAction() finish.");
        } catch (\Exception $e) {
            $this->logger->error("Console::AreaInitialAction() Error: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function ServerBridgeAction() {
        $this->logger->debug("ConsoleController::ServerBridge() start...");
        
        $code = \Develop\Utils\Request::post('code');
        
        try {
            if (strpos($code, '/') !== false) {
                list($appName, $actionName) = explode('/', $code);
                
                $className = "\\Develop\\Controllers\\{$appName}Controller";
                $methodName = "{$actionName}Action";
                $this->logger->debug("ConsoleController::ServerBridge() {$className}::{$methodName}()");
                
                // クラスの存在チェック（なければ例外を投げる）
                if (!class_exists($className)) {
                    throw new \Exception("App Class Not Found: {$className}");
                }
                
                $instance = new $className();
                
                // メソッドの存在チェック（なければ例外を投げる）
                if (!method_exists($instance, $methodName)) {
                    throw new \Exception("App Method Not Found: {$className}::{$methodName}");
                }
                
                $instance->$methodName();
                
            } else {
                // Console内部メソッド
                $methodName = "{$code}Action";
                if (!method_exists($this, $methodName)) {
                    throw new \Exception("Internal Method Not Found: {$methodName}");
                }
                $this->$methodName();
            }
            
            // 全て正常なら一括出力
            \Develop\Utils\Screen::areaView();
            $this->logger->debug("ConsoleController::ServerBridge() finish.");
            
        } catch (\Throwable $e) {
            $this->logger->error("ConsoleController::ServerBridge Fatal: " . $e->getMessage());
            
            header("HTTP/1.1 500 Internal Server Error");
            echo "Server Error: " . $e->getMessage();
            exit;
        }
    }
}
