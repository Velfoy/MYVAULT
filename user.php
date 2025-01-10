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

        if (isset($_FILES['movie']) && $_FILES['movie']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['movie']['tmp_name'];
            $fileName = $_FILES['movie']['name'];
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

        if (isset($_FILES['book']) && $_FILES['book']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['book']['tmp_name'];
            $fileName = $_FILES['book']['name'];
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

        if (isset($_FILES['recipe']) && $_FILES['recipe']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['recipe']['tmp_name'];
            $fileName = $_FILES['recipe']['name'];
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
        $number = sanitize_input($_POST['number']); 
        $role = sanitize_input($_POST['role']); 
        $user_id = $_SESSION['user_id'];
    
        $dest_path = null; 
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
    }elseif ($_POST['action'] === 'delete_user') {
        $user_id = intval($_POST['user_id']);
        $conn->begin_transaction();
    
        try {
            $stmt1 = $conn->prepare("DELETE FROM reviews WHERE user_id = ?");
            $stmt1->bind_param("i", $user_id);
            $stmt1->execute();
            $stmt2 = $conn->prepare("DELETE FROM movies WHERE user_id = ?");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
            $stmt3 = $conn->prepare("DELETE FROM books WHERE user_id = ?");
            $stmt3->bind_param("i", $user_id);
            $stmt3->execute();

            $stmt4 = $conn->prepare("DELETE FROM goals WHERE user_id = ?");
            $stmt4->bind_param("i", $user_id);
            $stmt4->execute();

            $stmt5 = $conn->prepare("DELETE FROM likes WHERE user_id = ?");
            $stmt5->bind_param("i", $user_id);
            $stmt5->execute();

            $stmt6 = $conn->prepare("DELETE FROM users WHERE ID_USER = ?");
            $stmt6->bind_param("i", $user_id);
            $stmt6->execute();

            $conn->commit();
            header('Location: user.php');
            exit();
    
        } catch (Exception $e) {
            $conn->rollback();
            echo "Error deleting user: " . $e->getMessage();
        }
    }
    
    
    
    
    
    
    
    
    
    
    
}

$sql_user = "SELECT * FROM users WHERE ID_USER = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param('i', $_SESSION['user_id']);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();

$sql_users_list = "SELECT * FROM users WHERE Role=? OR Role=?";
$stmt_users_list = $conn->prepare($sql_users_list);
$role_user = "user";
$role_admin = "admin";
$stmt_users_list->bind_param('ss', $role_user, $role_admin);
$stmt_users_list->execute();
$users_list = $stmt_users_list->get_result();

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
                if ($('#roleDisplay').text().toLowerCase() === 'admin') {
                    $('#roleDisplay').hide();
                    $('#role').show();
                }

               

                $('#firstName').show();
                $('#lastName').show();
                $('#username').show();
                $('#email').show();
                $('#number').show();
                
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

                if (!isValidEmail(email)) {
                    alert("Please enter a valid email address.");
                    return; 
                }

                if (!isValidNumber(number)) {
                    alert("Please enter a valid number.");
                    return; 
                }
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
                            $("#firstNameDisplay").text($('#firstName').val());
                            $("#lastNameDisplay").text($('#lastName').val());
                            $("#usernameDisplay").text($('#username').val());
                            $("#emailDisplay").text($('#email').val());
                            $("#numberDisplay").text($('#number').val());
                            $('#roleDisplay').text($('#role').val());

                            if (data.newImageUrl) {
                                $("#userImageDisplay").attr('src', data.newImageUrl);
                            }

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
            function isValidEmail(email) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailPattern.test(email);
            }

            function isValidNumber(number) {
                const numberPattern = /^[0-9]+$/;
                return numberPattern.test(number);
            }

            $('#new_password').on('input', function() {
                const password = $(this).val(); 
                const strengthMeter = $('#password-strength');
                const strengthText = $('#strength-text');
                let conditionsMet = 0;
                if (password.length >= 8) {
                    conditionsMet++;
                }
                if (/[a-z]/.test(password)) {
                    conditionsMet++;
                }
                if (/[A-Z]/.test(password)) {
                    conditionsMet++;
                }
                if (/\d/.test(password)) {
                    conditionsMet++;
                }
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                    conditionsMet++;
                }
                if (conditionsMet === 5) {
                    strengthMeter.removeClass().addClass('strong');  
                    strengthText.text('Strong password');
                    strengthText.css('color', 'green');
                } else if (conditionsMet >= 3) {
                    strengthMeter.removeClass().addClass('moderate'); 
                    strengthText.text('Moderate password.Password needs at least 8 characters, one lowercase letter, one uppercase letter, one number, and one special character.');
                    strengthText.css('color', ' rgb(255, 187, 0)');
                } else if (conditionsMet <= 2) {
                    strengthMeter.removeClass().addClass('weak'); 
                    strengthText.text('Weak password.Password needs at least 8 characters, one lowercase letter, one uppercase letter, one number, and one special character.');
                    strengthText.css('color', 'red');
                } 
            });

            $('#submit-button').click(function() {
                const oldPassword = $('#old_password').val();
                const newPassword = $('#new_password').val();
                const repeatNewPassword = $('#repeat_new_password').val();
                if (newPassword !== repeatNewPassword) {
                    alert('New passwords do not match.');
                    return;
                }

                const passwordStrengthPattern = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[A-Z])(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/;
                if (!passwordStrengthPattern.test(newPassword)) {
                    alert('Password must be at least 8 characters long, include one uppercase letter, one number, and one special character.');
                    return;
                }
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

            $('#passwordChangeButton').on('click', function() {
                $('#password-popup').css('display', 'flex');  
            });

            $('#close-popup').on('click', function() {
                $('#password-popup').css('display', 'none'); 
            });


            $('.toggle-eye').on('click', function () {
                const input = $($(this).data('target'));
                const icon = $(this).find('i'); 

                if (input.attr('type') === "password") {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                }
            });

            const $dragAndDropArea = $(".dragAndDropArea");
            const $fileInput = $("#image");
            const $fileNameDisplay = $(".fileNameDisplay");
            $dragAndDropArea.on("click", function () {
                $fileInput.trigger("click");
            });
            $fileInput.on("change", function () {
                showPreview(this.files);
            });
            $dragAndDropArea.on("dragover", function (e) {
                e.preventDefault();
                $dragAndDropArea.css("background-color", "#e0e0e0");
            });

            $dragAndDropArea.on("dragleave", function () {
                $dragAndDropArea.css("background-color", "#f9f9f9");
            });

            $dragAndDropArea.on("drop", function (e) {
                e.preventDefault();
                $dragAndDropArea.css("background-color", "#f9f9f9");
                const files = e.originalEvent.dataTransfer.files;
                $fileInput[0].files = files; 
                showPreview(files);
            });

            function showPreview(files) {
                $dragAndDropArea.empty(); 
                $fileNameDisplay.empty(); 
                if (files.length) {
                    const file = files[0];
                    $fileNameDisplay.text(`Selected File: ${file.name}`);
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const $img = $("<img>").attr("src", e.target.result);
                        $dragAndDropArea.append($img);
                    };
                    reader.readAsDataURL(file);
                } else {
                    $dragAndDropArea.html("<p>No file chosen</p>");
                    $fileNameDisplay.text("");
                }
            }

            const $dragAndDropAreaMovie = $("#drop-area");
            const $fileInputMovie = $("#movie");
            const $fileNameDisplayMovie = $(".drop-areaName");
            $dragAndDropAreaMovie.on("click", function () {
                $fileInputMovie.trigger("click");
            });
            $fileInputMovie.on("change", function () {
                showPreviewMovie(this.files);
            });
            $dragAndDropAreaMovie.on("dragover", function (e) {
                e.preventDefault();
                $dragAndDropAreaMovie.css("background-color", "#e0e0e0");
            });

            $dragAndDropAreaMovie.on("dragleave", function () {
                $dragAndDropAreaMovie.css("background-color", "#f9f9f9");
            });

            $dragAndDropAreaMovie.on("drop", function (e) {
                e.preventDefault();
                $dragAndDropAreaMovie.css("background-color", "#f9f9f9");
                const files = e.originalEvent.dataTransfer.files;
                $fileInputMovie[0].files = files; 
                showPreviewMovie(files);
            });

            function showPreviewMovie(files) {
                $dragAndDropAreaMovie.empty(); 
                $fileNameDisplayMovie.empty(); 
                if (files.length) {
                    const file = files[0];
                    $fileNameDisplayMovie.text(`Selected File: ${file.name}`);
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const $img = $("<img>").attr("src", e.target.result);
                        $dragAndDropAreaMovie.append($img);
                    };
                    reader.readAsDataURL(file);
                } else {
                    $dragAndDropAreaMovie.html("<p>No file chosen</p>");
                    $fileNameDisplayMovie.text("");
                }
            }

            const $dragAndDropAreaBook = $("#drop-area_book");
            const $fileInputBook = $("#book");
            const $fileNameDisplayBook = $(".drop-areaName_book");
            $dragAndDropAreaBook.on("click", function () {
                $fileInputBook.trigger("click");
            });
            $fileInputBook.on("change", function () {
                showPreviewBook(this.files);
            });
            $dragAndDropAreaBook.on("dragover", function (e) {
                e.preventDefault();
                $dragAndDropAreaBook.css("background-color", "#e0e0e0");
            });

            $dragAndDropAreaBook.on("dragleave", function () {
                $dragAndDropAreaBook.css("background-color", "#f9f9f9");
            });

            $dragAndDropAreaBook.on("drop", function (e) {
                e.preventDefault();
                $dragAndDropAreaBook.css("background-color", "#f9f9f9");
                const files = e.originalEvent.dataTransfer.files;
                $fileInputBook[0].files = files; 
                showPreviewBook(files);
            });

            function showPreviewBook(files) {
                $dragAndDropAreaBook.empty(); 
                $fileNameDisplayBook.empty(); 
                if (files.length) {
                    const file = files[0];
                    $fileNameDisplayBook.text(`Selected File: ${file.name}`);
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const $img = $("<img>").attr("src", e.target.result);
                        $dragAndDropAreaBook.append($img);
                    };
                    reader.readAsDataURL(file);
                } else {
                    $dragAndDropAreaBook.html("<p>No file chosen</p>");
                    $fileNameDisplayBook.text("");
                }
            }


            const $dragAndDropAreaRecipe = $("#drop-area_recipe");
            const $fileInputRecipe = $("#recipe");
            const $fileNameDisplayRecipe = $(".drop-areaName_recipe");
            $dragAndDropAreaRecipe.on("click", function () {
                $fileInputRecipe.trigger("click");
            });
            $fileInputRecipe.on("change", function () {
                showPreviewRecipe(this.files);
            });
            $dragAndDropAreaRecipe.on("dragover", function (e) {
                e.preventDefault();
                $dragAndDropAreaRecipe.css("background-color", "#e0e0e0");
            });

            $dragAndDropAreaRecipe.on("dragleave", function () {
                $dragAndDropAreaRecipe.css("background-color", "#f9f9f9");
            });

            $dragAndDropAreaRecipe.on("drop", function (e) {
                e.preventDefault();
                $dragAndDropAreaRecipe.css("background-color", "#f9f9f9");
                const files = e.originalEvent.dataTransfer.files;
                $fileInputRecipe[0].files = files; 
                showPreviewRecipe(files);
            });

            function showPreviewRecipe(files) {
                $dragAndDropAreaRecipe.empty(); 
                $fileNameDisplayRecipe.empty(); 
                if (files.length) {
                    const file = files[0];
                    $fileNameDisplayRecipe.text(`Selected File: ${file.name}`);
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const $img = $("<img>").attr("src", e.target.result);
                        $dragAndDropAreaRecipe.append($img);
                    };
                    reader.readAsDataURL(file);
                } else {
                    $dragAndDropAreaRecipe.html("<p>No file chosen</p>");
                    $fileNameDisplayRecipe.text("");
                }
            }
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
    <div class="div_divs">
        <div class="user-profile">
            <form id="updateForm" method="POST" enctype="multipart/form-data">
                <div class="container_for_all">
                    <input type="hidden" id="action" name="action" value="update_user">

                    <div class="col_us">
                        <div class="user_Header">User Information</div>

                        <div class="image-container">
                            <div class="image-wrapper">
                                <?php if (!empty($user['Image_Link'])): ?>
                                    <img id="userImageDisplay" src="<?php echo htmlspecialchars($user['Image_Link']); ?>" alt="Profile Image">
                                <?php else: ?>
                                    <img id="userImageDisplay" src="assets/fakers/no-image.jpg" alt="Profile Image">
                                <?php endif; ?>

                                <label class="overlay" id="overlay" for="userImageInput">
                                    <i class="fa fa-camera camera-icon" aria-hidden="true"></i>
                                    <input type="file" id="userImageInput" name="image" style="display:none;" accept="image/*">
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="div_info_user " style="margin-top:20px;">
                        <div class="width_long row_info_user">
                            <label for="username">Username:</label>
                            <span id="usernameDisplay"><?php echo htmlspecialchars($user['username']); ?></span>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="hidden">
                        </div>

                         <div  class="col_respons" style="display:flex;flex-direction:row;">
                            <div class="row_info_user">
                                <label for="lastName">Last Name:</label>
                                <span id="lastNameDisplay"><?php echo htmlspecialchars($user['Last_Name']); ?></span>
                                <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($user['Last_Name']); ?>" class="hidden">
                            </div>

                            <div class="row_info_user">
                                <label for="firstName">First Name:</label>
                                <span id="firstNameDisplay"><?php echo htmlspecialchars($user['First_Name']); ?></span>
                                <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($user['First_Name']); ?>" class="hidden">
                            </div>
                         </div>
                         <div  class="col_respons" style="display:flex;flex-direction:row;">

                            <div class="row_info_user">
                                <label for="email">Email:</label>
                                <span id="emailDisplay"><?php echo htmlspecialchars($user['email']); ?></span>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="hidden">
                            </div>

                            <div class="row_info_user">
                                <label for="number">Phone Number:</label>
                                <span id="numberDisplay"><?php echo htmlspecialchars($user['Number']); ?></span>
                                <input type="text" id="number" name="number" value="<?php echo htmlspecialchars($user['Number']); ?>" class="hidden">
                            </div>
                         </div>
                        

                        <div class="width_long row_info_user">
                            <label for="role">Role:</label>
                            <span id="roleDisplay"><?php echo htmlspecialchars($user['Role']); ?></span>
                            <select id="role" name="role" class="hidden">
                                <option value="user" <?php echo $user['Role'] === 'user' ? 'selected' : ''; ?>>user</option>
                                <option value="admin" <?php echo $user['Role'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="buttons_div">
                    <button type="button" id="passwordChangeButton">Change password</button>
                    <button type="button" id="editButton">Edit</button>
                    <button type="button" id="saveButton" class="hidden">Save</button>
                </div>
            </form>
        </div>
    </div>
    <div id="password-popup" class="popup-container">
    <div class="popup-content">
        <!-- Popup Header with title and close icon -->
        <div class="popup-header">
            <h2>Password Change</h2>
            <button id="close-popup" class="close-btn1"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <form id="password-change-form" method="post">
            <input type="hidden" name="action" value="change_password">

            <label for="old_password">Old Password:</label>
            <div class="password-input-wrapper">
                <input type="password" id="old_password" name="old_password" required>
                <button type="button" class="toggle-eye" data-target="#old_password">
                    <i class="fa-solid fa-eye-slash"></i>
                </button>
            </div>

            <label for="new_password">New Password:</label>
            <div class="password-input-wrapper">
                <input type="password" id="new_password" name="new_password" class="new-password_label" required>
                <button type="button" class="toggle-eye" data-target="#new_password">
                    <i class="fa-solid fa-eye-slash"></i>
                </button>
            </div>

            <p id="strength-text"></p> <!-- Show 'Weak password' immediately -->

            <label for="repeat_new_password">Repeat New Password:</label>
            <div class="password-input-wrapper">
                <input type="password" id="repeat_new_password" name="repeat_new_password" required>
                <button type="button" class="toggle-eye" data-target="#repeat_new_password">
                    <i class="fa-solid fa-eye-slash"></i>
                </button>
            </div>

            <button type="button" id="submit-button">Change Password</button>
        </form>
    </div>
</div>



    <?php if ($user['Role'] === 'admin'): ?>
        <div class="admin-container">
            <h2 class="admin_header">Admin Features</h2>
            <div class="user-list-container">
                <h2>Users list</h2>
                <ul>
                    <?php while ($user_list = $users_list->fetch_assoc()): ?>
                        <?php if($user_list['ID_USER'] != $_SESSION['user_id']): ?>
                            <li class="user-item">
                                 <div class="info_list_user">
                                    <div class="user-image">
                                        <?php if (!empty($user_list['Image_Link'])): ?>
                                            <img src="<?php echo htmlspecialchars($user_list['Image_Link']); ?>" alt="User Image">
                                        <?php else: ?>
                                            <img src="assets/fakers/no-image.jpg" alt="User Image">
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-info_list">
                                        <strong><?php echo htmlspecialchars($user_list['username']); ?> </strong> - 
                                        <?php echo htmlspecialchars($user_list['email']); ?> - 
                                        <?php echo htmlspecialchars($user_list['Role']); ?>
                                    </div>

                                 </div>
                                <div class="buttons_user_list">
                                    <form action="user.php" method="POST" class="role-form">
                                        <input type="hidden" name="action" value="change_user_role">
                                        <input type="hidden" name="user_id" value="<?php echo $user_list['ID_USER']; ?>">
                                        <select name="new_role" required>
                                            <option value="">Change Role</option>
                                            <option value="admin" <?php if ($user_list['Role'] === 'admin') echo 'selected'; ?>>Admin</option>
                                            <option value="user" <?php if ($user_list['Role'] === 'user') echo 'selected'; ?>>User</option>
                                        </select>
                                        <button type="submit" class="change_role_user">Change role</button>
                                    </form>

                                    <form action="user.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user_list['ID_USER']; ?>">
                                        <button type="submit" class="delete-button"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                                
                            </li>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </ul>
            </div>
            <div class="form-container">
                <h2>Create new user</h2>
                <form action="user.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_user_with_role">
                    
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="first_name">First name:</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last name:</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="number">Number:</label>
                        <input type="text" id="number" name="number">
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select id="role" name="role" required>
                            <option value="">Select a role</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>

                    <div class="form-group col">
                        <label for="image">Image:</label>
                        <div class="dragAndDropArea" >
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p>Drag & Drop an image here to upload</p>
                        </div>
                        <input type="file" id="image" name="image" accept="image/*" style="display: none;">
                        <div class="fileNameDisplay" style="margin-top: 5px; font-size: 14px; color: #555;"></div>
                    </div>

                    <button type="submit" class="btn">Add User</button>
                </form>
            </div>
            <div class="form-container">
                <h2>Create new movie</h2>
                <form action="user.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_movie">

                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category:</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $category['id_category']; ?>"><?php echo htmlspecialchars($category['Name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group col">
                        <label for="movie">Image:</label>
                        <div id="drop-area" class="drop-area">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p>Drag & Drop an image here to upload</p>
                        </div>
                        <input type="file" id="movie" name="movie" accept="image/*" required style="display:none;">
                        <div class="drop-areaName" style="margin-top: 5px; font-size: 14px; color: #555;"></div>
                    </div>
                    

                    <button type="submit" class="btn">Add Movie</button>
                </form>
            </div>
            <div class="form-container">
                <h3>Create new book</h3>
                <form action="user.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_book">

                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="author">Author:</label>
                        <input type="text" name="author" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea name="description" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category:</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php while ($category = $categories_books->fetch_assoc()): ?>
                                <option value="<?php echo $category['id_category']; ?>"><?php echo htmlspecialchars($category['Name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group col">
                        <label for="book">Image:</label>
                        <div id="drop-area_book" class="drop-area">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p>Drag & Drop an image here to upload</p>
                        </div>
                        <input type="file" id="book" name="book" accept="image/*" required style="display:none;">
                        <div class="drop-areaName_book" style="margin-top: 5px; font-size: 14px; color: #555;"></div>
                    </div>

                    <button type="submit" class="btn">Add Book</button>
                </form>
            </div>
            <div class="form-container">
                <h3>Create new goal</h3>
                <form action="user.php" method="post">
                    <input type="hidden" name="action" value="add_goal">
                    <div class="form-group">
                        <label for="goal_text">Goal:</label>
                        <textarea name="goal_text" required></textarea>
                    </div>

                    <button type="submit" class="btn">Add Goal</button>
                </form>
            </div>
            <div class="form-container" style="margin-bottom: 60px;">
                <h3>Create new recipe</h3>
                <form action="user.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_recipe">

                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="ingredients">Ingredients:</label>
                        <textarea name="ingredients" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="instructions">Instructions:</label>
                        <textarea name="instructions" required></textarea>
                    </div>

                    <div class="form-group col">
                        <label for="recipe">Image:</label>
                        <div id="drop-area_recipe" class="drop-area">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p>Drag & Drop an image here to upload</p>
                        </div>
                        <input type="file" id="recipe" name="recipe" accept="image/*" required style="display:none;">
                        <div class="drop-areaName_recipe" style="margin-top: 5px; font-size: 14px; color: #555;"></div>
                    </div>

                    <button type="submit" class="btn">Add Recipe</button>
                </form>
            </div>
        </div>


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
</body>
</html>
