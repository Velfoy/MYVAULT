<?php
session_start();
include 'includes/functions.php';
include 'includes/db.php';
check_login();

// Handle adding a recipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_recipe') {
    $title = $_POST['title'];
    $ingredients = $_POST['ingredients'];
    $instructions = $_POST['instructions'];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $uploadFileDir = 'uploads/recipes/'; 
        $dest_path = $uploadFileDir . basename($fileName);

        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $sql = "INSERT INTO recipes (title, Ingredients, Instructions, user_id, visibility, image_link) VALUES (?, ?, ?, ?, 0, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssis', $title, $ingredients, $instructions, $_SESSION['user_id'], $dest_path);
            
            if (!$stmt->execute()) {
                echo "Error adding recipe: " . $stmt->error;  
            }
            $stmt->close(); 
        } else {
            echo "Error moving the uploaded file.";
        }
    } else {
        if (isset($_FILES['image'])) {
            echo "Error uploading the image: " . $_FILES['image']['error'];
        } 
    }
}

// Handle AJAX request to toggle 'like' status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])&& $_POST['action'] === 'like_add' ) {
    $recipe_id = $_POST['recipe_id'];
    $user_id = $_SESSION['user_id'];
    $item_type = "recipe";
    $stmt = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND item_id = ? AND item_type = ?");
    $stmt->bind_param('iis', $user_id, $recipe_id, $item_type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND item_id = ? AND item_type = ?");
        $delete_stmt->bind_param('iis', $user_id, $recipe_id, $item_type);
        
        if ($delete_stmt->execute()) {
            $_SESSION['recent_likes'] = array_filter($_SESSION['recent_likes'], function($like) use ($recipe_id, $item_type) {
                return !($like['item_id'] == $recipe_id && $like['item_type'] == $item_type);
            });
            $_SESSION['recent_likes'] = array_values($_SESSION['recent_likes']);

            // Prepare response
            $response = [
                'success' => true,
                'recent_likes_count' => count($_SESSION['recent_likes']),
                'liked' => false
            ];
        } else {
            $response = ['success' => false, 'error' => 'Could not delete item.'];
        }
        $delete_stmt->close();
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO likes (user_id, item_id, item_type) VALUES (?, ?, ?)");
        $insert_stmt->bind_param('iis', $user_id, $recipe_id, $item_type);
        
        if ($insert_stmt->execute()) {
            $_SESSION['recent_likes'][] = [
                'item_id' => $recipe_id,
                'item_type' => $item_type,
                'timestamp' => time()
            ];
            $response = [
                'success' => true,
                'recent_likes_count' => count($_SESSION['recent_likes']),
                'liked' => true
            ];
        } else {
            $response = ['success' => false, 'error' => 'Could not insert item.'];
        }
        $insert_stmt->close();
    }
    echo json_encode($response);
    $stmt->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $recipe_id = intval($_POST['recipes_id']); 
    $user_id = $_SESSION['user_id']; 

    // Check if the user is the owner or an admin
    $check_owner_sql = "SELECT user_id FROM recipes WHERE id_recipes = ?";
    $check_owner_stmt = $conn->prepare($check_owner_sql);
    $check_owner_stmt->bind_param('i', $recipe_id);
    $check_owner_stmt->execute();
    $check_owner_result = $check_owner_stmt->get_result();

    $check_role_sql = "SELECT Role FROM users WHERE ID_USER = ?";
    $check_role_stmt = $conn->prepare($check_role_sql);
    $check_role_stmt->bind_param('i', $user_id);
    $check_role_stmt->execute();
    $check_role_result = $check_role_stmt->get_result();

    if ($check_owner_result->num_rows > 0) {
        $owner_row = $check_owner_result->fetch_assoc();
        $role_permission = $check_role_result->fetch_assoc();

        // Check if user has permission to delete
        if ($owner_row['user_id'] == $user_id || $role_permission['Role'] == 'admin') {

            // Delete all reviews associated with this recipe
            $delete_reviews_sql = "DELETE FROM reviews WHERE recipe_id = ?";
            $delete_reviews_stmt = $conn->prepare($delete_reviews_sql);
            $delete_reviews_stmt->bind_param('i', $recipe_id);
            $delete_reviews_stmt->execute();
            $delete_reviews_stmt->close();

            // Now delete the recipe itself
            $delete_recipe_sql = "DELETE FROM recipes WHERE id_recipes = ?";
            $delete_recipe_stmt = $conn->prepare($delete_recipe_sql);
            $delete_recipe_stmt->bind_param('i', $recipe_id);

            if ($delete_recipe_stmt->execute()) {
                $response = ['success' => true];
            } else {
                $response = ['success' => false, 'error' => 'Could not delete the recipe.'];
            }
            $delete_recipe_stmt->close();
        } else {
            $response = ['success' => false, 'error' => 'You do not have permission to delete this recipe.'];
        }
    } else {
        $response = ['success' => false, 'error' => 'Recipe not found.'];
    }

    $check_owner_stmt->close();
    echo json_encode($response);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && 
    in_array($_POST['action'], ['rating_1', 'rating_2', 'rating_3', 'rating_4', 'rating_5'])) {

    $recipe_id = intval($_POST['recipe_id']); 
    $user_id = intval($_SESSION['user_id']);
    $rating = intval(substr($_POST['action'], -1));
    
    // Prepare the response array
    $response = [];

    // Check if a rating already exists for this user and recipe
    $check_sql = "SELECT rating FROM reviews WHERE user_id = ? AND recipe_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $user_id, $recipe_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // Update the existing rating
        $update_sql = "UPDATE reviews SET rating = ? WHERE user_id = ? AND recipe_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('iii', $rating, $user_id, $recipe_id);

        if ($update_stmt->execute()) {
            $response = ['success' => true, 'message' => 'Rating successfully updated!'];
        } else {
            $response = ['success' => false, 'error' => 'Error updating rating: ' . $update_stmt->error];
        }

        $update_stmt->close();
    } else {
        // Insert a new rating
        $insert_sql = "INSERT INTO reviews (user_id, rating, recipe_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param('iii', $user_id, $rating, $recipe_id);

        if ($insert_stmt->execute()) {
            $response = ['success' => true, 'message' => 'Rating successfully saved!'];
        } else {
            $response = ['success' => false, 'error' => 'Error saving rating: ' . $insert_stmt->error];
        }

        $insert_stmt->close();
    }

    // Close the check statement and return JSON response
    $check_stmt->close();
    echo json_encode($response);
    exit;
}


