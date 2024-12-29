<?php
session_start();
include 'includes/functions.php';
include 'includes/db.php';
check_login();

$sql_recipes = "SELECT item_id FROM likes WHERE user_id = ? AND item_type=?";
$stmt_recipes = $conn->prepare($sql_recipes);
$user_id = $_SESSION['user_id'];
$item_type = "recipe";
$stmt_recipes->bind_param('is', $user_id, $item_type);
$stmt_recipes->execute();
$result_recipes = $stmt_recipes->get_result(); 

$recipe_ids = [];
while ($row = $result_recipes->fetch_assoc()) {
    $recipe_ids[] = $row['item_id'];
}

$recipes = [];
if (!empty($recipe_ids)) {
    $placeholders = implode(',', array_fill(0, count($recipe_ids), '?'));

    $sql_details = "
        SELECT recipes.*, 
               COALESCE(AVG(reviews.rating), 0) AS average_rating,
               SUM(reviews.rating) AS total_rating,
                COUNT(reviews.rating) AS total_reviews
        FROM recipes
        LEFT JOIN reviews ON recipes.id_recipes = reviews.recipe_id
        LEFT JOIN likes ON likes.item_id = recipes.id_recipes
        WHERE recipes.id_recipes IN ($placeholders)
        GROUP BY recipes.id_recipes
        ORDER BY likes.id DESC 
    ";

    $stmt_details = $conn->prepare($sql_details);
    $types = str_repeat('i', count($recipe_ids)); 
    $stmt_details->bind_param($types, ...$recipe_ids); 
    $stmt_details->execute();
    $result_details = $stmt_details->get_result(); 
    
    while ($recipe = $result_details->fetch_assoc()) {
        $recipes[] = $recipe; 
    }
}

$sql_books = "SELECT item_id FROM likes WHERE user_id = ? AND item_type=?";
$stmt_books = $conn->prepare($sql_books);
$item_type = "book";
$stmt_books->bind_param('is', $user_id, $item_type);
$stmt_books->execute();
$result_books = $stmt_books->get_result(); 

$book_ids = [];
while ($row = $result_books->fetch_assoc()) {
    $book_ids[] = $row['item_id'];
}

$books = [];
if (!empty($book_ids)) {
    $placeholders = implode(',', array_fill(0, count($book_ids), '?'));

    $sql_book_details = "
        SELECT books.*, 
               COALESCE(AVG(reviews.rating), 0) AS average_rating,
                SUM(reviews.rating) AS total_rating,
                COUNT(reviews.rating) AS total_reviews
        FROM books
        LEFT JOIN reviews ON books.id = reviews.id_book
        LEFT JOIN likes ON likes.item_id = books.id
        WHERE books.id IN ($placeholders)
        GROUP BY books.id
        ORDER BY likes.id DESC 
    ";
    
    $stmt_book_details = $conn->prepare($sql_book_details);
    $types = str_repeat('i', count($book_ids)); 
    $stmt_book_details->bind_param($types, ...$book_ids); 
    $stmt_book_details->execute();
    $result_book_details = $stmt_book_details->get_result(); 
    
    while ($book = $result_book_details->fetch_assoc()) {
        $books[] = $book; 
    }
}

$sql_movies = "SELECT item_id FROM likes WHERE user_id = ? AND item_type=?";
$stmt_movies = $conn->prepare($sql_movies);
$item_type = "movie";
$stmt_movies->bind_param('is', $user_id, $item_type);
$stmt_movies->execute();
$result_movies = $stmt_movies->get_result(); 

$movie_ids = [];
while ($row = $result_movies->fetch_assoc()) {
    $movie_ids[] = $row['item_id'];
}

$movies = [];
if (!empty($movie_ids)) {
    $placeholders = implode(',', array_fill(0, count($movie_ids), '?'));
    $sql_movie_details = "
        SELECT movies.*, 
               COALESCE(AVG(reviews.rating), 0) AS average_rating,
               SUM(reviews.rating) AS total_rating,
                COUNT(reviews.rating) AS total_reviews,
                categories.Name AS category_name
        FROM movies
        LEFT JOIN reviews ON movies.id_movies = reviews.movie_id
        LEFT JOIN likes ON likes.item_id=movies.id_movies
        LEFT JOIN categories ON movies.Category_id = categories.id_category
        WHERE movies.id_movies IN ($placeholders)
        GROUP BY movies.id_movies
        ORDER BY likes.id DESC  
    ";
    
    $stmt_movie_details = $conn->prepare($sql_movie_details);
    $types = str_repeat('i', count($movie_ids)); 
    $stmt_movie_details->bind_param($types, ...$movie_ids); 
    $stmt_movie_details->execute();
    $result_movie_details = $stmt_movie_details->get_result(); 
    
    while ($movie = $result_movie_details->fetch_assoc()) {
        $movies[] = $movie; 
    }
}

