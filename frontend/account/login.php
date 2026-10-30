
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Hardware Manager</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="login-page">

    <main class="login-container">
        <div class="brand-icon">H</div>

        <h1>Hardware Manager</h1>
        <p class="login-subtitle">Sign in to manage your organization's assets.</p>

        <form action="#" method="post">
            <div class="form-group">
                <label for="username">Username or email</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >
            </div>

            <div class="login-options">
                <label class="remember-option">
                    <input type="checkbox" name="remember">
                    Remember me
                </label>
                <a href="#">Forgot password?</a>
            </div>

            <button type="submit" class="login-button">Sign In</button>
        </form>

        <p class="register-link">
            Don't have an account?
            <a href="register.php">Create account</a>
        </p>
    </main>

</body>
</html>
