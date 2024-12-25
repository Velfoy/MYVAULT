<?php
session_start();
include 'includes/functions.php';
include 'includes/db.php';
check_login();

// Handle adding a movie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_movie') {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $category = sanitize_input($_POST['category']);
    $user_id = $_SESSION['user_id'];

    // Fetch category ID
    $sql = "SELECT id_category FROM categories WHERE Name=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $category);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id_category = $row['id_category'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $uploadFileDir = 'uploads/movies/';
            $dest_path = $uploadFileDir . basename($fileName);

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $sql_add_movie = "INSERT INTO movies (Title, Description, Category_id, user_id, visibility, image_link) VALUES (?, ?, ?, ?, 0, ?)";
                $stmt_add_movie = $conn->prepare($sql_add_movie);
                $stmt_add_movie->bind_param('ssiis', $title, $description, $id_category, $user_id, $dest_path);

                if (!$stmt_add_movie->execute()) {
                    echo "Error adding movie: " . $stmt_add_movie->error;
                }
            } else {
                echo "Error moving the uploaded file.";
            }
        } else {
            echo "Error uploading the image.";
        }
    } else {
        echo "Category not found!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['movie_id'])&&isset($_POST['action'])&& $_POST['action'] === 'like' ) {
    $movie_id = $_POST['movie_id'];
    $user_id = $_SESSION['user_id'];
    $item_type = "movie";
    $stmt = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND item_id = ? AND item_type = ?");
    $stmt->bind_param('iis', $user_id, $movie_id, $item_type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND item_id = ? AND item_type = ?");
        $delete_stmt->bind_param('iis', $user_id, $movie_id, $item_type);
        
        if ($delete_stmt->execute()) {
            $_SESSION['recent_likes'] = array_filter($_SESSION['recent_likes'], function($like) use ($movie_id, $item_type) {
                return !($like['item_id'] == $movie_id && $like['item_type'] == $item_type);
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
        $insert_stmt->bind_param('iis', $user_id, $movie_id, $item_type);
        
        if ($insert_stmt->execute()) {
            $_SESSION['recent_likes'][] = [
                'item_id' => $movie_id,
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
    $movie_id = intval($_POST['movie_id']);
    $user_id = $_SESSION['user_id'];

    // Start transaction
    $conn->begin_transaction();

    // Check movie ownership
    $check_owner_sql = "SELECT user_id FROM movies WHERE id_movies = ?";
    $check_owner_stmt = $conn->prepare($check_owner_sql);
    $check_owner_stmt->bind_param('i', $movie_id);
    $check_owner_stmt->execute();
    $check_owner_result = $check_owner_stmt->get_result();

    // Check user role
    $check_role_sql = "SELECT Role FROM users WHERE ID_USER = ?";
    $check_role_stmt = $conn->prepare($check_role_sql);
    $check_role_stmt->bind_param('i', $user_id);
    $check_role_stmt->execute();
    $check_role_result = $check_role_stmt->get_result();

    if ($check_owner_result->num_rows > 0) {
        $owner_row = $check_owner_result->fetch_assoc();
        $role_permission = $check_role_result->fetch_assoc();

        // Check if user has permission to delete the movie
        if ($owner_row['user_id'] == $user_id || $role_permission['Role'] == 'admin') {

            // Delete related reviews
            $delete_reviews_sql = "DELETE FROM reviews WHERE movie_id = ?";
            $delete_reviews_stmt = $conn->prepare($delete_reviews_sql);
            $delete_reviews_stmt->bind_param('i', $movie_id);
            $delete_reviews_success = $delete_reviews_stmt->execute();

            if ($delete_reviews_success) {
                // If reviews deleted successfully, delete the movie
                $delete_movie_sql = "DELETE FROM movies WHERE id_movies = ?";
                $delete_movie_stmt = $conn->prepare($delete_movie_sql);
                $delete_movie_stmt->bind_param('i', $movie_id);
                if ($delete_movie_stmt->execute()) {
                    $conn->commit(); // Commit transaction
                    $response = ['success' => true];
                } else {
                    $conn->rollback(); // Rollback transaction on failure
                    $response = ['success' => false, 'error' => 'Could not delete the movie.'];
                }
                $delete_movie_stmt->close();
            } else {
                $conn->rollback(); // Rollback transaction on failure to delete reviews
                $response = ['success' => false, 'error' => 'Could not delete associated reviews.'];
            }
            $delete_reviews_stmt->close();
        } else {
            $response = ['success' => false, 'error' => 'You do not have permission to delete this movie.'];
        }
    } else {
        $response = ['success' => false, 'error' => 'Movie not found.'];
    }

    echo json_encode($response);

    // Close statements and end transaction if not committed
    $check_owner_stmt->close();
    $check_role_stmt->close();
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && 
    in_array($_POST['action'], ['rating_1', 'rating_2', 'rating_3', 'rating_4', 'rating_5'])) {

    $movie_id = intval($_POST['movie_id']); 
    $user_id = intval($_SESSION['user_id']);
    $rating = intval(substr($_POST['action'], -1));
    
    $response = [];

    // Check if a rating already exists for this user and movie
    $check_sql = "SELECT rating FROM reviews WHERE user_id = ? AND movie_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $user_id, $movie_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // Update the existing rating
        $update_sql = "UPDATE reviews SET rating = ? WHERE user_id = ? AND movie_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('iii', $rating, $user_id, $movie_id);

        if ($update_stmt->execute()) {
            $response = ['success' => true, 'message' => 'Rating successfully updated!'];
        } else {
            $response = ['success' => false, 'error' => 'Error updating rating: ' . $update_stmt->error];
        }

        $update_stmt->close();
    } else {
        // Insert a new rating
        $insert_sql = "INSERT INTO reviews (user_id, rating, movie_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param('iii', $user_id, $rating, $movie_id);

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

// Fetch categories
$sql_categories = "SELECT Name FROM categories";
$stmt_categories = $conn->prepare($sql_categories);
$stmt_categories->execute();
$categories_result = $stmt_categories->get_result();
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Check if search filters are set
$movies_per_page = 5;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $movies_per_page;

// Sanitize and retrieve filter/search variables
$search_title = isset($_GET['search_title']) ? '%' . sanitize_input($_GET['search_title']) . '%' : null;
$search_category = isset($_GET['search_category']) && $_GET['search_category'] !== '' ? sanitize_input($_GET['search_category']) : null;
$filter = isset($_GET['filter']) ? sanitize_input($_GET['filter']) : null;

$sql = "
    SELECT movies.*, 
           COALESCE(AVG(reviews.rating), 0) AS average_rating
    FROM movies
    LEFT JOIN reviews ON movies.id_movies = reviews.movie_id
    LEFT JOIN categories ON movies.Category_id = categories.id_category
    WHERE (movies.visibility = 1 OR movies.user_id = ?)
";

$params = [$_SESSION['user_id']];
$types = "i"; 

// Add search by title
if ($search_title) {
    $sql .= " AND movies.Title LIKE ?";
    $params[] = $search_title;
    $types .= "s";
}

// Add search by category
if ($search_category) {
    $sql .= " AND categories.Name = ?";
    $params[] = $search_category;
    $types .= "s";
}

// Group the results
$sql .= " GROUP BY movies.id_movies";

// Sorting logic
if ($filter) {
    switch ($filter) {
        case 'az':
            $sql .= " ORDER BY movies.Title ASC";
            break;
        case 'za':
            $sql .= " ORDER BY movies.Title DESC";
            break;
        case 'newest':
            $sql .= " ORDER BY movies.Created_at DESC"; 
            break;
        case 'oldest':
            $sql .= " ORDER BY movies.Created_at ASC";
            break;
        default:
            $sql .= " ORDER BY movies.Created_at DESC"; 
            break;
    }
} else {
    $sql .= " ORDER BY movies.Created_at DESC"; 
}

// Add LIMIT and OFFSET for pagination
$sql .= " LIMIT ? OFFSET ?";
$params[] = $movies_per_page;
$params[] = $offset;
$types .= "ii"; 

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Fetch total number of movies for pagination calculation
$total_sql = "
    SELECT COUNT(*) as total 
    FROM movies
    LEFT JOIN categories ON movies.Category_id = categories.id_category
    WHERE (movies.visibility = 1 OR movies.user_id = ?)
";

$total_params = [$_SESSION['user_id']];
$total_types = "i";

// Add search by title
if ($search_title) {
    $total_sql .= " AND movies.Title LIKE ?";
    $total_params[] = $search_title;
    $total_types .= "s";
}

// Add search by category
if ($search_category) {
    $total_sql .= " AND categories.Name = ?";
    $total_params[] = $search_category;
    $total_types .= "s";
}
$total_stmt = $conn->prepare($total_sql);
$total_stmt->bind_param($total_types, ...$total_params);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_movies = $total_row['total'];
$total_pages = ceil($total_movies / $movies_per_page);


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
    <title>Movies</title>
    <link rel="stylesheet" href="css/style.css">
    <?php include 'includes/fontawesome.php'; ?> 
    <link rel="stylesheet" href="assets/styles/header_footer.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        
        $(document).ready(function() {
            
            $('.toggle_favourite').click(function() {
                var button = $(this);
                var movieId = button.data('movie-id');

                $.ajax({
                    url: 'movies.php',
                    type: 'POST',
                    data: { movie_id: movieId ,action: 'like' },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
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
            $('.delete_movie_from_db').click(function() {
                var button = $(this);
                var movieId = button.data('movie-id');

                if (!confirm("Are you sure you want to delete this movie?")) {
                    return; 
                }
                
                $.ajax({
                    url: 'movies.php',
                    type: 'POST', 
                    data: { movie_id: movieId, action: 'delete' },
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
            $('.rating_movie_1').click(function() {
                var button = $(this);
                var movieId = button.data('movie-id');
                $.ajax({
                    url: 'movies.php',
                    type: 'POST',
                    data: { movie_id: movieId,action:'rating_1' },
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
            $('.rating_movie_2').click(function() {
                var button = $(this);
                var movieId = button.data('movie-id');
                $.ajax({
                    url: 'movies.php',
                    type: 'POST',
                    data: { movie_id: movieId,action:'rating_2' },
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
            $('.rating_movie_3').click(function() {
                var button = $(this);
                var movieId = button.data('movie-id');
                $.ajax({
                    url: 'movies.php',
                    type: 'POST',
                    data: { movie_id: movieId,action:'rating_3' },
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
            $('.rating_movie_4').click(function() {
                var button = $(this);
                var movieId = button.data('movie-id');
                $.ajax({
                    url: 'movies.php',
                    type: 'POST',
                    data: { movie_id: movieId,action:'rating_4' },
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
            $('.rating_movie_5').click(function() {
                var button = $(this);
                var movieId = button.data('movie-id');
                $.ajax({
                    url: 'movies.php',
                    type: 'POST',
                    data: { movie_id: movieId,action:'rating_5' },
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
    <h2>Your Movies</h2>
    <form method="POST" action="movies.php" enctype="multipart/form-data"> 
        <input type="hidden" name="action" value="add_movie">
        
        <label for="title">Title:</label>
        <input type="text" name="title" required>

        <label for="description">Description:</label>
        <textarea name="description" required></textarea>

        <label for="category">Category:</label>
        <select name="category" required>
            <option value="">Select a category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo htmlspecialchars($category['Name']); ?>"><?php echo htmlspecialchars($category['Name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="image">Image</label>
        <input type="file" id="image" name="image" accept="image/*" required>
        
        <button type="submit">Add Movie</button>
    </form>
    <h3>Movie List</h3>
    <!-- Filter and Sort Form -->
    <h3>Search Movies</h3>
<form method="GET" action="">
    <label for="search_title">Title:</label>
    <input type="text" name="search_title" placeholder="Enter part of the title" value="<?php echo htmlspecialchars($_GET['search_title'] ?? ''); ?>">

    <label for="search_category">Category:</label>
    <select name="search_category">
        <option value="">Select a category</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?php echo htmlspecialchars($category['Name']); ?>" 
                <?php echo (isset($_GET['search_category']) && $_GET['search_category'] === htmlspecialchars($category['Name'])) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($category['Name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="filter">Sort By:</label>
    <select name="filter">
        <option value="">Select sorting option</option>
        <option value="az" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'az') ? 'selected' : ''; ?>>Title (A-Z)</option>
        <option value="za" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'za') ? 'selected' : ''; ?>>Title (Z-A)</option>
        <option value="newest" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'newest') ? 'selected' : ''; ?>>Newest First</option>
        <option value="oldest" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
    </select>
    
    <button type="submit">Search</button>
</form>

<!-- Display Selected Search Criteria -->
<?php if (isset($_GET['search_title']) || (isset($_GET['search_category']) && $_GET['search_category'] !== '') || isset($_GET['filter'])): ?>
    <p><strong>Currently Viewing:</strong>
        <?php if (isset($_GET['search_title']) && $_GET['search_title'] !== ''): ?>
            Title containing "<em><?php echo htmlspecialchars($_GET['search_title']); ?></em>"
        <?php endif; ?>
        <?php if (isset($_GET['search_category']) && $_GET['search_category'] !== ''): ?>
            <?php echo isset($_GET['search_title']) && $_GET['search_title'] !== '' ? ' and ' : ''; ?>
            Category "<em><?php echo htmlspecialchars($_GET['search_category']); ?></em>"
        <?php endif; ?>
        <?php if (isset($_GET['filter'])): ?>
            <?php
            if (isset($_GET['search_title']) || isset($_GET['search_category'])) {
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
<?php endif; ?>



    <ul>
        <?php while ($movie = $result->fetch_assoc()): ?>
            <li>
                <strong><?php echo htmlspecialchars($movie['Title']); ?></strong><br>
                <?php echo htmlspecialchars($movie['Description']); ?><br>
                <?php if (!empty($movie['image_link'])): ?>
                    <img src="<?php echo htmlspecialchars($movie['image_link']); ?>" alt="Movie Image" style="width:50px;height:auto;">
                <?php endif; ?><br>
                <button class="toggle_favourite" data-movie-id="<?php echo $movie['id_movies']; ?>">
                    <?php 
                        // Check if the user has liked the movie
                        $check_like_sql = "SELECT * FROM likes WHERE user_id = ? AND item_id = ? AND item_type = ?";
                        $check_stmt = $conn->prepare($check_like_sql);
                        $item_type = "movie";
                        $check_stmt->bind_param('iis', $_SESSION['user_id'], $movie['id_movies'], $item_type);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        echo ($check_result->num_rows > 0) ? 'Remove from Favourite' : 'Add to Favourite';
                        $check_stmt->close();
                    ?>
                </button>
                <button class="delete_movie_from_db" data-movie-id="<?php echo $movie['id_movies']; ?>">Delete</button>
                <button class="rating_movie_1" data-movie-id="<?php echo $movie['id_movies']; ?>">1</button>
                <button class="rating_movie_2" data-movie-id="<?php echo $movie['id_movies']; ?>">2</button>
                <button class="rating_movie_3" data-movie-id="<?php echo $movie['id_movies']; ?>">3</button>
                <button class="rating_movie_4" data-movie-id="<?php echo $movie['id_movies']; ?>">4</button>
                <button class="rating_movie_5" data-movie-id="<?php echo $movie['id_movies']; ?>">5</button>
                <?php echo number_format($movie['average_rating'], 2); ?>
            </li>
        <?php endwhile; ?>
    </ul>

    <!-- Pagination Links -->
    <div class="pagination">
        <?php if ($total_pages > 1): ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search_title=<?php echo urlencode($_GET['search_title'] ?? ''); ?>&search_category=<?php echo urlencode($_GET['search_category'] ?? ''); ?>&filter=<?php echo $filter; ?>" <?php if ($i == $current_page) echo 'class="active"'; ?>><?php echo $i; ?></a>
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