if (isset($_POST['item_id']) && isset($_POST['item_type'])) {
    $item_id = intval($_POST['item_id']);
    $item_type = $_POST['item_type'];
    $user_id = $_SESSION['user_id'];

    $check_like_sql = "SELECT * FROM likes WHERE user_id = ? AND item_id = ? AND item_type = ?";
    $check_stmt = $conn->prepare($check_like_sql);
    $check_stmt->bind_param('iis', $user_id, $item_id, $item_type);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $delete_like_sql = "DELETE FROM likes WHERE user_id = ? AND item_id = ? AND item_type = ?";
        $delete_stmt = $conn->prepare($delete_like_sql);
        $delete_stmt->bind_param('iis', $user_id, $item_id, $item_type);
        if ($delete_stmt->execute()) {
            $_SESSION['recent_likes'] = array_filter($_SESSION['recent_likes'], function($like) use ($item_id, $item_type) {
                return !($like['item_id'] == $item_id && $like['item_type'] == $item_type);
            });
            $_SESSION['recent_likes'] = array_values($_SESSION['recent_likes']);
            $response = [
                'success' => true,
                'recent_likes_count' => count($_SESSION['recent_likes'])
            ];
        } else {
            $response = ['success' => false, 'error' => 'Could not delete item.'];
        }
        $delete_stmt->close();
    } else {
        $response = ['success' => false, 'error' => 'Item not found in likes.'];
    }
    $check_stmt->close();
    echo json_encode($response);
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $item_type = $_POST['action']; 
    $item_id = intval($_POST['item_id']);
    $user_id = intval($_SESSION['user_id']);
    $rating = intval(substr($item_type, -1)); 

    if (strpos($item_type, 'movie') !== false) {
        $table = 'reviews';
        $column = 'movie_id';
    } elseif (strpos($item_type, 'recipe') !== false) {
        $table = 'reviews';
        $column = 'recipe_id';
    } else {
        $table = 'reviews';
        $column = 'id_book';
    }
    $check_sql = "SELECT rating FROM $table WHERE user_id = ? AND $column = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $user_id, $item_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $update_sql = "UPDATE $table SET rating = ? WHERE user_id = ? AND $column = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('iii', $rating, $user_id, $item_id);

        if ($update_stmt->execute()) {
            $avg_sql = "SELECT COALESCE(AVG(rating), 0) AS average_rating FROM $table WHERE $column = ?";
            $avg_stmt = $conn->prepare($avg_sql);
            $avg_stmt->bind_param('i', $item_id);
            $avg_stmt->execute();
            $avg_result = $avg_stmt->get_result();
            $avg_row = $avg_result->fetch_assoc();
            $new_average_rating = $avg_row['average_rating'];
            $avg_stmt->close();
            echo json_encode(['success' => true, 'new_average_rating' => $new_average_rating]);
        } else {
            echo json_encode(['success' => false, 'error' => $update_stmt->error]);
        }

        $update_stmt->close();
    } else {
        $insert_sql = "INSERT INTO $table (user_id, rating, $column) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param('iii', $user_id, $rating, $item_id);

        if ($insert_stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $insert_stmt->error]);
        }

        $insert_stmt->close();
    }

    $check_stmt->close();
    exit;
}
$userData = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT username, Image_Link FROM users WHERE ID_USER = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - MyApp</title>
    <link rel="stylesheet" href="assets/styles/favourite.css">
    <?php include 'includes/fontawesome.php'; ?> 
    <link rel="stylesheet" href="assets/styles/header_footer.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
            $('.like_delete_item').click(function(){
                var button = $(this);
                var itemId = button.data('item-id');
                var itemType = button.data('item-type');
                
                $.ajax({
                    url: 'favourite.php',
                    type: 'POST',
                    data: { item_id: itemId, item_type: itemType },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            $('.recent_likes_count').text(data.recent_likes_count);
                            $('#recent_likes_count').text(data.recent_likes_count);
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
            
            $(document).on('mousemove', function (e) {
                tooltip.css({
                    left: e.pageX + 15,
                    top: e.pageY + 15
                });
            });

            tooltip.show();
        });

        $(document).on('mouseleave', '.star-rating span', function () {
            $('.tooltip').remove();
        });

        $(document).on('click', '.star-rating span', function () {
            var star = $(this);
            var itemId = star.closest('.star-rating').data('item-id');
            var itemType = star.closest('.star-rating').data('item-type');
            var rating = star.data('rating');

            var action = 'rating_' + itemType + '_' + rating;
            
            $.ajax({
                url: 'favourite.php',
                type: 'POST',
                data: {
                    item_id: itemId,
                    action: action,
                    rating: rating
                },
                success: function (response) {
                    var data = JSON.parse(response);
                    if (data.success) {
                        console.log('Success');
                        var newRating = (isNaN(data.new_average_rating) || data.new_average_rating === 0) ? rating : parseFloat(data.new_average_rating);
                        $('p.rating-value[data-item-id="' + itemId + '"][data-item-type="' + itemType + '"]').text(newRating.toFixed(2));
                        var fullStars = Math.floor(newRating);
                        var halfStar = (newRating - fullStars >= 0.5);
                        var emptyStars = 5 - fullStars - (halfStar ? 1 : 0); 

                        var starsHtml = '';
                        for (var i = 0; i < fullStars; i++) {
                            starsHtml += '<span class="bi bi-star-fill full-star" data-rating="' + (i + 1) + '" title="Rating: ' + newRating + '"></span>';
                        }
                        if (halfStar) {
                            starsHtml += '<span class="bi bi-star-half star-half" data-rating="' + (fullStars + 1) + '" title="Rating: ' + newRating + '"></span>';
                        }
                        for (var i = 0; i < emptyStars; i++) {
                            starsHtml += '<span class="bi bi-star empty-star" data-rating="' + (fullStars + halfStar + i + 1) + '" title="Rating: ' + newRating + '"></span>';
                        }
                        $('.star-rating[data-item-id="' + itemId + '"][data-item-type="' + itemType + '"]').html(starsHtml);
                        $('.star-rating[data-item-id="' + itemId + '"][data-item-type="' + itemType + '"]').addClass('clicked');
                    } else {
                        alert('Error: ' + data.error);
                    }
                },
                error: function () {
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
<div class="favorites-container">
    <!-- Sidebar -->
    <div class="new-sidebar">
        <ul>
            <li>
                <a href="?section=recent_likes">
                    <p class="text-margin-sidemenu"><i class="fas fa-heart newsidemneu-icon"></i> Recent Likes</p>
                </a>
            </li>
            <li>
                <a href="?section=movies">
                    <p class="text-margin-sidemenu"><i class="fas fa-film newsidemneu-icon"></i> Movies</p>
                </a>
            </li>
            <li>
                <a href="?section=books">
                    <p class="text-margin-sidemenu"><i class="fas fa-book newsidemneu-icon"></i> Books</p>
                </a>
            </li>
            <li>
                <a href="?section=recipes">
                    <p class="text-margin-sidemenu"><i class="fas fa-utensils newsidemneu-icon"></i> Recipes</p>
                </a>
            </li>
        </ul>
    </div>
    <div class="content-area">
        <?php 
        $section = $_GET['section'] ?? 'recent_likes'; 
        $default_image = 'assets/fakers/no-image.jpg';
        if ($section == 'recent_likes') {
            echo '<div class="favorites-section recent-likes-section">
                  <h3>Recent Likes: <span id="recent_likes_count">' . count($_SESSION['recent_likes']) . '</span></h3>';

            if (empty($_SESSION['recent_likes'])) {
                echo '<div class="no-items-message">No elements to be displayed</div>';
            } else {
                echo '<ul class="recent-likes-list">';
                foreach (array_reverse($_SESSION['recent_likes']) as $recent) {
                    $item_id = htmlspecialchars($recent['item_id']);
                    $item_type = htmlspecialchars($recent['item_type']);
                    
                    if ($item_type === 'movie') {
                        $item = current(array_filter($movies, fn($movie) => $movie['id_movies'] == $item_id));
                        $name = $item['Title'];
                        $image = $item['image_link'] ?: $default_image;
                    } else if ($item_type === 'recipe') {
                        $item = current(array_filter($recipes, fn($recipe) => $recipe['id_recipes'] == $item_id));
                        $name = $item['title'];
                        $image = $item['image_link'] ?: $default_image;
                    } else if ($item_type === 'book') {
                        $item = current(array_filter($books, fn($book) => $book['id'] == $item_id));
                        $name = $item['title'];
                        $image = $item['image_link'] ?: $default_image;
                    }
                    
                    echo '<li class="favorite-item">
                            <img src="' . $image . '" alt="' . $name . '" class="item-image">
                            <div class="fffff"><strong>' . ucfirst($item_type) . ': </strong><p class="fffff_name">'. $name .'</p></div>
                            <button class="like_delete_item" data-item-id="' . $item_id . '" data-item-type="' . $item_type . '">
                                <i class="fas fa-trash"></i>
                            </button>
                          </li>';
                }
                echo '</ul>';
            }
            echo '</div>';
        } elseif ($section == 'movies') {
            echo '<div class="favorites-section movies-section"><h3>Movies</h3>';
            
            if (empty($movies)) {
                echo '<div class="no-items-message">No elements to be displayed</div>';
            } else {
                echo '<ul class="item-list movies-list">';
                foreach ($movies as $movie) {
                    $image = $movie['image_link'] ?: $default_image;
                    $rating = isset($movie['average_rating']) && $movie['average_rating'] > 0 ? $movie['average_rating'] : 0;
                    $fullStars = floor($rating);
                    $halfStar = ($rating - $fullStars >= 0.5);
                    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                
                    echo '<li class="favorite-item">
                            <img src="' . $image . '" alt="Movie Image" data-item-id="'.$movie['id_movies'].'" class="item-image">
                            <div class="fffff">
                                <p class="item-title">' . htmlspecialchars($movie['Title']) . '</p>
                                <p class="item-category">' . htmlspecialchars($movie['category_name']) . '</p>
                            </div>
                            <div class="average-rating">
                                <div class="star-rating" data-item-id="' . $movie['id_movies'] . '" data-item-type="movie">';

                    for ($i = 1; $i <= $fullStars; $i++) {
                        echo '<span class="bi bi-star-fill full-star" data-rating="' . $i . '" title="Rating: ' . $rating . '"></span>';
                    }
                
                    if ($halfStar) {
                        echo '<span class="bi bi-star-half star-half" data-rating="' . ($fullStars + 1) . '" title="Rating: ' . $rating . '"></span>';
                    }
                    for ($i = 0; $i < $emptyStars; $i++) {
                        echo '<span class="bi bi-star empty-star" data-rating="' . ($fullStars + $halfStar + $i + 1) . '" title="Rating: ' . $rating . '"></span>';
                    }
                
                    echo '   </div>
                            </div>
                            <button class="like_delete_item" data-item-id="' . $movie['id_movies'] . '" data-item-type="movie">
                                <i class="fas fa-trash"></i>
                            </button>
                        </li>';
                }
                
                echo '</ul>';
            }
            echo '</div>';
        } elseif ($section == 'books') {
            echo '<div class="favorites-section books-section"><h3>Books</h3>';
            
            if (empty($books)) {
                echo '<div class="no-items-message">No elements to be displayed</div>';
            } else {
                echo '<ul class="item-list books-list">';
                foreach ($books as $book) {
                    $image = $book['image_link'] ?: $default_image;
                    echo '<li class="favorite-item">
                            <img src="' . $image . '" alt="Book Image" class="item-image">
                            <div class="fffff">
                                <p class="item-title">' . htmlspecialchars($book['title']) . '</p>';
                    
                    if (!empty($book['author'])) {
                        echo '<p class="item-category">' . htmlspecialchars($book['author']) . '</p>';
                    }
                
                    echo '</div>
                            <div class="average-rating">
                                <div class="star-rating" data-item-id="' . $book['id'] . '" data-item-type="book">';
                    $rating = isset($book['average_rating']) ? $book['average_rating'] : 0;
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
                
                    echo '</div>
                            </div>
                            <button class="like_delete_item" data-item-id="' . $book['id'] . '" data-item-type="book">
                                <i class="fas fa-trash"></i>
                            </button>
                        </li>';
                }
                
                echo '</ul>';
            }
            echo '</div>';
        } elseif ($section == 'recipes') {
            echo '<div class="favorites-section recipes-section"><h3>Recipes</h3>';
            
            if (empty($recipes)) {
                echo '<div class="no-items-message">No elements to be displayed</div>';
            } else {
                echo '<ul class="item-list recipes-list">';
                foreach ($recipes as $recipe) {
                    $image = $recipe['image_link'] ?: $default_image;
                
                    echo '<li class="favorite-item">
                            <img src="' . $image . '" alt="Recipe Image" class="item-image">
                            <div class="fffff">
                                <p class="item-title">' . htmlspecialchars($recipe['title']) . '</p>';
                
                    if (!empty($recipe['created_at'])) {
                        echo '<p class="item-category created_at_item">' . htmlspecialchars($recipe['created_at']) . '</p>';
                    }
                
                    echo '</div>
                            <div class="average-rating">
                                <div class="star-rating" data-item-id="' . $recipe['id_recipes'] . '" data-item-type="recipe">';
                
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
                
                    echo '</div>
                            </div>
                            <button class="like_delete_item" data-item-id="' . $recipe['id_recipes'] . '" data-item-type="recipe">
                                <i class="fas fa-trash"></i>
                            </button>
                        </li>';
                }
                
                    echo '</ul>';
            }
            echo '</div>';
        }
        echo '<div id="image-modal" class="modal-overlay hidden">
    <div class="modal-content">
        <img id="modal-image" src="" alt="Preview Image">
    </div>
</div>';
        ?>
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
<script src="assets/js/favourite.js"></script>
</body>
</html>
