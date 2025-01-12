<?php
session_start();
include 'includes/functions.php';
include 'includes/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_movie') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    function sanitize_input_function($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    $title = sanitize_input_function($_POST['title']);
    $description = sanitize_input_function($_POST['description']);
    $category = sanitize_input_function($_POST['category']);
    $user_id = $_SESSION['user_id'];

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
            $fileName = basename($_FILES['image']['name']);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($fileExtension, $allowedExtensions)) {
                die("Invalid file type");
            }

            $newFileName = uniqid('movie_', true) . '.' . $fileExtension;
            $uploadFileDir = 'uploads/movies/';
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $sql_add_movie = "INSERT INTO movies (Title, Description, Category_id, user_id, visibility, image_link) VALUES (?, ?, ?, ?, 0, ?)";
                $stmt_add_movie = $conn->prepare($sql_add_movie);
                $stmt_add_movie->bind_param('ssiis', $title, $description, $id_category, $user_id, $dest_path);

                if ($stmt_add_movie->execute()) {
                    // echo "Movie added successfully!";
                } else {
                    echo "Error adding movie: " . $stmt_add_movie->error;
                }
            } else {
                die("Error moving the uploaded file");
            }
        } else {
            die("Error uploading the image");
        }
    } else {
        die("Category not found");
    }
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['movie_id']) && isset($_POST['action']) && $_POST['action'] === 'like') {
    $movie_id = $_POST['movie_id'];
    $response = ['success' => false];

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $item_type = 'movie';
        $stmt = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND item_id = ? AND item_type = ?");
        $stmt->bind_param('iis', $user_id, $movie_id, $item_type);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $delete_stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND item_id = ? AND item_type = ?");
            $delete_stmt->bind_param('iis', $user_id, $movie_id, $item_type);
            $delete_stmt->execute();
            $_SESSION['recent_likes'] = array_filter($_SESSION['recent_likes'], function($favourite) use ($movie_id) {
                return $favourite['item_id'] !== $movie_id;
            });
            $recent_likes_count = get_total_likes_count();
            $response = [
                'success' => true,
                'liked' => false,
                'recent_likes_count' => $recent_likes_count,
                'logged_in' => true
            ];
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO likes (user_id, item_id, item_type) VALUES (?, ?, ?)");
            $insert_stmt->bind_param('iis', $user_id, $movie_id, $item_type);
            $insert_stmt->execute();
            $_SESSION['recent_likes'][] = ['item_id' => $movie_id, 'item_type' => 'movie', 'timestamp' => time()];
            $recent_likes_count = get_total_likes_count();
            $response = [
                'success' => true,
                'liked' => true,
                'recent_likes_count' => $recent_likes_count,
                'logged_in' => true
            ];
        }
    } else {
        if (isset($_COOKIE['favourites'])) {
            $favourites = json_decode($_COOKIE['favourites'], true);
        } else {
            $favourites = [];
        }
        $favourite_exists = false;
        foreach ($favourites as $favourite) {
            if ($favourite['item_id'] === $movie_id) {
                $favourite_exists = true;
                break;
            }
        }
        if ($favourite_exists) {
            $favourites = array_filter($favourites, function($fav) use ($movie_id) {
                return $fav['item_id'] !== $movie_id;
            });
            setcookie('favourites', json_encode($favourites), time() + 3600, '/');
            $response = [
                'success' => true,
                'liked' => false,
                'recent_likes_count' => count($favourites),
                'logged_in' => false
            ];
        } else {
            $favourites[] = ['item_id' => $movie_id, 'item_type' => 'movie'];
            setcookie('favourites', json_encode($favourites), time() + 3600, '/');
            $response = [
                'success' => true,
                'liked' => true,
                'recent_likes_count' => count($favourites),
                'logged_in' => false
            ];
        }
    }

    echo json_encode($response);
    exit;
}

function get_total_likes_count() {
    $session_likes_count = isset($_SESSION['recent_likes']) ? count($_SESSION['recent_likes']) : 0;
    if (isset($_COOKIE['favourites'])) {
        $cookie_likes = json_decode($_COOKIE['favourites'], true);
        $cookie_likes_count = is_array($cookie_likes) ? count($cookie_likes) : 0;
    } else {
        $cookie_likes_count = 0;
    }
    return $session_likes_count + $cookie_likes_count;
}



