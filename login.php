<?php
include 'includes/db.php';
include 'includes/functions.php';
session_start();

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['hashed_password'])) {
                $_SESSION['user_id'] = $user['ID_USER'];
                $_SESSION['username'] = $user['username'];
                redirect('index.php');
            } else {
                $error_message = "Wrong password, please try again.";
            }
        } else {
            $error_message = "No user found with that username.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        $strength_criteria = [
            'length' => strlen($password) >= 8,
            'lowercase' => preg_match('/[a-z]/', $password),
            'uppercase' => preg_match('/[A-Z]/', $password),
            'digit' => preg_match('/\d/', $password),
            'special' => preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)
        ];

        if (in_array(false, $strength_criteria)) {
            $error_message = "Password must contain at least 8 characters, one lowercase letter, one uppercase letter, one digit, and one special character.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql_check = "SELECT * FROM users WHERE username = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param('s', $username);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $error_message = "Username already taken. Please choose a different username.";
            } else {
                $sql = "INSERT INTO users (First_Name, Last_Name, username, email, hashed_password, Role, created_at)
                        VALUES (?, ?, ?, ?, ?, 'user', NOW())";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssss', $first_name, $last_name, $username, $email, $hashed_password);

                if ($stmt->execute()) {
                    echo "Registration successful! You can now <a href='login.php'>login</a>.";
                } else {
                    $error_message = "Error: " . $stmt->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Register</title>
    <?php include 'includes/fontawesome.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            const $loginBtn = $("#login");
            const $registerBtn = $("#register");
            const $loginForm = $(".login-form");
            const $registerForm = $(".register-form");

            $loginBtn.on("click", function() {
                $loginBtn.css("background-color", "rgba(130, 219, 215, 0.2)");
                $registerBtn.css("background-color", "rgba(255, 255, 255, 0.2)");

                $loginForm.css({
                    "left": "50%",
                    "opacity": "1"
                });
                $registerForm.css({
                    "left": "150%",
                    "opacity": "0"
                });
                $(".col-1").css("border-radius", "0 20% 20% 0");
            });

            $registerBtn.on("click", function() {
                $registerBtn.css("background-color", "rgba(130, 219, 215, 0.2)");
                $loginBtn.css("background-color", "rgba(255, 255, 255, 0.2)");

                $registerForm.css({
                    "left": "50%",
                    "opacity": "1"
                });
                $loginForm.css({
                    "left": "-50%",
                    "opacity": "0"
                });
                $(".col-1").css("border-radius", "0 30% 30% 0");
            });

            $(".input-box .icon-password ").click(function() {
                var passwordField = $(this).siblings(".input-field");
                var icon = $(this);
                if (passwordField.attr("type") === "password") {
                    passwordField.attr("type", "text");
                    icon.removeClass("fa-eye-slash").addClass("fa-eye");
                } else {
                    passwordField.attr("type", "password");
                    icon.removeClass("fa-eye").addClass("fa-eye-slash");
                }
            });

            $('#new_password').on('input', function() {
                const password = $(this).val();
                const strengthMeter = $('#password-strength');
                const strengthText = $('#strength-text');
                let conditionsMet = 0;

                if (password.length >= 8) conditionsMet++;
                if (/[a-z]/.test(password)) conditionsMet++;
                if (/[A-Z]/.test(password)) conditionsMet++;
                if (/\d/.test(password)) conditionsMet++;
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) conditionsMet++;

                if (conditionsMet === 5) {
                    strengthMeter.removeClass().addClass('strong');
                    strengthText.text('Strong password').css('color', 'green');
                } else if (conditionsMet >= 3) {
                    strengthMeter.removeClass().addClass('moderate');
                    strengthText.text('Moderate password. Please meet the criteria').css('color', 'rgb(255, 187, 0)');
                } else if (conditionsMet <= 2) {
                    strengthMeter.removeClass().addClass('weak');
                    strengthText.text('Weak password.(e.g. A2a)1oiu)').css('color', 'red');
                }
            });
        });
    </script>
    <link rel="stylesheet" href="assets/styles/login.css">
</head>
<body>
    <div class="form-container">
        <div class="col col-1">
            <div class="image-layer">
                <img src="assets/images/login.png" class="form-image-main">
            </div>
            <p class="featured-words">You Are Few minutes Away To Save Our Favourite Things With <span>MYVAULT</span></p>
        </div>
        <div class="col col-2">
            <div class="btn-box">
                <button class="btn btn-1" id="login">Sign In</button>
                <button class="btn btn-2" id="register">Sign Up</button>
                <div class="form-home-link">
                    <a href="index.php"><i class="fa-solid fa-house"></i></a>
                </div>
            </div>

            <div class="login-form">
                <div class="form-title"><span>Sign In</span></div>
                <div class="form-inputs">
                    <form method="POST" id="login-form">
                        <input type="hidden" name="action" value="login">
                        <div class="input-box">
                            <input type="text" class="input-field" name="username" placeholder="Username" required>
                            <i class="icon fa-regular fa-circle-user"></i>
                        </div>
                        <div class="input-box">
                            <input type="password" class="input-field" name="password" placeholder="Password" required>
                            <i class="icon icon-password fa-regular fa-eye-slash"></i>
                        </div>
                        <div class="forgot-pass"><a href="#">Forgot Password?</a></div>
                        <div class="input-box">
                            <button class="input-submit">
                                <span>Sign In</span>
                                <i class="icon fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                        <?php if ($error_message): ?>
                            <div class="error-message"><p><?php echo $error_message; ?></p></div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="register-form">
                <div class="form-title"><span>Sign Up</span></div>
                <div class="form-inputs">
                    <form method="POST" id="register-form">
                        <input type="hidden" name="action" value="register">
                        <div class="input-box">
                            <input type="text" class="input-field" name="username" placeholder="Username" required>
                            <i class="icon fa-regular fa-circle-user"></i>
                        </div>
                        <div class="input-box">
                            <input type="text" class="input-field" name="first_name" placeholder="First Name" required>
                            <i class="icon fa-regular fa-user"></i>
                        </div>
                        <div class="input-box">
                            <input type="text" class="input-field" name="last_name" placeholder="Last Name" required>
                            <i class="icon fa-regular fa-user"></i>
                        </div>
                        <div class="input-box">
                            <input type="text" class="input-field" name="email" placeholder="Email" required>
                            <i class="icon fa-regular fa-envelope"></i>
                        </div>
                        <div class="input-box">
                            <input type="password" id="new_password" class="input-field" name="password" placeholder="Password" required>
                            <i class="icon icon-password fa-regular fa-eye-slash"></i>
                        </div>
                        <div class="password-strength">
                            <div id="password-strength"></div>
                            <p id="strength-text"></p>
                        </div>
                        <div class="forgot-pass"><a href="#">Forgot Password?</a></div>
                        <div class="input-box">
                            <button class="input-submit">
                                <span>Sign Up</span>
                                <i class="icon fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                        <?php if ($error_message): ?>
                            <div class="error-message"><p><?php echo $error_message; ?></p></div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
