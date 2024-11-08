<?php


class Controller_Taskapp extends Controller
{

    // beforeメソッドで、各アクション前に認証チェックを追加
    public function before()
    {
        parent::before();

        // 認証不要のアクションを設定
        $unrestricted_actions = ['userRegister','login']; //manageTasksは仮置き（js練習中）

        // 現在のアクションを取得
        $current_action = \Request::active()->action;

        // 認証不要アクションでなければ認証チェックを実施
        if (!in_array($current_action, $unrestricted_actions)) {
            // 認証が成功しない場合はログインページにリダイレクト
            if (!\Auth::check()) {
                \Response::redirect('taskapp/login');
            }
        }
        
    }

    public function action_login()
    {
        // POSTリクエストの時はユーザ認証を試みる
        if (Input::method() == 'POST')
        {
            $loginData = Input::post();
            $username = $loginData['username'];
            $password = $loginData['password'];

            // ユーザー認証を試みる
            if (Auth::login($username, $password)) 
            {
                // 認証成功時はタスク管理画面にリダイレクト
                Session::set_flash('success','ログインしました！');
                Response::redirect('taskapp/manageTasks');
            } else 
                {
                    // 認証失敗時にエラーメッセージをフラッシュセッションに保存
                    Session::set_flash('error', 'ユーザー名またはパスワードが間違っています。');
                }
        }

        // GETリクエストor認証失敗の時はログインビューを表示
        return Response::forge(View::forge('login'));
    }

    public function action_userRegister()
    {
        // POSTリクエストかどうかを確認
        if (Input::method() == 'POST') 
        {
            // POSTされたデータを取得
            $username = Input::post('username');
            $email = Input::post('email');
            $password = Input::post('password');

            // 簡単なバリデーション
            if (empty($username) || empty($email) || empty($password)) {
                Session::set_flash('error', '全ての項目を入力してください。');
                return Response::forge(View::forge('userRegister'));
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Session::set_flash('error', '正しいメールアドレスを入力してください。');
                return Response::forge(View::forge('userRegister'));
            }

            if (strlen($password) < 8) {
                Session::set_flash('error', 'パスワードは8文字以上にしてください。');
                return Response::forge(View::forge('userRegister'));
            }

            // 新規ユーザーの登録処理
            try 
            {
                // 新しいユーザを登録する
                Auth::create_user($username, $password, $email);

                // 前回のユーザーセッションが残る可能性があるため、明示的にログアウト
                Auth::logout();
                
                // 登録成功時はログイン画面にリダイレクト
                Session::set_flash('success', 'ユーザー登録が完了しました。');
                Response::redirect('taskapp/login');
                
            } catch (Exception $e) 
            {
                // 登録に失敗した場合はエラーメッセージを表示
                Session::set_flash('error', 'ユーザー登録に失敗しました: ' . $e->getMessage());
            }
        }

        // GETリクエストの場合、または登録に失敗した場合は登録フォームを表示
        return Response::forge(View::forge('userRegister')); 
    }

    public function action_manageTasks()


    {
        // 現在のユーザーIDを取得
        list(, $user_id) = \Auth::get_user_id();

        // タスクリストを取得
        $tasks = Model_Task::taskList($user_id);

        // セッションに保存されたトークンがあれば取得し、なければ新規作成
        if (!$csrf_token = Session::get('csrf_token')) {
            $csrf_token = Security::fetch_token();
            Session::set('csrf_token', $csrf_token); // セッションにトークンを保存
        }

        // 重要度の選択肢を取得
        Config::load('importance', true);
        $importanceOptions = Config::get('importance.options');
        
        // タスクの内容ををビューに渡す
        return View::forge('manageTasks', [
            'tasks' => $tasks,
            'importanceOptions' => $importanceOptions,
            'csrf_token' => $csrf_token
        ]); // 配列の、に注意
    }

