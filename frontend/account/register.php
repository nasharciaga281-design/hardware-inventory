
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Hardware Manager</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="login-page">

    <main class="login-container">
        <div class="brand-icon">H</div>

        <h1>Create an account</h1>
        <p class="login-subtitle">
            Register to access the Hardware Manager.
        </p>

        <form action="#" method="post">
            <div class="form-group">
                <label for="fullname">Full name</label>
                <input type="text" id="fullname" name="fullname"
                       placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       placeholder="Choose a username" required>
            </div>

            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email"
                       placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Create a password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm password</label>
                <input type="password" id="confirm_password"
                       name="confirm_password"
                       placeholder="Re-enter your password" required>
            </div>

            <button type="submit" class="login-button">
                Create Account
            </button>
        </form>

        <p class="register-link">
            Already have an account?
            <a href="login.php">Sign in</a>
        </p>
    </main>

</body>
</html>
