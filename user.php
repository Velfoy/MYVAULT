<?php
session_start();
include 'includes/functions.php';
include 'includes/db.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add_user_with_role') {
        $username = sanitize_input($_POST['username']);
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $email = sanitize_input($_POST['email']);
        $number = sanitize_input($_POST['number']);
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT); 
        $role = sanitize_input($_POST['role']);

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $uploadFileDir = 'uploads/users/';
            $dest_path = $uploadFileDir . basename($fileName);

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $sql_add_user = "INSERT INTO users (username, First_Name, Last_Name, email, Number, hashed_password, Role, Image_Link) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_add_user = $conn->prepare($sql_add_user);
                $stmt_add_user->bind_param('ssssssss', $username, $first_name, $last_name, $email, $number, $password, $role, $dest_path);

                if (!$stmt_add_user->execute()) {
                    echo "Error adding user: " . $stmt_add_user->error;
                }
            } else {
                echo "Error moving the uploaded file.";
            }
        } else {
            echo "Error uploading the image.";
        }
    } elseif ($_POST['action'] === 'change_user_role') {
        $newRole = sanitize_input($_POST['new_role']);
        $userId = (int)$_POST['user_id'];

        $sql_update_role = "UPDATE users SET Role = ? WHERE ID_USER = ?";
        $stmt_update_role = $conn->prepare($sql_update_role);
        $stmt_update_role->bind_param('si', $newRole, $userId);

        if (!$stmt_update_role->execute()) {
             echo "Error updating user role: " . $stmt_update_role->error;
        }
    } elseif ($_POST['action'] === 'add_movie') {
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $category_id = (int)$_POST['category_id'];
        $user_id = $_SESSION['user_id'];
        $visibility = 1;
        $like =0;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $uploadFileDir = 'uploads/movies/';
            $dest_path = $uploadFileDir . basename($fileName);

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $sql_add_movie = "INSERT INTO movies (Title, Description, Category_id, user_id, visibility, `like`, image_link) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_add_movie = $conn->prepare($sql_add_movie);
                $stmt_add_movie->bind_param('ssiiiss', $title, $description, $category_id, $user_id, $visibility, $like, $dest_path);

                if (!$stmt_add_movie->execute()) {
                    echo "Error adding movie: " . $stmt_add_movie->error;
                }
            } else {
                echo "Error moving the uploaded file.";
            }
        } else {
            echo "Error uploading the image.";
        }
    } elseif ($_POST['action'] === 'add_book') {
        $title = sanitize_input($_POST['title']);
        $author = sanitize_input($_POST['author']);
        $description = sanitize_input($_POST['description']);
        $category_id = (int)$_POST['category_id']; 
        $user_id = $_SESSION['user_id'];
        $visibility = 1;
        $like = 0;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $uploadFileDir = 'uploads/books/';
            $dest_path = $uploadFileDir . basename($fileName);

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $sql_add_book = "INSERT INTO books (title, author, description, Category_id, user_id, visibility, `like` ,image_link) VALUES (?, ?, ?,?,?, ?, ?, ?)";
                $stmt_add_book = $conn->prepare($sql_add_book);
                $stmt_add_book->bind_param('sssiiiis', $title, $author, $description, $category_id, $user_id,$visibility,$like, $dest_path);

                if (!$stmt_add_book->execute()) {
                    echo "Error adding book: " . $stmt_add_book->error;
                }
            } else {
                echo "Error moving the uploaded file.";
            }
        } else {
            echo "Error uploading the image.";
        }
    } elseif ($_POST['action'] === 'add_goal') {
        $goal_text = sanitize_input($_POST['goal_text']);
        $user_id = $_SESSION['user_id'];
        $visibility=1;
        $sql_add_goal = "INSERT INTO goals (text, user_id,visibility) VALUES (?, ?,?)";
        $stmt_add_goal = $conn->prepare($sql_add_goal);
        $stmt_add_goal->bind_param('sii', $goal_text, $user_id,$visibility);

        if (!$stmt_add_goal->execute()) {
            echo "Error adding goal: " . $stmt_add_goal->error;
        }
    } elseif ($_POST['action'] === 'add_recipe') {
        $title = sanitize_input($_POST['title']);
        $ingredients = sanitize_input($_POST['ingredients']);
        $instructions = sanitize_input($_POST['instructions']);
        $user_id = $_SESSION['user_id'];
        $visibility = 1;
        $like = 0;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $uploadFileDir = 'uploads/recipes/';
            $dest_path = $uploadFileDir . basename($fileName);

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $sql_add_recipe = "INSERT INTO recipes (title, Ingredients, Instructions, user_id,visibility,`like`, image_link) VALUES (?, ?, ?, ?,?,?, ?)";
                $stmt_add_recipe = $conn->prepare($sql_add_recipe);
                $stmt_add_recipe->bind_param('sssiiis', $title, $ingredients, $instructions, $user_id, $visibility,$like,$dest_path);

                if (!$stmt_add_recipe->execute()) {
                    echo "Error adding recipe: " . $stmt_add_recipe->error;
                }
            } else {
                echo "Error moving the uploaded file.";
            }
        } else {
            echo "Error uploading the image.";
        }
    }else if ($_POST['action'] === 'update_user') {
        $firstName = sanitize_input($_POST['firstName']);
        $lastName = sanitize_input($_POST['lastName']);
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $number = sanitize_input($_POST['number']); // New field
        $role = sanitize_input($_POST['role']); // New field
        $user_id = $_SESSION['user_id'];
    
        $dest_path = null; // Initialize to handle optional image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
            $uploadFileDir = 'uploads/users/';
            $dest_path = $uploadFileDir . $fileName;
    
            if (!move_uploaded_file($fileTmpPath, $dest_path)) {
                echo json_encode(['status' => 'error', 'message' => 'Error moving the uploaded file.']);
                exit;
            }
        }
    
        // SQL to update fields and conditionally update Image_Link
        $sql_update_user = "UPDATE users 
                            SET First_Name = ?, Last_Name = ?, username = ?, email = ?, Number = ?, Role = ?, 
                            Image_Link = IF(? IS NOT NULL, ?, Image_Link) 
                            WHERE ID_USER = ?";
        $stmt_update_user = $conn->prepare($sql_update_user);
        $stmt_update_user->bind_param(
            'ssssisssi',
            $firstName, $lastName, $username, $email, $number, $role, 
            $dest_path, $dest_path, $user_id
        );
    
        if ($stmt_update_user->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'User updated successfully', 'newImageUrl' => $dest_path]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error updating user: ' . $stmt_update_user->error]);
        }
        exit;
    }elseif ($_POST['action'] === 'change_password') {
        $user_id = $_SESSION['user_id'] ?? null;
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $repeat_new_password = $_POST['repeat_new_password'] ?? '';
    
        if ($new_password !== $repeat_new_password) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
            exit;
        }
    
        $sql_change = "SELECT hashed_password FROM users WHERE ID_USER = ?";
        $stmt_change=$conn->prepare($sql_change);
        $stmt_change->bind_param('i', $user_id);
        $stmt_change->execute();
        $user = $stmt_change->get_result()->fetch_assoc();
    
        if (!$user || !password_verify($old_password, $user['hashed_password'])) {
            echo json_encode(['success' => false, 'message' => 'Old password is incorrect.']);
            exit;
        }
    
        $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
    
        $stmt = $conn->prepare('UPDATE users SET hashed_password = ? WHERE ID_USER = ?');
        $stmt->execute([$hashed_new_password, $user_id]);
    
        echo json_encode(['success' => true]);
        exit;
    }
    
    
    
    
    
    
    
    
    
}