$recipes_per_page = 2; // Number of recipes per page
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Current page
$offset = ($current_page - 1) * $recipes_per_page; // Calculate offset

// Sanitize and retrieve filter/search variables
$search_title = isset($_GET['search_title']) ? '%' . sanitize_input($_GET['search_title']) . '%' : null;
$sort = isset($_GET['filter']) ? sanitize_input($_GET['filter']) : null; // Retrieve sort variable

// Base SQL Query for Recipes with Average Rating and Filters
$sql = "
    SELECT recipes.*, 
           COALESCE(AVG(reviews.rating), 0) AS average_rating
    FROM recipes
    LEFT JOIN reviews ON recipes.id_recipes = reviews.recipe_id
    WHERE (recipes.visibility = 1 OR recipes.user_id = ?)
";

$params = [$_SESSION['user_id']];
$types = "i";

// Add search by title
if ($search_title) {
    $sql .= " AND recipes.title LIKE ?";
    $params[] = $search_title;
    $types .= "s";
}

// Group the results
$sql .= " GROUP BY recipes.id_recipes";

// Sorting logic
if ($sort) {
    switch ($sort) {
        case 'az':
            $sql .= " ORDER BY recipes.title ASC";
            break;
        case 'za':
            $sql .= " ORDER BY recipes.title DESC";
            break;
        case 'newest':
            $sql .= " ORDER BY recipes.created_at DESC"; // Assuming 'created_at' is a valid column in your recipes table
            break;
        case 'oldest':
            $sql .= " ORDER BY recipes.created_at ASC"; // Assuming 'created_at' is a valid column in your recipes table
            break;
        case 'none':
            // No specific order will keep the default sorting, so no changes needed.
            break;
        default:
            $sql .= " ORDER BY recipes.created_at DESC"; // Default sorting
            break;
    }
} else {
    $sql .= " ORDER BY recipes.created_at DESC"; // Default sorting
}

