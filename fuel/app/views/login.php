<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <link rel="stylesheet" href="/assets/css/styleforlogin.css">
</head>
<body>
    <div class="container">
        
        <h1>ログイン</h1>

        <?php if (Session::get_flash('error')): ?>
            <p class="error-message"><?php echo Session::get_flash('error'); ?></p>
        <?php endif; ?>

        <form action="/taskapp/login_check" method="post">
            <div class="form-group">
                <label for="username">ユーザー名:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">パスワード:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">ログイン</button>
        </form>

        <p><a href="/taskapp/user_register">まだ登録していませんか？新規登録はこちら</a></p>
    </div>
</body>
</html>