// Fetch user information
$sql_user = "SELECT * FROM users WHERE ID_USER = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param('i', $_SESSION['user_id']);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();

// Fetch list of users for admin
$sql_users_list = "SELECT * FROM users WHERE Role=? OR Role=?";
$stmt_users_list = $conn->prepare($sql_users_list);
$role_user = "user";
$role_admin = "admin";
$stmt_users_list->bind_param('ss', $role_user, $role_admin);
$stmt_users_list->execute();
$users_list = $stmt_users_list->get_result();

// Fetch categories for dropdown
$sql_categories = "SELECT * FROM categories";
$stmt_categories = $conn->prepare($sql_categories);
$stmt_categories->execute();
$categories = $stmt_categories->get_result();

$sql_categories_books = "SELECT * FROM categories";
$stmt_categories_books = $conn->prepare($sql_categories_books);
$stmt_categories_books->execute();
$categories_books = $stmt_categories_books->get_result();

$userData = null;
if (isset($_SESSION['user_id'])) {
    $user_id_for_header = $_SESSION['user_id'];
    $query_for_header = "SELECT username, Image_Link FROM users WHERE ID_USER = ?";
    $stmt_for_header = $conn->prepare($query_for_header);
    $stmt_for_header->bind_param("i", $user_id_for_header);
    $stmt_for_header->execute();
    $result_for_header = $stmt_for_header->get_result();
    $userData = $result_for_header->fetch_assoc();
    $stmt_for_header->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <?php include 'includes/fontawesome.php'; ?> 
    <link rel="stylesheet" href="assets/styles/header_footer.css">
    <link rel="stylesheet" href="assets/styles/user.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
     $(document).ready(function () {
            $('#saveButton').hide();

            $('#editButton').click(function () {
                $('#updateForm').find(".hidden").removeClass("hidden");
                $('#updateForm').find('.image-wrapper').addClass('editable');
                $('#editButton').hide();
                $('#saveButton').show();

                $('#firstNameDisplay').hide();
                $('#lastNameDisplay').hide();
                $('#usernameDisplay').hide();
                $('#emailDisplay').hide();
                $('#numberDisplay').hide();
                $('#roleDisplay').hide();
                $('#firstName').show();
                $('#lastName').show();
                $('#username').show();
                $('#email').show();
                $('#number').show();
                $('#role').show();
            });

            $('.image-wrapper').click(function () {
                $('#userImageInput').trigger('click');
            });

            $('#userImageInput').change(function (event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        $('#userImageDisplay').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(file);
                }
            });

            $('#saveButton').click(function () {
                const email = $('#email').val();
                const number = $('#number').val();

                // Perform client-side validation for email and number
                if (!isValidEmail(email)) {
                    alert("Please enter a valid email address.");
                    return; // Prevent form submission if validation fails
                }

                if (!isValidNumber(number)) {
                    alert("Please enter a valid number.");
                    return; // Prevent form submission if validation fails
                }

                // Proceed with the form submission if all validations pass
                const formData = new FormData($('#updateForm')[0]);

                $.ajax({
                    url: 'user.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            // Update display with new values
                            $("#firstNameDisplay").text($('#firstName').val());
                            $("#lastNameDisplay").text($('#lastName').val());
                            $("#usernameDisplay").text($('#username').val());
                            $("#emailDisplay").text($('#email').val());
                            $("#numberDisplay").text($('#number').val());
                            $("#roleDisplay").text($('#role').val());

                            if (data.newImageUrl) {
                                $("#userImageDisplay").attr('src', data.newImageUrl);
                            }

                            // Hide input fields, show display fields
                            $('#updateForm').find('.hidden').addClass('hidden');
                            $('#updateForm').find('.image-wrapper').removeClass('editable');
                            $('#editButton').show();
                            $('#saveButton').hide();
                            $('#firstNameDisplay').show();
                            $('#lastNameDisplay').show();
                            $('#usernameDisplay').show();
                            $('#emailDisplay').show();
                            $('#numberDisplay').show();
                            $('#roleDisplay').show();
                            $('#firstName').hide();
                            $('#lastName').hide();
                            $('#username').hide();
                            $('#email').hide();
                            $('#number').hide();
                            $('#role').hide();
                        } else {
                            alert(data.message);
                        }
                    },
                    error: function () {
                        alert("Update failed.");
                    }
                });
            });

            // Email Validation function
            function isValidEmail(email) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailPattern.test(email);
            }

            // Number Validation function
            function isValidNumber(number) {
                const numberPattern = /^\d+$/;
                return numberPattern.test(number);
            }

            // When the user types in the 'new_password' field
            $('#new_password').on('input', function() {
                const password = $(this).val(); // Get the value of the password input
                const strengthMeter = $('#password-strength');
                const strengthText = $('#strength-text');

                // Define conditions to check
                let conditionsMet = 0;

                // Condition 1: At least 8 characters
                if (password.length >= 8) {
                    conditionsMet++;
                }

                // Condition 2: Contains at least one lowercase letter
                if (/[a-z]/.test(password)) {
                    conditionsMet++;
                }

                // Condition 3: Contains at least one uppercase letter
                if (/[A-Z]/.test(password)) {
                    conditionsMet++;
                }

                // Condition 4: Contains at least one digit
                if (/\d/.test(password)) {
                    conditionsMet++;
                }

                // Condition 5: Contains at least one special character
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                    conditionsMet++;
                }

                // Evaluate password strength based on number of conditions met
                if (conditionsMet === 5) {
                    // Strong password
                    strengthMeter.removeClass().addClass('strong');  // Add class for strong password style
                    strengthText.text('Strong password');
                    strengthText.css('color', 'green');
                } else if (conditionsMet >= 3) {
                    // Moderate password
                    strengthMeter.removeClass().addClass('moderate'); // Add class for moderate password style
                    strengthText.text('Moderate password');
                    strengthText.css('color', 'yellow');
                } else if (conditionsMet <= 2) {
                    // Weak password
                    strengthMeter.removeClass().addClass('weak');    // Add class for weak password style
                    strengthText.text('Weak password.Password needs at least 8 characters, one lowercase letter, one uppercase letter, one number, and one special character.');
                    strengthText.css('color', 'red');
                } 
            });

            // Password change form submission handler
            $('#submit-button').click(function() {
                const oldPassword = $('#old_password').val();
                const newPassword = $('#new_password').val();
                const repeatNewPassword = $('#repeat_new_password').val();

                // Check if both passwords match
                if (newPassword !== repeatNewPassword) {
                    alert('New passwords do not match.');
                    return;
                }

                // Regular expression to check for strong password criteria (for server-side validation)
                const passwordStrengthPattern = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[A-Z])(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/;

                // If the password doesn't match the strong pattern, show an alert
                if (!passwordStrengthPattern.test(newPassword)) {
                    alert('Password must be at least 8 characters long, include one uppercase letter, one number, and one special character.');
                    return;
                }

                // Proceed with form submission using AJAX
                $.ajax({
                    url: 'user.php',
                    type: 'POST',
                    data: $('#password-change-form').serialize(),
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            alert('Password successfully updated.');
                            window.location.href = 'user.php';
                        } else {
                            alert(data.message || 'An error occurred.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        alert('An error occurred. Please try again later.');
                    }
                });
            });








        });


    </script>
