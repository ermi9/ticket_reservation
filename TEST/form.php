<!DOCTYPE html>
<html>
<head>
    <title>GET vs POST Demo</title>
</head>
<body>
    <h2>Login Form (GET)</h2>
    <form action="get_handler.php" method="GET">
        Username: <input type="text" name="username"><br>
        Password: <input type="password" name="password"><br>
        <input type="submit" value="Login with GET">
    </form>

    <hr>

    <h2>Login Form (POST)</h2>
    <form action="post_handler.php" method="POST">
        Username: <input type="text" name="username"><br>
        Password: <input type="password" name="password"><br>
        <input type="submit" value="Login with POST">
    </form>
</body>
</html>
