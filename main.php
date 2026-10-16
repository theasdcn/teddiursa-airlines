<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/assets/img/TED_LOGOwoBG.png">
    
</head>
<body>
    <div class="corner-star" style="top: 20px; left: 20px; animation-delay: 0s;"></div>
    <div class="corner-star" style="top: 20px; right: 20px; animation-delay: 1s;"></div>
    <div class="corner-star" style="bottom: 20px; left: 20px; animation-delay: 2s;"></div>
    <div class="corner-star" style="bottom: 20px; right: 20px; animation-delay: 3s;"></div>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>

    <div class="login-wrapper">
        <div class="logo-container">
            <div class="logo">
                <img src="/assets/img/LOGO-Transparent.png" alt="Logo">
            </div>
        </div>

        <div class="login-container">
            <h2>Login</h2>

            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" placeholder="Enter your username">
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" placeholder="Enter your password">
            </div>

            <button class="login-btn" type="button">Login</button>

            <div class="register-link">
                Don't have an account? <a href="/pages/signup/signup.php">Register</a>
            </div>
        </div>
    </div>

    <script src="/assets/js/script.js"></script>
</body>
</html>
