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


$recipes_per_page = 5; // Number of recipes per page
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Current page
$offset = ($current_page - 1) * $recipes_per_page; // Calculate offset

// Sanitize and retrieve filter/search variables
$search_title = isset($_GET['search_title']) ? '%' . sanitize_input($_GET['search_title']) . '%' : null;
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : null; // Retrieve sort variable

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
    <link rel="stylesheet" href="css/style.css">
    <?php include 'includes/fontawesome.php'; ?> 
    <link rel="stylesheet" href="assets/styles/header_footer.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.add_to_favourite').click(function() {
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
            $('.delete_book_from_db').click(function() {
                var button = $(this);
                var recipeId = button.data('recipe-id');
                $.ajax({
                    url: 'recipes.php',
                    type: 'POST',
                    data: { recipes_id: recipeId,action:'delete' },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            button.closest('tr').fadeOut(); 
                        } else {
                            alert('Error: ' + data.error);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request.');
                    }
                });
            });
            $('.rating_recipe_1').click(function() {
                var button = $(this);
                var recipeId = button.data('recipe-id');
                $.ajax({
                    url: 'recipes.php',
                    type: 'POST',
                    data: { recipe_id: recipeId,action:'rating_1' },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            console.log('Success');
                        } else {
                            alert('Error: ' + data.error);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request.');
                    }
                });
            });
            $('.rating_recipe_2').click(function() {
                var button = $(this);
                var recipeId = button.data('recipe-id');
                $.ajax({
                    url: 'recipes.php',
                    type: 'POST',
                    data: { recipe_id: recipeId,action:'rating_2' },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            console.log('Success');
                        } else {
                            alert('Error: ' + data.error);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request.');
                    }
                });
            });
            $('.rating_recipe_3').click(function() {
                var button = $(this);
                var recipeId = button.data('recipe-id');
                $.ajax({
                    url: 'recipes.php',
                    type: 'POST',
                    data: { recipe_id: recipeId,action:'rating_3' },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            console.log('Success');
                        } else {
                            alert('Error: ' + data.error);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request.');
                    }
                });
            });
            $('.rating_recipe_4').click(function() {
                var button = $(this);
                var recipeId = button.data('recipe-id');
                $.ajax({
                    url: 'recipes.php',
                    type: 'POST',
                    data: { recipe_id: recipeId,action:'rating_4' },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            console.log('Success');
                        } else {
                            alert('Error: ' + data.error);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request.');
                    }
                });
            });
            $('.rating_recipe_5').click(function() {
                var button = $(this);
                var recipeId = button.data('recipe-id');
                $.ajax({
                    url: 'recipes.php',
                    type: 'POST',
                    data: { recipe_id: recipeId,action:'rating_5' },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            console.log('Success');
                        } else {
                            alert('Error: ' + data.error);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request.');
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
    <h2>Your Recipes</h2>
    <form method="POST" action="recipes.php" enctype="multipart/form-data"> 
        <input type="hidden" name="action" value="add_recipe">
        
        <label for="title">Title:</label>
        <input type="text" name="title" required>

        <label for="ingredients">Ingredients:</label>
        <textarea name="ingredients" required></textarea>

        <label for="instructions">Instructions:</label>
        <textarea name="instructions" required></textarea>

        <label for="image">Image</label>
        <input type="file" id="image" name="image" accept="image/*" required>
        
        <button type="submit">Add Recipe</button>
    </form>
    <h3>Search Recipes</h3>
    <form method="GET" action="recipes.php">
    <label for="search_title">Title:</label>
    <input type="text" name="search_title" placeholder="Enter part of the title" 
           value="<?php echo htmlspecialchars($_GET['search_title'] ?? ''); ?>">

    <label for="sort">Sort By:</label>
    <select name="sort">
        <option value="" <?php echo (isset($_GET['sort']) && $_GET['sort'] === '') ? 'selected' : ''; ?>>No Sort</option>
        <option value="newest" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'newest') ? 'selected' : ''; ?>>Newest</option>
        <option value="oldest" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'oldest') ? 'selected' : ''; ?>>Oldest</option>
        <option value="az" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'az') ? 'selected' : ''; ?>>A-Z</option>
        <option value="za" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'za') ? 'selected' : ''; ?>>Z-A</option>
    </select>

    <button type="submit">Search</button>
