<?php

namespace Develop\Controllers;

class LoginController extends \Develop\Utils\BaseController {
    
    /**
     * ログイン画面の初期表示
     */
    public function initialAction() {
        $this->logger->debug("Login::initialAction() start...");
        
        $error = \Develop\Utils\Request::get('error');
        $errorMessage = ($error === '1') ? 'IDまたはパスワードが正しくありません' : '';
        $loginId = \Develop\Utils\Request::get('login_id');
        
        $this->logger->debug("Login::initialAction() errorMessage : {$errorMessage}");
        
        $base_url = \Framework\Core\Container::get('BASE_URL');
        $screenData = [
            'title' => 'wwProjectシステム',
            'auth_url' => $base_url . '/Develop/Login/authentication',
            'login_id' => $loginId,
            'error_msg' => $errorMessage
        ];
        
        // ログイン画面の表示
        \Develop\Utils\Screen::view('\Develop\Views\Login\Login.view', $screenData);
        
        $this->logger->debug("Login::initialAction() finish.");
    }
    
    /**
     * 認証処理
     */
    public function AuthenticationAction() {
        $this->logger->debug("Login::AuthenticationAction() start...");
        
        try {
            $db = \Framework\Core\Container::getDb_develop();
            
            $login_id = \Develop\Utils\Request::post('login_id');
            $password = \Develop\Utils\Request::post('password');
            
            // ユーザー取得
            $stmt = $db->prepare("SELECT * FROM developers WHERE BINARY login_id = :login_id LIMIT 1");
            $stmt->execute([':login_id' => $login_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $authenticated = false;
                
                // パスワード照合
                if ($user['is_hash'] == 1) {
                    if (password_verify($password, $user['password'])) {
                        $authenticated = true;
                    }
                } else {
                    if ($password === $user['password']) {
                        $authenticated = true;
                    }
                }
                
                if ($authenticated) {
                    $this->logger->debug("Login OK - {$login_id}");
                    
                    session_regenerate_id(true);
                    \Develop\Utils\Session::loginSuccess($user);
                    
                    // --- 重要修正箇所：図の1枚目から2枚目へバトンを渡す ---
                    $base_url = \Framework\Core\Container::get('BASE_URL');
                    $this->logger->debug("Redirect to Console::initialAction()");
                    
                    // 直接Viewを表示せず、コントローラーの初期化処理へリダイレクト
                    header("Location: {$base_url}/Develop/Console/initial");
                    exit;
                    // ---------------------------------------------------
                }
            }
            
            // 認証失敗（共通処理）
            $this->logger->debug("Login NG - {$login_id}");
            $this->showErrorPage($login_id, 'IDまたはパスワードが正しくありません');
            
        } catch (\PDOException $e) {
            $this->logger->error("Login::AuthenticationAction() DB Error: " . $e->getMessage());
            $this->showErrorPage(\Develop\Utils\Request::post('login_id'), 'システムエラー：データベースに接続できません。');
        }
    }
    
    /**
     * エラー時のログイン画面再表示（共通化）
     */
    private function showErrorPage($loginId, $msg) {
        $base_url = \Framework\Core\Container::get('BASE_URL');
        $screenData = [
            'title'     => 'wwProjectシステム',
            'auth_url'  => $base_url . '/Develop/Login/authentication',
            'login_id'  => $loginId,
            'error_msg' => $msg
        ];
        \Develop\Utils\Screen::view('\Develop\Views\Login\Login.view', $screenData);
        $this->logger->debug("Login::AuthenticationAction() finish with error.");
    }
    
    /**
     * ログアウト実行
     */
    public function logoutAction() {
        $this->logger->debug("LoginController::logoutAction() start...");
        
        $userName = \Develop\Utils\Session::get('user_name') ?? 'Unknown';
        $this->logger->debug("Logout実行: ユーザー[{$userName}]");
        
        \Develop\Utils\Session::destroy();
        
        $base_url = \Framework\Core\Container::get('BASE_URL');
        header("Location: {$base_url}/Develop/Login/initialAction");
        exit;
    }
}