if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $movie_id = intval($_POST['movie_id']);
    $user_id = $_SESSION['user_id'];

    $conn->begin_transaction();

    $check_owner_sql = "SELECT user_id FROM movies WHERE id_movies = ?";
    $check_owner_stmt = $conn->prepare($check_owner_sql);
    $check_owner_stmt->bind_param('i', $movie_id);
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

        if ($owner_row['user_id'] == $user_id || $role_permission['Role'] == 'admin') {
            $delete_reviews_sql = "DELETE FROM reviews WHERE movie_id = ?";
            $delete_reviews_stmt = $conn->prepare($delete_reviews_sql);
            $delete_reviews_stmt->bind_param('i', $movie_id);
            $delete_reviews_success = $delete_reviews_stmt->execute();

            if ($delete_reviews_success) {
                $delete_movie_sql = "DELETE FROM movies WHERE id_movies = ?";
                $delete_movie_stmt = $conn->prepare($delete_movie_sql);
                $delete_movie_stmt->bind_param('i', $movie_id);
                if ($delete_movie_stmt->execute()) {
                    $conn->commit(); 
                    $response = ['success' => true];
                } else {
                    $conn->rollback(); 
                    $response = ['success' => false, 'error' => 'Could not delete the movie.'];
                }
                $delete_movie_stmt->close();
            } else {
                $conn->rollback();
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

    $check_owner_stmt->close();
    $check_role_stmt->close();
    exit;
}


$sql_categories = "SELECT Name FROM categories";
$stmt_categories = $conn->prepare($sql_categories);
$stmt_categories->execute();
$categories_result = $stmt_categories->get_result();
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

$movies_per_page = 8;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $movies_per_page;

$search_title = isset($_GET['search_title']) ? '%' . sanitize_input($_GET['search_title']) . '%' : null;
$search_category = isset($_GET['search_category']) && $_GET['search_category'] !== '' ? sanitize_input($_GET['search_category']) : null;
$filter = isset($_GET['filter']) ? sanitize_input($_GET['filter']) : null;
$sql = "
    SELECT movies.*, 
           COALESCE(AVG(reviews.rating), 0) AS average_rating
    FROM movies
    LEFT JOIN reviews ON movies.id_movies = reviews.movie_id
    LEFT JOIN categories ON movies.Category_id = categories.id_category
    WHERE (movies.visibility = 1
";

$params = []; 
$types = ""; 

if (isset($_SESSION['user_id'])) {
    $sql .= " OR movies.user_id = ?";
    $params[] = $_SESSION['user_id'];
    $types .= "i"; 
}
$sql .= ")";
if ($search_title) {
    $sql .= " AND movies.Title LIKE ?";
    $params[] = '%' . $search_title . '%'; 
    $types .= "s";
}

if ($search_category) {
    $sql .= " AND categories.Name = ?";
    $params[] = $search_category; 
    $types .= "s"; 
}

$sql .= " GROUP BY movies.id_movies";

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

$sql .= " LIMIT ? OFFSET ?";
$params[] = $movies_per_page; 
$params[] = $offset; 
$types .= "ii"; 

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$total_sql = "
    SELECT COUNT(*) as total 
    FROM movies
    LEFT JOIN categories ON movies.Category_id = categories.id_category
    WHERE (movies.visibility = 1
";

$total_params = []; 
$total_types = "";

if (isset($_SESSION['user_id'])) {
    $total_sql .= " OR movies.user_id = ?";
    $total_params[] = $_SESSION['user_id'];
    $total_types .= "i";
}

$total_sql .= ")";

if ($search_title) {
    $total_sql .= " AND movies.Title LIKE ?";
    $total_params[] = '%' . $search_title . '%'; 
    $total_types .= "s";
}

if ($search_category) {
    $total_sql .= " AND categories.Name = ?";
    $total_params[] = $search_category;
    $total_types .= "s";
}

$total_stmt = $conn->prepare($total_sql);
if (!empty($total_params)) {
    $total_stmt->bind_param($total_types, ...$total_params);
}

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
    <?php include 'includes/fontawesome.php'; ?> 
    <link rel="stylesheet" href="assets/styles/header_footer.css">
    <link rel="stylesheet" href="assets/styles/movies.css">
    <?php include 'includes/fontawesome.php'; ?> 
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        
        $(document).ready(function() {
            
            $('.toggle_favourite').click(function() {
                var button = $(this);
                var movieId = button.data('movie-id');

                $.ajax({
                    url: 'movies.php',
                    type: 'POST',
                    data: { 
                        movie_id: movieId,
                        action: 'like' 
                    },
                    success: function(response) {
                        var data = JSON.parse(response);

                        if (data.success) {
                            button.text(data.liked ? 'Remove from Favourite' : 'Add to Favourite');
                            if (data.logged_in === false) {
                                var favourites = JSON.parse(localStorage.getItem('favourites')) || [];
                                
                                if (data.liked) {
                                    favourites.push({ item_id: movieId, item_type: 'movie' });
                                } else {
                                    favourites = favourites.filter(function(fav) {
                                        return !(fav.item_id === movieId && fav.item_type === 'movie');
                                    });
                                }
                                localStorage.setItem('favourites', JSON.stringify(favourites)); 
                            }
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
            var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

            if (!isLoggedIn) {
                $('.star-rating span').css('pointer-events', 'none'); 
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
                <a class="links_navigation link_log like_log" href="favourite.php">
                    <i class="fa-regular fa-heart icon_size"></i>
                    <p class="recent_likes_count">
                        <?php
                            $likes_count = 0;
                            if (isset($_SESSION['user_id'])) {
                                $likes_count += count($_SESSION['recent_likes']);
                                if (isset($_COOKIE['favourites'])) {
                                    $favourites = json_decode($_COOKIE['favourites'], true);
                                    $likes_count += count($favourites);
                                }
                            } else {
                                if (isset($_COOKIE['favourites'])) {
                                    $favourites = json_decode($_COOKIE['favourites'], true);
                                    $likes_count = count($favourites);
                                }
                            }
                            echo $likes_count; 
                        ?>
                    </p>
                </a>

                <a class="links_navigation link_log" href="index.php"><i class="fas fa-home icon_size"></i></a>
            </nav>
            <a class="links_navigation favourite_small_screens like_log" href="favourite.php"><i class="fa-regular fa-heart icon_margin favourite_icon"></i>
                <p class="recent_likes_count"> 
                    <?php
                        $likes_count = 0;
                        if (isset($_SESSION['user_id'])) {
                            $likes_count += count($_SESSION['recent_likes']);
                            if (isset($_COOKIE['favourites'])) {
                                $favourites = json_decode($_COOKIE['favourites'], true);
                                $likes_count += count($favourites);
                            }
                        } else {
                            if (isset($_COOKIE['favourites'])) {
                                $favourites = json_decode($_COOKIE['favourites'], true);
                                $likes_count = count($favourites);
                            }
                        }
                        echo $likes_count; 
                    ?>
                </p>
            </a>
                    
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
                <h3>Create New Movie</h3>
                <form method="POST" action="movies.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_movie">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" name="title" required maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea name="description" required maxlength="500"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="category">Category:</label>
                        <select name="category" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['Name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($category['Name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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

                    <button type="submit" class="btn">Add Movie</button>
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

            <?php if (isset($_SESSION['user_id'])): ?>
                <button class="add-movie-icon btn add_movie" style="min-width:120px;" id="addMovieBtn"> Add Movie</button>
            <?php endif ?>

        </div>
        <?php if (isset($_GET['search_title']) || (isset($_GET['search_category']) && $_GET['search_category'] !== '') || isset($_GET['filter'])): ?>
            <div class="currently-viewing-container">
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
            </div>
        <?php endif; ?>
        <ul class="movie-gallery">
            <?php while ($movie = $result->fetch_assoc()): ?>
                <li class="movie-item">
                    <div class="movie-image-container">
                        <?php if (!empty($movie['image_link'])): ?>
                            <img class="movie-image" src="<?php echo htmlspecialchars($movie['image_link']); ?>" alt="Movie Image">
                        <?php endif; ?>
                        <div class="movie-overlay">
                            <strong style="font-size:1.3rem;margin-bottom:5px;"><?php echo htmlspecialchars($movie['Title']); ?></strong>
                            <p style="margin-bottom:auto;font-size:1rem;"><?php echo htmlspecialchars($movie['Description']); ?></p>
                            <div class="movie-actions">
                                <button class="toggle_favourite" data-movie-id="<?php echo $movie['id_movies']; ?>">
                                    <?php
                                        if (isset($_SESSION['user_id'])) {
                                            $user_id = $_SESSION['user_id'];
                                            $check_like_sql = "SELECT * FROM likes WHERE user_id = ? AND item_id = ? AND item_type = ?";
                                            $check_stmt = $conn->prepare($check_like_sql);
                                            $item_type = 'movie';
                                            $check_stmt->bind_param('iis', $user_id, $movie['id_movies'], $item_type);
                                            $check_stmt->execute();
                                            $check_result = $check_stmt->get_result();
                                            echo ($check_result->num_rows > 0) ? 'Remove from Favourite' : 'Add to Favourite';
                                            $check_stmt->close();
                                        } else {
                                            if (isset($_COOKIE['favourites'])) {
                                                $favourites = json_decode($_COOKIE['favourites'], true);
                                                $is_liked = false;
                                                foreach ($favourites as $favourite) {
                                                    if (isset($favourite['item_id']) && (string)$favourite['item_id'] === (string)$movie['id_movies'] && $favourite['item_type'] === 'movie') {
                                                        $is_liked = true; 
                                                        break;
                                                    }
                                                }
                                                echo $is_liked ? 'Remove from Favourite' : 'Add to Favourite';
                                            } else {
                                                echo 'Add to Favourite';
                                            }
                                        }
                                    ?>
                                </button>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="delete_movie_from_db" data-movie-id="<?php echo $movie['id_movies']; ?>">Delete</button>
                                <?php endif; ?>
                                <div class="star-rating" data-item-id="<?php echo $movie['id_movies']; ?>" data-item-type="movie">
                                    <?php
                                        $rating = isset($movie['average_rating']) ? $movie['average_rating'] : 0;
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
                                <p class="rating-value" data-item-id="<?php echo $movie['id_movies']; ?>" data-item-type="movie">
                                    Average Rating: <?php echo number_format($movie['average_rating'], 2); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>




        <div class="pagination-container">
            <?php if ($total_pages > 1): ?>
                <?php if ($current_page > 1): ?>
                    <a href="?page=1&search_title=<?php echo urlencode($_GET['search_title'] ?? ''); ?>&search_category=<?php echo urlencode($_GET['search_category'] ?? ''); ?>&filter=<?php echo $filter; ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                <?php endif; ?>
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?>&search_title=<?php echo urlencode($_GET['search_title'] ?? ''); ?>&search_category=<?php echo urlencode($_GET['search_category'] ?? ''); ?>&filter=<?php echo $filter; ?>">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php endif; ?>

                <?php 
                $start_page = max(1, $current_page - 1); 
                $end_page = min($total_pages, $current_page + 1);
                for ($i = $start_page; $i <= $end_page; $i++): 
                ?>
                    <a href="?page=<?php echo $i; ?>&search_title=<?php echo urlencode($_GET['search_title'] ?? ''); ?>&search_category=<?php echo urlencode($_GET['search_category'] ?? ''); ?>&filter=<?php echo $filter; ?>" <?php if ($i == $current_page) echo 'class="active"'; ?>>
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>&search_title=<?php echo urlencode($_GET['search_title'] ?? ''); ?>&search_category=<?php echo urlencode($_GET['search_category'] ?? ''); ?>&filter=<?php echo $filter; ?>">
                        <i class="fas fa-angle-right"></i>
                    </a>
                <?php endif; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $total_pages; ?>&search_title=<?php echo urlencode($_GET['search_title'] ?? ''); ?>&search_category=<?php echo urlencode($_GET['search_category'] ?? ''); ?>&filter=<?php echo $filter; ?>">
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