</form>



<?php 
// Update Currently Viewing Message to reflect search title and sorting option
if (!empty($_GET['search_title']) || !empty($_GET['search_category']) || !empty($_GET['sort'])): 
?>
    <p><strong>Currently Viewing:</strong>
        <?php if (!empty($_GET['search_title'])): ?>
            Title containing "<em><?php echo htmlspecialchars($_GET['search_title']); ?></em>"
        <?php endif; ?>
        <?php if (!empty($_GET['search_category'])): ?>
            <?php if (!empty($_GET['search_title'])) echo ' and '; ?>
            Category "<em><?php echo htmlspecialchars($_GET['search_category']); ?></em>"
        <?php endif; ?>
        <?php if (!empty($_GET['sort'])): ?>
            <?php if (!empty($_GET['search_title']) || !empty($_GET['search_category'])) echo ' sorted by '; ?>
            <em>
                <?php
                switch ($_GET['sort']) {
                    case 'newest':
                        echo 'Newest First';
                        break;
                    case 'oldest':
                        echo 'Oldest First';
                        break;
                    case 'az':
                        echo 'Title (A-Z)';
                        break;
                    case 'za':
                        echo 'Title (Z-A)';
                        break;
                }
                ?>
            </em>
        <?php endif; ?>
    </p>
<?php endif; ?>



    <h3>Recipe List</h3>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Ingredients</th>
                <th>Instructions</th>
                <th>Image</th>
                <th>Like</th>
                <th>Delete</th>
                <th>Rating</th>
                <th>Average Rating</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($recipe = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($recipe['title']); ?></td>
                    <td><?php echo htmlspecialchars($recipe['Ingredients']); ?></td>
                    <td><?php echo htmlspecialchars($recipe['Instructions']); ?></td>
                    <td>
                        <?php if (!empty($recipe['image_link'])): ?>
                            <img src="<?php echo htmlspecialchars($recipe['image_link']); ?>" alt="Recipe Image" style="width:50px;height:auto;">
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="add_to_favourite" data-recipe-id="<?php echo $recipe['id_recipes']; ?>" data-liked="<?php echo $recipe['like']; ?>">
                            <?php 
                                // Check if the user has liked the recipe
                                $user_id = $_SESSION['user_id'];
                                $check_like_sql = "SELECT * FROM likes WHERE user_id = ? AND item_id = ? AND item_type = ?";
                                $check_stmt = $conn->prepare($check_like_sql);
                                $item_type = "recipe";
                                $check_stmt->bind_param('iis', $user_id, $recipe['id_recipes'], $item_type);
                                $check_stmt->execute();
                                $check_result = $check_stmt->get_result();
                                echo ($check_result->num_rows > 0) ? 'Remove from Favourite' : 'Add to Favourite';
                                $check_stmt->close();
                            ?>
                        </button>
                    </td>
                    <td>
                    <button class="delete_book_from_db" data-recipe-id="<?php echo $recipe['id_recipes']; ?>">
                            Delete
                        </button>
                    </td> 
                    <td>
                        <button class="rating_recipe_1" data-recipe-id="<?php echo $recipe['id_recipes']; ?>">1</button>
                        <button class="rating_recipe_2" data-recipe-id="<?php echo $recipe['id_recipes']; ?>">2</button>
                        <button class="rating_recipe_3" data-recipe-id="<?php echo $recipe['id_recipes']; ?>">3</button>
                        <button class="rating_recipe_4" data-recipe-id="<?php echo $recipe['id_recipes']; ?>">4</button>
                        <button class="rating_recipe_5" data-recipe-id="<?php echo $recipe['id_recipes']; ?>">5</button>
                    </td>
                    <td><?php echo number_format($recipe['average_rating'], 2); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <div class="pagination">
    <?php if ($total_pages > 1): ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search_title=<?php echo urlencode($_GET['search_title'] ?? ''); ?>&sort=<?php echo urlencode($_GET['sort'] ?? ''); ?>" <?php if ($i == $current_page) echo 'class="active"'; ?>><?php echo $i; ?></a>
        <?php endfor; ?>
    <?php endif; ?>
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