</head>
<body>
<header>
        <div class="header-container">
            <a href="./index.php" class="logo">
                <img src="./assets/images/logo.png" class="logo_img">
                <h1 class="logo_text">MYVAULT</h1>
            </a>

            <nav class="navbar center-nav">
                <a class="links_navigation underline_animation" href="movies.php">Movies</a>
                <a class="links_navigation underline_animation" href="books.php">Books</a>
                <a class="links_navigation underline_animation" href="recipes.php">Recipes</a>
                <a class="links_navigation underline_animation" href="goals.php">Goals</a>
            </nav>

            <nav class="navbar right-nav">
                <?php if ($userData): ?>
                    <a class="links_navigation logout link_log " href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
                    <a class="links_navigation user_div link_log" href="user.php"><?php echo htmlspecialchars($userData['username']); ?>
                        <?php if (!is_null($userData['Image_Link'])): ?>
                            <img src="<?php echo htmlspecialchars($userData['Image_Link']); ?>" alt="Profile Picture" class="profile-picture user_margin">
                        <?php else: ?>
                            <i class="fa-regular fa-circle-user profile-icon user_margin icon_size"></i>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="login links_navigation link_log">Login <i class="fa-solid fa-arrow-right-to-bracket "></i></a>
                <?php endif; ?>
                <a class="links_navigation link_log like_log" href="favourite.php"><i class="fa-regular fa-heart icon_size"></i><p class="recent_likes_count"><?php echo count($_SESSION['recent_likes'])?></p></a>

                <a class="links_navigation link_log" href="index.php"><i class="fas fa-home icon_size"></i></a>
            </nav>
            <a class="links_navigation favourite_small_screens like_log" href="favourite.php"><i class="fa-regular fa-heart icon_margin favourite_icon"></i><p class="recent_likes_count"><?php echo count($_SESSION['recent_likes'])?></p></a>
                    
        </div>

        <button class="hamburger" id="hamburger">
            <i class="fas fa-bars"></i>
        </button>
        <nav class="sidebar" id="sidebar">
            <button class="close-btn" id="close-btn">
                <i class="fas fa-times"></i>
            </button>
            <div class="div_for_sidebar_links">
                <div class="div_for_sidebar_links_top">
                    <a class="links_navigation first_link_sidebar" href="index.php"><i class="fas fa-home icon_margin"></i>Home</a>
                    <a class="links_navigation" href="movies.php"><i class="fa-solid fa-film icon_margin"></i>Movies</a>
                    <a class="links_navigation" href="books.php"><i class="fa-solid fa-book icon_margin"></i>Books</a>
                    <a class="links_navigation" href="recipes.php"><i class="fa-solid fa-utensils icon_margin"></i>Recipes</a>
                    <a class="links_navigation" href="goals.php"><i class="fa-solid fa-list-check icon_margin"></i>Goals</a>
                    <a class="links_navigation" href="user.php"><i class="fa-solid fa-user-pen icon_margin"></i>Your account</a>  
                </div>
                <div class="user_status">
                    <?php if ($userData): ?>
                        <a class="links_navigation" href="user.php">
                            <?php if (!is_null($userData['Image_Link'])): ?>
                                <img src="<?php echo htmlspecialchars($userData['Image_Link']); ?>" alt="Profile Picture" class="profile-picture">
                            <?php else: ?>
                                <i class="fa-regular fa-circle-user profile-icon icon_margin"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($userData['username']); ?>
                        </a>
                        <a class="links_navigation" href="logout.php" style="padding-left:10px"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="login links_navigation">Login<i class="fa-solid fa-arrow-right-to-bracket"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>
    <div class="user-profile">
        <form id="updateForm" method="POST" enctype="multipart/form-data">
            <div class="buttons_div">
                <div class="user_Header">User info</div>
                <button type="button" id="editButton">Edit</button>
                <button type="button" id="saveButton" class="hidden">Save</button>
            </div>
            
            <div class="container_for_all">
                <input type="hidden" id="action" name="action" value="update_user">
                <!-- Profile Image -->
                <div class="image-container">
                    <div class="image-wrapper">
                        <img id="userImageDisplay" src="<?php echo htmlspecialchars($user['Image_Link']); ?>" alt="Profile Image">
                        <label class="overlay" id="overlay" for="userImageInput">
                            <i class="fa fa-camera camera-icon" aria-hidden="true"></i>
                            <!-- Hidden file input to upload image -->
                            <input type="file" id="userImageInput" name="image" style="display:none;" accept="image/*">
                        </label>
                    </div>
                </div>
                <div class="div_inf0_user">
                    <!-- First Name -->
                     <div class="row_info_u">
                        <div class="row_info_user">
                            <label for="firstName">First Name:</label>
                            <span id="firstNameDisplay"><?php echo htmlspecialchars($user['First_Name']); ?></span>
                            <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($user['First_Name']); ?>" class="hidden">
                        </div>

                        <!-- Last Name -->
                        <div class="row_info_user">
                            <label for="lastName">Last Name:</label>
                            <span id="lastNameDisplay"><?php echo htmlspecialchars($user['Last_Name']); ?></span>
                            <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($user['Last_Name']); ?>" class="hidden">
                        </div>

                     </div>
                    
                    <!-- Username -->
                    <div class="row_info_user">
                        <label for="username">Username:</label>
                        <span id="usernameDisplay"><?php echo htmlspecialchars($user['username']); ?></span>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="hidden">
                    </div>

                    <!-- Email -->
                    <div class="row_info_user">
                        <label for="email">Email:</label>
                        <span id="emailDisplay"><?php echo htmlspecialchars($user['email']); ?></span>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="hidden">
                    </div>

                    <!-- Number -->
                    <div class="row_info_user">
                        <label for="number">Number:</label>
                        <span id="numberDisplay"><?php echo htmlspecialchars($user['Number']); ?></span>
                        <input type="text" id="number" name="number" value="<?php echo htmlspecialchars($user['Number']); ?>" class="hidden">
                    </div>

                    <!-- Role -->
                    <div class="row_info_user">
                        <label for="role">Role:</label>
                        <span id="roleDisplay"><?php echo htmlspecialchars($user['Role']); ?></span>

                        <!-- The role select field will be disabled if the role is 'user' -->
                        <select id="role" name="role" class="hidden" <?php echo ($user['Role'] == 'user') ? 'disabled' : ''; ?>>
                            <option value="admin" <?php echo ($user['Role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="user" <?php echo ($user['Role'] == 'user') ? 'selected' : ''; ?>>User</option>
                        </select>

                        <!-- Optionally display a message that informs users with a 'user' role that they cannot change their role -->
                        <?php if ($user['Role'] == 'user'): ?>
                            <p class="info-message">Your role cannot be changed as you are a "User".</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <form id="password-change-form" method="post">
    <input type="hidden" name="action" value="change_password">

    <label for="old_password">Old Password:</label><br>
    <input type="password" id="old_password" name="old_password" required><br><br>

    <label for="new_password">New Password:</label><br>
    <input type="password" id="new_password" name="new_password" required><br><br>

    <!-- <div id="password-strength" style="height: 10px; width: 100%; background-color: #ddd;"></div><br> -->
    <p id="strength-text"></p> <!-- Show 'Weak password' immediately -->

    <label for="repeat_new_password">Repeat New Password:</label><br>
    <input type="password" id="repeat_new_password" name="repeat_new_password" required><br><br>

    <button type="button" id="submit-button">Change Password</button>
</form>










   
    <?php if ($user['Role'] === 'admin'): ?>
        <h2>Admin Features</h2>
        <h2>Add New User</h2>
        <form action="user.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_user_with_role">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            <label for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" required>
            <label for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" required>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
            <label for="number">Number</label>
            <input type="text" id="number" name="number">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required> 
            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="">Select a role</option>
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>
            <label for="image">Image</label>
            <input type="file" id="image" name="image" accept="image/*" required>
            <button type="submit">Add User</button>
        </form>
        <h2>Add New Movie</h2>
        <form action="user.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_movie">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required>
            <label for="description">Description</label>
            <textarea id="description" name="description" required></textarea>
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <option value="">Select a category</option>
                <?php while ($category = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $category['id_category']; ?>"><?php echo htmlspecialchars($category['Name']); ?></option>
                <?php endwhile; ?>
            </select>
            <label for="image">Image</label>
            <input type="file" id="image" name="image" accept="image/*" required>
            <button type="submit">Add Movie</button>
        </form>
        

        <h3>Add New Book</h3>
        <form action="user.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_book">
            <label for="title">Title:</label>
            <input type="text" name="title" required><br>

            <label for="author">Author:</label>
            <input type="text" name="author" required><br>

            <label for="description">Description:</label>
            <textarea name="description" required></textarea><br>

            <label for="category_id">Category:</label>
            <select id="category_id" name="category_id" required>
                <option value="">Select a category</option>
                <?php while ($category = $categories_books->fetch_assoc()): ?>
                    <option value="<?php echo $category['id_category']; ?>"><?php echo htmlspecialchars($category['Name']); ?></option>
                <?php endwhile; ?>
            </select><br>

            <label for="image">Book Image:</label>
            <input type="file" name="image" accept="image/*"><br>

            <input type="submit" value="Add Book">
        </form>

        <h3>Add New Goal</h3>
        <form action="user.php" method="post">
            <input type="hidden" name="action" value="add_goal">
            <label for="goal_text">Goal:</label>
            <textarea name="goal_text" required></textarea><br>

            <input type="submit" value="Add Goal">
        </form>

        <h3>Add New Recipe</h3>
        <form action="user.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_recipe">
            <label for="title">Title:</label>
            <input type="text" name="title" required><br>

            <label for="ingredients">Ingredients:</label>
            <textarea name="ingredients" required></textarea><br>

            <label for="instructions">Instructions:</label>
            <textarea name="instructions" required></textarea><br>

            <label for="image">Recipe Image:</label>
            <input type="file" name="image" accept="image/*"><br>

            <input type="submit" value="Add Recipe">
        </form>
        <!-- Users List -->
        <h2>Users List:</h2>
        <ul>
            <?php while ($user_list = $users_list->fetch_assoc()): ?>
                <?php if($user_list['ID_USER'] != $_SESSION['user_id']): ?>
                    <li>
                        <?php echo htmlspecialchars($user_list['username']); ?> - 
                        <?php echo htmlspecialchars($user_list['email']); ?> - 
                        <?php echo htmlspecialchars($user_list['Role']); ?>
                        
                        <!-- Form to Change User Role -->
                        <form action="user.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="change_user_role">
                            <input type="hidden" name="user_id" value="<?php echo $user_list['ID_USER']; ?>">
                            <select name="new_role" required>
                                <option value="">Change Role</option>
                                <option value="admin" <?php if ($user_list['Role'] === 'admin') echo 'selected'; ?>>Admin</option>
                                <option value="user" <?php if ($user_list['Role'] === 'user') echo 'selected'; ?>>User</option>
                            </select>
                            <button type="submit">Change</button>
                        </form>
                        
                        <!-- User Image -->
                        <?php if (!empty($user_list['Image_Link'])): ?>
                            <img src="<?php echo htmlspecialchars($user_list['Image_Link']); ?>" alt="User Image" style="width:50px;height:auto;">
                        <?php else: ?>
                            <p>No image found</p>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
            <?php endwhile; ?>
        </ul>

    <?php endif; ?>

    <footer>
        <div class="footer-container">
            <div class="footer-left">
                <p>&copy; 2024 MyVault. All rights reserved.</p>
            </div>
            <div class="footer-center">
                <a href="index.php" class="footer-link">Privacy Policy</a>
                <a href="index.php" class="footer-link">Terms of Service</a>
                <a href="index.php" class="footer-link">Contact Us</a>
            </div>
            <div class="footer-right">
                <a href="https://www.facebook.com/profile.php?id=100086034558322&locale=pl_PL" target="_blank" class="social-icon"><i class="fab fa-facebook"></i></a>
                <a href="https://www.linkedin.com/in/valeriia-zlydar-0089a4304/" target="_blank" class="social-icon"><i class="fab fa-linkedin"></i></a>
                <a href="https://www.instagram.com/vel.zly_/" target="_blank" class="social-icon"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

<script src="assets/js/header_footer.js"></script>
<script scr="assets/js/user.js"></script>
</body>
</html>
