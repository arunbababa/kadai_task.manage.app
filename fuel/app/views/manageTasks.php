<!DOCTYPE html>
<html lang="ja">
<head>
    <title>タスク管理</title>
    <meta name="fuel_csrf_token" content="<?= $csrf_token; ?>">
    <script src="https://cdn.jsdelivr.net/npm/knockout@3.5.1/build/output/knockout-latest.js"></script>
</head>
<body>

    <!-- ログインできたかどうかの確認のセッション -->
    <?php if (Session::get_flash('success')): ?>
        <p><?php echo Session::get_flash('success'); ?></p>
    <?php endif; ?>

    <!-- フラッシュメッセージの表示場所 -->
    <div id="flash-messages"></div>

    <h1>タスク管理</h1>

    <!-- タスク追加フォーム -->
    <h2>タスクを追加</h2>
    <form data-bind="submit: addTask"> 
        <input type="text" placeholder="タスク名" data-bind="value: newTaskName" required /> 
        <input type="text" placeholder="カテゴリ" data-bind="value: newCategory" required /> 
        <select data-bind="value: newImportance">
            <option value=""><?= $importanceOptions['default'] ?></option> <!-- デフォルトのラベル -->
            <?php foreach ($importanceOptions['values'] as $value): ?>  <!-- PHPで設定ファイルからオプションを動的に生成 -->
                <option value="<?= $value ?>"><?= $value ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">追加</button> <!-- これを押すとaddTaskメソッドが呼ばれて、タスクが追加されます。 -->
    </form>

    <!-- タスクリストの表示 -->
    <h2>タスクリスト</h2>
    <ul data-bind="foreach: tasks">
        <li>
            <!-- 編集モードの切り替え: 通常表示と編集フィールド -->
            <span data-bind="visible: !editing(), text: taskname"></span>
            <input type="text" data-bind="visible: editing, value: taskname" />

            <span data-bind="visible: !editing(), text: category"></span>
            <input type="text" data-bind="visible: editing, value: category" />

            <span data-bind="visible: !editing(), text: importance"></span>
            <select data-bind="visible: editing, value: importance">
                <option value="低">低</option>
                <option value="中">中</option>
                <option value="高">高</option>
            </select>

            <!-- 編集と保存ボタン -->
            <button data-bind="click: editTask, visible: !editing()">編集</button>
            <button data-bind="click: saveTask, visible: editing">保存</button>
            <button data-bind="click: $parent.removeTask">削除</button>
        </li>
    </ul>

    <script>
        console.log(<?= json_encode($tasks); ?>);  // タスクリストを確認
    </script>

    <script>

    function displayFlashMessage(message, type = 'success') {
        const flashContainer = document.getElementById('flash-messages');
        const flashMessage = document.createElement('p');
        flashMessage.textContent = message;
        flashMessage.className = type;  // メッセージの種類に応じたクラスを設定（スタイリング用）
        flashContainer.appendChild(flashMessage);
        // 一定時間後にフラッシュメッセージを自動で消去
        setTimeout(() => {
            flashMessage.remove();
        }, 5000);  // 5秒後にメッセージを削除
    }


    function Task(data) {
        this.taskname = ko.observable(data.taskname);
        this.original_taskname = data.taskname;  // 編集前のタスク名を保持
        this.category = ko.observable(data.category);
        this.importance = ko.observable(data.importance);
        this.editing = ko.observable(false);  // 編集モードのフラグ
    }

    function TaskViewModel() {
        const self = this;
        console.log("Knockout.jsバインドが初期化されました");
        
        // 初期タスクリストのデータをTaskオブジェクトに変換
        const mappedTasks = <?= json_encode($tasks); ?>.map(function(task) {
            return new Task(task);
        });
        
        self.tasks = ko.observableArray(mappedTasks); // 初期タスクリスト
        
        self.newTaskName = ko.observable('');
        self.newCategory = ko.observable('');
        self.newImportance = ko.observable('');
        // Configから読み込んだ重要度の選択肢を渡す
        self.importanceOptions = <?= json_encode($importanceOptions['values']); ?>;


    // タスクを追加するメソッド
    self.addTask = function() {

        console.log("addTaskメソッドが呼ばれました");

        // CSRFトークンをメタタグから取得
        const csrfToken = document.querySelector('meta[name="fuel_csrf_token"]').getAttribute('content');

        const newTask = {
            taskname: self.newTaskName(),
            category: self.newCategory(),
            importance: self.newImportance()
        };

        console.log("新しいタスクをリストに追加します:", newTask);
        // フロントエンドでリストに追加
        // self.tasks.push(new Task(newTask));

        // サーバーにタスクを保存する処理を呼び出す（createコントローラ)
        fetch('/taskApp/createTask', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken 
            },
            body: JSON.stringify(newTask)
        }).then(response => response.json())
        .then(data => {
            if (data.status === 'true') {
                console.log(data.message);  // 成功メッセージを表示
                // 成功メッセージをフロントエンドで表示
                displayFlashMessage(data.message, 'success');
                self.tasks.push(new Task(newTask));
                // 入力フィールドをリセット
                self.newTaskName('');
                self.newCategory('');
                self.newImportance('中');
            } else {
                console.log(data.message);  // エラーメッセージを表示
            }
        });

        console.log("現在のタスクリスト:", self.tasks());
    };

    // タスクを削除するメソッド
    self.removeTask = function(task) {

        const csrfToken = document.querySelector('meta[name="fuel_csrf_token"]').getAttribute('content');
        
        // サーバーに削除リクエストを送信
        fetch('/taskapp/deleteTask', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken // CSRFトークンを追加
            },
            body: JSON.stringify({
                taskname: task.taskname(),
                category: task.category()
            })
        })
        .then(response => response.json())  // テキストレスポンスを処理
        .then(data => {
            if (data.status === 'true') {
            console.log(data);  // 成功メッセージを表示
            displayFlashMessage(data.message, 'success');
            // 成功後にフロントエンドでリストから削除
            self.tasks.remove(task);
            } else {
                    console.log(data.message);  // エラーメッセージを表示
                }
        })
        .catch(error => {
            console.error('Error:', error);
        });
            };
        }

    // タスクを更新するメソッド
    self.editTask = function(task) {

        console.log("editTaskメソッドが呼ばれました:", task);

        task.editing(true);  // 編集モードに切り替え
};

self.saveTask = function(task) {
    task.editing(false);  // 編集モードを解除

    // CSRFトークンをメタタグから取得
    const csrfToken = document.querySelector('meta[name="fuel_csrf_token"]').getAttribute('content');

    // サーバーに更新内容を送信
    const updatedTask = {
        pre_taskname: task.original_taskname, 
        taskname: task.taskname(),
        category: task.category(),
        importance: task.importance()
    };

    fetch('/taskApp/updateTask', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken  // CSRFトークンを追加
        },
        body: JSON.stringify(updatedTask)
    }).then(response => response.json())
    .then(data => {
        if (data.status === 'true') {
            console.log(data.message);  // 成功メッセージ
            displayFlashMessage(data.message,'success')
        } else {
            console.log(data.message);  // エラーメッセージ
        }
    });
}

        ko.applyBindings(new TaskViewModel());
    </script>

</body>
</html>