    public function post_createTask()
    {


        try {

            // POSTデータを取得
            $post = \Input::json();

            // セッションからCSRFトークンを取得し、送信されたトークンと照合
            $expected_token = Session::get('csrf_token');
            $csrf_token = \Input::headers('X-CSRF-Token');

            if ($csrf_token !== $expected_token) {
                Log::error('CSRFトークンが一致しませんでした。送信されたトークン: ' . $csrf_token);
                return Response::forge(json_encode(['status' => 'error', 'message' => 'CSRFトークンが無効です']), 400)
                                ->set_header('Content-Type', 'application/json');
            }

            // 現在のユーザーIDを取得
            list(, $user_id) = \Auth::get_user_id();
    
            // 必要なデータが存在するか確認
            if (!empty($post['taskname']) && !empty($post['category']) && !empty($post['importance'])) {
                // タスクを追加するためにModel_Taskを呼び出し
                Model_Task::addTask($post['taskname'], $post['category'], $post['importance'],$user_id);

                return Response::forge(json_encode(['status' => 'true', 'message' => 'タスクが追加されました']), 200)
                                ->set_header('Content-Type', 'application/json');
            } else {
                // エラーメッセージを返す
                return Response::forge(json_encode(['status' => 'error', 'message' => 'タスクデータが不足しています']), 400)
                                ->set_header('Content-Type', 'application/json');
            }
        } catch (Exception $e) {
            // エラーメッセージをログに記録して、デバッグ出力する
            Log::error('タスク追加時にエラーが発生: ' . $e->getMessage());
            return Response::forge(json_encode(['status' => 'error', 'message' => 'サーバーでエラーが発生しました']), 500)
                            ->set_header('Content-Type', 'application/json');
        }
    }

    public function post_deleteTask() 
    {
        try {
            // POSTデータを取得
            $post = \Input::json();
    
            // セッションからCSRFトークンを取得し、送信されたトークンと照合
            $csrf_token = \Input::headers('X-CSRF-Token');
            $expected_token = Session::get('csrf_token');
    
            if ($csrf_token !== $expected_token) {
                Log::error('CSRFトークンが一致しません。送信されたトークン: ' . $csrf_token);
                return \Response::forge(json_encode(['status' => false, 'message' => 'CSRFトークンが無効です']), 400)
                                ->set_header('Content-Type', 'application/json');
            }
    
            // 現在のユーザーIDを取得
            list(, $user_id) = \Auth::get_user_id();
    
            // タスク削除処理を実行
            $result = Model_Task::deleteTask($post['taskname'], $post['category'], $user_id);

            // 成功または失敗の結果に応じてレスポンスを返す
            if ($result) {
                return \Response::forge(json_encode(['status' => 'true', 'message' => 'タスクが削除されました']), 200)
                                ->set_header('Content-Type', 'application/json');
            } else {
                return \Response::forge(json_encode(['status' => false, 'message' => '削除するタスクが見つかりませんでした']), 404)
                                ->set_header('Content-Type', 'application/json');
            }
        } catch (Exception $e) {
            // エラーログを記録し、サーバーエラーレスポンスを返す
            Log::error('タスク削除時にエラーが発生: ' . $e->getMessage());
            return \Response::forge(json_encode(['status' => false, 'message' => 'サーバーでエラーが発生しました']), 500)
                            ->set_header('Content-Type', 'application/json');
        }
    }

    public function post_updateTask()
    {
        try {
            // POSTデータを取得
            $post = \Input::json();
    
            // セッションからCSRFトークンを取得し、送信されたトークンと照合
            $csrf_token = \Input::headers('X-CSRF-Token');
            $expected_token = Session::get('csrf_token');
    
            if ($csrf_token !== $expected_token) {
                Log::error('CSRFトークンが一致しません。送信されたトークン: ' . $csrf_token);
                return \Response::forge(json_encode(['status' => false, 'message' => 'CSRFトークンが無効です']), 400)
                                ->set_header('Content-Type', 'application/json');
            }
    
            // 現在のユーザーIDを取得
            list(, $user_id) = \Auth::get_user_id();
    
            // タスクの編集処理
            $result = Model_Task::updateTask($post['taskname'], $post['category'], $post['importance'], $post['pre_taskname'], $user_id);
    
            // 結果に応じてレスポンスを返す
            if ($result) {
                return \Response::forge(json_encode(['status' => 'true', 'message' => 'タスクが更新されました']), 200)
                                ->set_header('Content-Type', 'application/json');
            } else {
                return \Response::forge(json_encode(['status' => false, 'message' => '更新するタスクが見つかりませんでした']), 404)
                                ->set_header('Content-Type', 'application/json');
            }
        } catch (Exception $e) {
            // エラーログを記録し、サーバーエラーレスポンスを返す
            Log::error('タスク更新時にエラーが発生: ' . $e->getMessage());
            return \Response::forge(json_encode(['status' => false, 'message' => 'サーバーでエラーが発生しました']), 500)
                            ->set_header('Content-Type', 'application/json');
        }
    }
}