// Add LIMIT and OFFSET for pagination
$sql .= " LIMIT ? OFFSET ?";
$params[] = $recipes_per_page; // Number of records per page
$params[] = $offset; // Offset for pagination
$types .= "ii"; 

// Prepare, Bind Parameters, and Execute
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Fetch total number of recipes for pagination calculation
$total_sql = "
    SELECT COUNT(*) as total 
    FROM recipes
    WHERE (recipes.visibility = 1 OR recipes.user_id = ?)
";

$total_params = [$_SESSION['user_id']];
$total_types = "i";

// Add search by title to the total count query
if ($search_title) {
    $total_sql .= " AND recipes.title LIKE ?";
    $total_params[] = $search_title;
    $total_types .= "s";
}

$total_stmt = $conn->prepare($total_sql);
$total_stmt->bind_param($total_types, ...$total_params);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_recipes = $total_row['total'];
$total_pages = ceil($total_recipes / $recipes_per_page); 

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
    <title>Recipes</title>
    <?php include 'includes/fontawesome.php'; ?> 
    <link rel="stylesheet" href="assets/styles/header_footer.css">
    <link rel="stylesheet" href="assets/styles/movies.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.toggle_favourite').click(function() {
                var button = $(this);
                var recipeId = button.data('recipe-id');

                $.ajax({
                    url: 'recipes.php',
                    type: 'POST',
                    data: { recipe_id: recipeId,action:'like_add' },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            button.data('liked', data.liked);
                            button.text(data.liked ? 'Remove from Favourite' : 'Add to Favourite');
                            $('.recent_likes_count').text(data.recent_likes_count);
                        } else {
                            alert('Error: ' + data.error);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request.');
                    }
                });
            });
            $('.delete_recipe_from_db').click(function() {
                var button = $(this);
                var recipeId = button.data('recipe-id');
                $.ajax({
                    url: 'recipes.php',
                    type: 'POST',
                    data: { recipes_id: recipeId,action:'delete' },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            button.closest('li').fadeOut(); 
                        } else {
                            alert('Error: ' + data.error);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request.');
                    }
                });
            });
            $(document).on('mouseenter', '.star-rating span', function () {
                var star = $(this);
                var ratingValue = star.data('rating');
                var tooltip = $('<div class="tooltip"></div>').text(ratingValue.toFixed(2));
                $('body').append(tooltip);

                $(document).on('mousemove.tooltip', function (e) {
                    tooltip.css({
                        left: e.pageX + 15,
                        top: e.pageY + 15
                    });
                });

                tooltip.show();
            });

            $(document).on('mouseleave', '.star-rating span', function () {
                $('.tooltip').remove();
                $(document).off('mousemove.tooltip');
            });

            $(document).on('click', '.star-rating span', function () {
                var star = $(this);
                var itemId = star.closest('.star-rating').data('item-id');
                var itemType = star.closest('.star-rating').data('item-type');
                var rating = star.data('rating');

                $.ajax({
                    url: 'favourite.php',
                    type: 'POST',
                    data: {
                        item_id: itemId,
                        action: 'rating_' + itemType + '_' + rating,
                        rating: rating
                    },
                    success: function (response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            console.log('Rating successful');
                            
                            var newRating = (data.new_average_rating == 0 || isNaN(data.new_average_rating)) ? rating : parseFloat(data.new_average_rating);
                            if (isNaN(newRating)) {
                                newRating = 0;
                            }
                            $('p.rating-value[data-item-id="' + itemId + '"]').text(" Average Rating: "+newRating.toFixed(2));
                            var fullStars = Math.floor(newRating);
                            var halfStar = (newRating - fullStars >= 0.5);
                            var starsHtml = '';
                            for (var i = 1; i <= 5; i++) {
                                if (i <= fullStars) {
                                    starsHtml += '<span class="bi bi-star-fill full-star" data-rating="' + i + '" title="Rating: ' + newRating + '"></span>';
                                } else if (i === fullStars + 1 && halfStar) {
                                    starsHtml += '<span class="bi bi-star-half star-half" data-rating="' + i + '" title="Rating: ' + newRating + '"></span>';
                                } else {
                                    starsHtml += '<span class="bi bi-star empty-star" data-rating="' + i + '" title="Rating: ' + newRating + '"></span>';
                                }
                            }
                            $('.star-rating[data-item-id="' + itemId + '"]').html(starsHtml);
                        } else {
                            alert('Error: ' + data.error);
                        }
                    },
                    error: function () {
                        alert('An error occurred while processing your request.');
                    }
                });
            });




            $("#movieModal").hide();
            $(".add-movie-icon").click(function() {
                $("#movieModal").fadeIn();
            });
            $(".close").click(function() {
                $("#movieModal").fadeOut();
            });
            $(window).click(function(event) {
                if ($(event.target).is("#movieModal")) {
                    $("#movieModal").fadeOut();
                }
            });
            
            const $dragAndDropAreaMovie = $("#drop-area_movie");
            const $fileInputMovie = $("#image");
            const $fileNameDisplayMovie = $(".drop-areaName_movie");
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
    <div id="movieModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <div class="form-container">
                <h3>Create New Recipe</h3>
                <form method="POST" action="recipes.php" enctype="multipart/form-data">
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
                        <label for="image">Image:</label>
                        <div id="drop-area_movie" class="drop-area">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p>Drag & Drop an image here to upload</p>
                        </div>
                        <input type="file" id="image" name="image" accept="image/*" required style="display:none;">
                        <div class="drop-areaName_movie" style="margin-top: 5px; font-size: 14px; color: #555;"></div>
                    </div>

                    <button type="submit" class="btn">Add Recipe</button>
                </form>
            </div>
        </div>
    </div>

    <div class="min_height_div">
        <div class="filter-section">
            <form method="GET" action="" class="filter-div">
                <div class="filter-field">
                    <label for="search_title">Title:</label>
                    <input type="text" name="search_title" placeholder="Enter part of the title" 
                        value="<?php echo htmlspecialchars($_GET['search_title'] ?? ''); ?>">
                </div>
                <div class="filter-field">
                    <label for="filter">Sort By:</label>
                    <select name="filter">
                        <option value="">Select sorting option</option>
                        <option value="az" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'az') ? 'selected' : ''; ?>>Title (A-Z)</option>
                        <option value="za" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'za') ? 'selected' : ''; ?>>Title (Z-A)</option>
                        <option value="newest" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>
                <div class="filter-field">
                    <button type="submit" class="btn">Search</button>
                </div>
            </form>

            <button class="add-movie-icon btn add_movie" style="min-width:120px;" id="addMovieBtn"> Add Recipe</button>

        </div>
        <?php if (isset($_GET['search_title']) || isset($_GET['filter'])): ?>
            <div class="currently-viewing-container">
                <p><strong>Currently Viewing:</strong>
                    <?php if (isset($_GET['search_title']) && $_GET['search_title'] !== ''): ?>
                        Title containing "<em><?php echo htmlspecialchars($_GET['search_title']); ?></em>"
                    <?php endif; ?>
                    <?php if (isset($_GET['filter'])): ?>
                        <?php
                        if (isset($_GET['search_title'])) {
                            echo ' sorted by ';
                        }
                        switch ($_GET['filter']) {
                            case 'az':
                                echo 'Title (A-Z)';
                                break;
                            case 'za':
                                echo 'Title (Z-A)';
                                break;
                            case 'newest':
                                echo 'Newest First';
                                break;
                            case 'oldest':
                                echo 'Oldest First';
                                break;
                            default:
                                break;
                        }
                        ?>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
        <ul class="movie-gallery-recipe">
            <?php while ($recipe = $result->fetch_assoc()): ?>
                <li class="movie-item movie-item-recipe">
                    <div class="movie-image-container ">
                        <?php if (!empty($recipe['image_link'])): ?>
                            <img class="movie-image movie-image-recipe" src="<?php echo htmlspecialchars($recipe['image_link']); ?>" alt="Movie Image">
                        <?php endif; ?>
                        <div class="movie-overlay movie-overlay-recipe">
                            <strong style="font-size:1.3rem;margin-bottom:5px;"><?php echo htmlspecialchars($recipe['title']); ?></strong>
                            <p style="margin-bottom:auto;font-size:1rem;"><?php echo htmlspecialchars($recipe['Ingredients']); ?></p>
                            <p style="margin-bottom:auto;font-size:1rem;"><?php echo htmlspecialchars($recipe['Instructions']); ?></p>
                            <div class="movie-actions movie-actions-recipe">
                                <button class="toggle_favourite" data-recipe-id="<?php echo $recipe['id_recipes']; ?>">
                                    <?php 
                                        $check_like_sql = "SELECT * FROM likes WHERE user_id = ? AND item_id = ? AND item_type = ?";
                                        $check_stmt = $conn->prepare($check_like_sql);
                                        $item_type = "recipe";
                                        $check_stmt->bind_param('iis', $_SESSION['user_id'], $recipe['id_recipes'], $item_type);
                                        $check_stmt->execute();
                                        $check_result = $check_stmt->get_result();
                                        echo ($check_result->num_rows > 0) ? 'Remove from Favourite' : 'Add to Favourite';
                                        $check_stmt->close();
                                    ?>
                                </button>
                                <button class="delete_recipe_from_db" data-recipe-id="<?php echo $recipe['id_recipes']; ?>">Delete</button>
                                <div class="star-rating" data-item-id="<?php echo $recipe['id_recipes']; ?>" data-item-type="recipe">
                                    <?php 
                                        $rating = isset($recipe['average_rating']) ? $recipe['average_rating'] : 0;
                                        $fullStars = floor($rating);
                                        $halfStar = ($rating - $fullStars >= 0.5);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $fullStars) {
                                                echo '<span class="bi bi-star-fill full-star" data-rating="' . $i . '" title="Rating: ' . $rating . '"></span>';
                                            } elseif ($i == $fullStars + 1 && $halfStar) {
                                                echo '<span class="bi bi-star-half star-half" data-rating="' . $i . '" title="Rating: ' . $rating . '"></span>';
                                            } else {
                                                echo '<span class="bi bi-star empty-star" data-rating="' . $i . '" title="Rating: ' . $rating . '"></span>';
                                            }
                                        }
                                    ?>
                                </div>
                                <p class="rating-value" data-item-id="<?php echo $recipe['id_recipes']; ?>" data-item-type="recipe">
                                    Average Rating: <?php echo number_format($recipe['average_rating'], 2); ?>
                                </p>
                            </div>
                        </div>

                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
        <div class="pagination-container">
    <?php if ($total_pages > 1): ?>
        <!-- First Page Link -->
        <?php if ($current_page > 1): ?>
            <a href="?page=1&search_title=<?php echo urlencode($_GET['search_title'] ?? ''); ?>&filter=<?php echo urlencode($_GET['filter'] ?? ''); ?>">
                <i class="fas fa-angle-double-left"></i>
            </a>
        <?php endif; ?>

        <!-- Previous Page Link -->
        <?php if ($current_page > 1): ?>
            <a href="?page=<?php echo $current_page - 1; ?>&search_title=<?php echo urlencode($_GET['search_title'] ?? ''); ?>&filter=<?php echo urlencode($_GET['filter'] ?? ''); ?>">
                <i class="fas fa-angle-left"></i>
            </a>
        <?php endif; ?>

        <!-- Page Numbers -->
        <?php 
        $start_page = max(1, $current_page - 1); 
        $end_page = min($total_pages, $current_page + 1);
        for ($i = $start_page; $i <= $end_page; $i++): 
        ?>
            <a href="?page=<?php echo $i; ?>&search_title=<?php echo urlencode($_GET['search_title'] ?? ''); ?>&filter=<?php echo urlencode($_GET['filter'] ?? ''); ?>" 
               class="<?php echo ($i == $current_page) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <!-- Next Page Link -->
        <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1; ?>&search_title=<?php echo urlencode($_GET['search_title'] ?? ''); ?>&filter=<?php echo urlencode($_GET['filter'] ?? ''); ?>">
                <i class="fas fa-angle-right"></i>
            </a>
        <?php endif; ?>

        <!-- Last Page Link -->
        <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?php echo $total_pages; ?>&search_title=<?php echo urlencode($_GET['search_title'] ?? ''); ?>&filter=<?php echo urlencode($_GET['filter'] ?? ''); ?>">
                <i class="fas fa-angle-double-right"></i>
            </a>
        <?php endif; ?>
    <?php endif; ?>
</div>


    </div>
    
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
