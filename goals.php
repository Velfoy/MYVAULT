<?php
session_start();
include 'includes/functions.php';
include 'includes/db.php';
check_login();

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

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filterQuery = '';

if ($filter === 'in_progress') {
    $filterQuery = "AND is_completed = 0";
} elseif ($filter === 'completed') {
    $filterQuery = "AND is_completed = 1";
}

$sql = "SELECT * FROM goals WHERE user_id = ? $filterQuery ORDER BY id_goals DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_goal') {
    $goalText = trim($_POST['goal_text']);

    if (!empty($goalText)) {
        $sql = "INSERT INTO goals (user_id, text, is_completed, visibility) VALUES (?, ?, 0, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('is', $_SESSION['user_id'], $goalText);

        if ($stmt->execute()) {
            header("Location: goals.php?filter=" . $filter);
            exit();
        } else {
            echo "Error adding goal: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "<p>Please enter a goal.</p>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_goals') {
    $sql_update_all = "UPDATE goals SET is_completed = 0 WHERE user_id = ?";
    $stmt_update_all = $conn->prepare($sql_update_all);
    $stmt_update_all->bind_param('i', $_SESSION['user_id']);
    $stmt_update_all->execute();
    $stmt_update_all->close();

    if (isset($_POST['goals'])) {
        foreach ($_POST['goals'] as $goalId) {
            $sql_update = "UPDATE goals SET is_completed = 1 WHERE id_goals = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param('i', $goalId);
            $stmt_update->execute();
            $stmt_update->close();
        }
    }
    header("Location: goals.php?filter=" . $filter);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_goal') {
    $goalId = isset($_POST['goal_id']) ? (int)$_POST['goal_id'] : 0;

    if ($goalId) {
        $sql_check_goal = "SELECT id_goals FROM goals WHERE id_goals = ? AND user_id = ?";
        $stmt_check_goal = $conn->prepare($sql_check_goal);
        $stmt_check_goal->bind_param('ii', $goalId, $_SESSION['user_id']);
        $stmt_check_goal->execute();
        $result_check_goal = $stmt_check_goal->get_result();

        if ($result_check_goal->num_rows > 0) {
            $sql_delete = "DELETE FROM goals WHERE id_goals = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param('i', $goalId);
            
            if ($stmt_delete->execute()) {
                echo "success";
            } else {
                echo "error";
            }
            $stmt_delete->close();
        } else {
            echo "error";
        }

        $stmt_check_goal->close();
    } else {
        echo "error";
    }

    exit();
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goals</title>
    <link rel="stylesheet" href="assets/styles/goals.css">
    <?php include 'includes/fontawesome.php'; ?> 
    <link rel="stylesheet" href="assets/styles/header_footer.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $(".delete-goal").click(function() {
                var goalId = $(this).data("goal-id");
                if (confirm("Are you sure you want to delete this goal?")) {
                    $.ajax({
                        url: 'goals.php',  
                        type: 'POST',
                        data: {
                            action: 'delete_goal',
                            goal_id: goalId
                        },
                        success: function(response) {
                            if (response === "success") {
                                $("#goal-" + goalId).fadeOut(400, function() {
                                    $(this).remove(); 
                                });
                            } else {
                                alert("Error deleting goal");
                            }
                        },
                        error: function() {
                            alert("There was an error with the request. Please try again.");
                        }
                    });
                }
            });
            $('.filter-icon').click(function() {
                $('#filterModal').addClass('open');
                $('body').addClass('modal-open');
            });

            $('.close-modal').click(function() {
                $('#filterModal').removeClass('open');
                $('body').removeClass('modal-open');
            });
            $(document).click(function(event) {
                if (!$(event.target).closest('.filter-modal-content').length && !$(event.target).closest('.filter-icon').length) {
                    $('#filterModal').removeClass('open');
                    $('body').removeClass('modal-open');
                }
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
    <div class="main-container">
        <div class="goals-container">
            <h2>Your Goals</h2>

            <form method="POST" action="goals.php" class="goals-form">
                <input type="hidden" name="action" value="add_goal">
                <label for="goal_text">New Goal:</label>
                <input type="text" name="goal_text" required>
                <button type="submit" class="btn">Add Goal</button>
            </form>
            <div class="goals-header">
                <h3>Goals: 
                    <?php 
                        if ($filter === 'in_progress') {
                            echo 'In Progress';
                        } elseif ($filter === 'completed') {
                            echo 'Completed';
                        } else {
                            echo 'All Goals';
                        }
                    ?>
                </h3>
                <span class="filter-icon">
                    <i class="fas fa-filter"></i>
                </span>
            </div>
            <div id="filterModal" class="filter-modal">
                <div class="filter-modal-content">
                    <span class="close-modal">
                        <i class="fas fa-times"></i>
                    </span>

                    <h3>Choose Filter Option:</h3>
                    <div class="filter-buttons">
                        <a href="?filter=all" class="filter-btn <?php echo ($filter === 'all') ? 'active' : ''; ?>">
                            <i class="fas fa-th-list"></i> All Goals
                        </a>
                        <a href="?filter=in_progress" class="filter-btn <?php echo ($filter === 'in_progress') ? 'active' : ''; ?>">
                            <i class="fas fa-spinner"></i> In Progress
                        </a>
                        <a href="?filter=completed" class="filter-btn <?php echo ($filter === 'completed') ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i> Completed
                        </a>
                    </div>
                </div>
            </div>
            <form method="POST" action="goals.php" class="goals-form">
                <input type="hidden" name="action" value="update_goals">
                <?php if ($result->num_rows > 0): ?>
                    <ul class="goals-list">
                        <?php while ($goal = $result->fetch_assoc()): ?>
                            <li id="goal-<?php echo $goal['id_goals']; ?>">
                                <input type="checkbox" name="goals[]" value="<?php echo $goal['id_goals']; ?>" <?php echo $goal['is_completed'] ? 'checked' : ''; ?>>
                                <div class="goal_divv">
                                     <?php echo htmlspecialchars($goal['text']); ?> - <?php echo $goal['is_completed'] ? 'Completed' : 'In Progress'; ?>
                                    <button class="delete-goal " data-goal-id="<?php echo $goal['id_goals']; ?>"><i class="fa-solid fa-trash"></i></button>
                                </div>
                               
                            </li>
                        <?php endwhile; ?>
                    </ul>
                    <button type="submit" class="btn">Update Goals</button>
                <?php else: ?>
                    <p class="no-goals-message">There are no goals to display</p>
                <?php endif; ?>
            </form>
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
