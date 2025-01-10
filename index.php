<?php
session_start();
if (!isset($_SESSION['recent_likes'])) {
    $_SESSION['recent_likes'] = []; 
}
include 'includes/db.php'; 

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
    <?php include 'includes/fontawesome.php'; ?> 
    <link rel="stylesheet" href="assets/styles/header_footer.css">
    <link rel="stylesheet" href="assets/styles/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Cormorant+Garamond:wght@400;500&display=swap" rel="stylesheet">

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
    <div class="top_content">
        <div class="homepage_first">
            <div class="title_homepage">
                <h1 class="title">Save Your Favourite Things</h1>
            </div>
        </div>
        <section class="grow-section">
            <div class="icon-container">
                <div class="icon-box">
                    <i class="fa-solid fa-film"></i>
                    <h3>Movies</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur elit.</p>
                </div>
                <div class="icon-box">
                    <i class="fa-solid fa-book"></i>
                    <h3>Books</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur elit.</p>
                </div>
                <div class="icon-box">
                    <i class="fa-solid fa-utensils"></i>
                    <h3>Recipes</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur elit.</p>
                </div>
                <div class="icon-box">
                    <i class="fa-solid fa-bullseye"></i>
                    <h3>Goals</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur elit.</p>
                </div>
            </div>
            <div class="image1_homepage">
                <img src="assets/images/book2.png" alt="image start" class="image1">
            </div>
        </section>
    </div>
    <div class="goal_content">
        <div class="header_middle">Goal of Project</div>
        <div class="content_middle">
            <div id="plusToDot1" class="plus-to-dot"></div>
            <div id="plusToDot2" class="plus-to-dot"></div>
            <div id="plusToDot3" class="plus-to-dot"></div>
            <div id="plusToDot4" class="plus-to-dot"></div>
            <div id="goal1" class="goal " >
                <h3> Organize Interests</h3>
                <p>
                    Provide a platform for users to save and organize their favorite things across different categories such as movies, books, recipes, and personal goals.
                </p>
            </div>
            <div id="goal2" class="goal ">
                <h3>Simplify Tracking</h3>
                <p>
                    Simplify the process of keeping track of personal interests, making it easy to update and access information.
                </p>
            </div>
            <div id="goal3" class="goal">
                <h3>Aesthetic Appeal</h3>
                <p>
                    Offer an aesthetically pleasing and user-friendly interface to enhance the user experience.
                </p>
            </div>
        </div>
        <section class="journey-of-creation">
            <h2>Journey of Creation</h2>
            <p>
                Every great project begins with a vision. Here's how this platform came to life in just three months.
            </p>
            <div class="timeline-container">
                <div class="timeline-item">
                    <div class="timeline-date">Month 1</div>
                    <div class="timeline-content">
                        <h3>The Vision</h3>
                        <p>It all started with a dream to create a seamless platform for users to organize and save their favorite things effortlessly.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-date">Month 2</div>
                    <div class="timeline-content">
                        <h3>Bringing it to Life</h3>
                        <p>From sketching designs to writing code, every detail of the user experience was meticulously crafted during this phase.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-date">Month 3</div>
                    <div class="timeline-content">
                        <h3>The Launch</h3>
                        <p>After countless hours of refining, the platform was unveiled to the world, ready to empower users with its intuitive features.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="overview">
            <h2>Why This Project?</h2>
            <p>
                In today's fast-paced world, keeping track of your favorite things—whether it’s movies, books, recipes, or personal goals—can be overwhelming. This platform is designed to help you organize and access your interests effortlessly, all in one place.
            </p>
        </section>
        <section class="cta">
            <h2>Ready to Get Started?</h2>
            <p>Sign up today and start organizing your favorite things effortlessly.</p>
            <?php if ($userData): ?>
                <a href="books.php">Get started</a>
            <?php else: ?>
                <a href="login.php">Login<i class="fa-solid fa-arrow-right-to-bracket"></i></a>
            <?php endif; ?>
        </section>

        <section class="how-it-works">
            <h2>How It Works</h2>
            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Create an Account</h3>
                    <p>Sign up quickly and start your journey to better organization.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Add Your Favorites</h3>
                    <p>Easily add movies, books, recipes, or goals to your personalized list.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Stay Organized</h3>
                    <p>Access and update your collections anytime, anywhere.</p>
                </div>
            </div>
        </section>
        <section class="did-you-know">
            <h2>Did You Know?</h2>
            <p>Most people struggle to remember their favorite books, movies, or recipes because they lack a single place to store them. This platform changes that!</p>
        </section>



        <div class="middle_content">
            <div id="arrow">
                <img src="assets/images/telegram.png" alt="Telegram">
            </div>
            <svg fill="none" xmlns="http://www.w3.org/2000/svg" width="100%" height="auto" viewBox="0 0 1000 2800">
                <!-- Gradient Definition -->
                <defs>
                    <linearGradient id="pathGradient" x1="0%" y1="0%" x2="100%" y2="0%" r="50%">
                        <stop offset="0%" style="stop-color: #000000; stop-opacity: 1" />
                        <stop offset="50%" style="stop-color: #666666; stop-opacity: 1" />
                        <stop offset="100%" style="stop-color: #000000; stop-opacity: 1" />
                    </linearGradient>
                </defs>

                <!-- Path with Gradient -->
                <path id="path" 
                    d="M-200,-200
                    C200,200 205,255 355,255  
                    C575,255 625,200 650,170
                    C700,100 660,90 660,90 
                    C570,60 530,220 900,220
                    C900,220 1200,200 1300,220

                    C250,1100 700,1150 200,1500
                    C200,1500 0,1700 400,1800
                    C400,1800 800,1900 1300,2000
                    C1300,2300 600,2400 580,2500
                    C600,2500 400,2700 -150,2700" 
                    stroke="url(#pathGradient)" stroke-width="20" fill="none" />
            </svg>
        </div>

    </div>
    <div class="goal_content_mobile">
        <div class="header_middle_mobile">Goal of Project</div>
        <div class="content_middle_mobile">
            <div class="goal_mobile">
                <h3>Goal 1: Organize Interests</h3>
                <p>
                    Provide a platform for users to save and organize their favorite things across different categories such as movies, books, recipes, and personal goals.
                </p>
            </div>
            <div class="goal_mobile">
                <h3>Goal 2: Simplify Tracking</h3>
                <p>
                    Simplify the process of keeping track of personal interests, making it easy to update and access information.
                </p>
            </div>
            <div  class="goal_mobile">
                <h3>Goal 3: Aesthetic Appeal</h3>
                <p>
                    Offer an aesthetically pleasing and user-friendly interface to enhance the user experience.
                </p>
            </div>
        </div>
    </div>
    <section class="mobile-journey-of-creation">
        <div class="mobile-journey-of-creation-div">
            <h2>Journey of Creation</h2>
            <p>
                Every great project begins with a vision. Here's how this platform came to life in just three months.
            </p>
            <div class="mobile-timeline-container">
                <div class="mobile-timeline-item">
                    <div class="mobile-timeline-date">Month 1</div>
                    <div class="mobile-timeline-content">
                        <h3>The Vision</h3>
                        <p>It all started with a dream to create a seamless platform for users to organize and save their favorite things effortlessly.</p>
                    </div>
                </div>
                <div class="mobile-timeline-item">
                    <div class="mobile-timeline-date">Month 2</div>
                    <div class="mobile-timeline-content">
                        <h3>Bringing it to Life</h3>
                        <p>From sketching designs to writing code, every detail of the user experience was meticulously crafted during this phase.</p>
                    </div>
                </div>
                <div class="mobile-timeline-item">
                    <div class="mobile-timeline-date">Month 3</div>
                    <div class="mobile-timeline-content">
                        <h3>The Launch</h3>
                        <p>After countless hours of refining, the platform was unveiled to the world, ready to empower users with its intuitive features.</p>
                    </div>
                </div>
            </div>
        </div>
        
    </section>
    <div class="why">
        <section class="overview_mobile">
            <h2>Why This Project?</h2>
            <p>
                In today's fast-paced world, keeping track of your favorite things—whether it’s movies, books, recipes, or personal goals—can be overwhelming. This platform is designed to help you organize and access your interests effortlessly, all in one place.
            </p>
        </section>
    </div>
    <div class="cta_mobile">
        <h2>Ready to Get Started?</h2>
        <p>Sign up today and start organizing your favorite things effortlessly.</p>
        <?php if ($userData): ?>
            <a href="books.php" class="cta_button_mobile">Get started</a>
        <?php else: ?>
            <a href="login.php" class="cta_button_mobile">Login<i class="fa-solid fa-arrow-right-to-bracket"></i></a>
        <?php endif; ?>
    </div>

    <div class="how-it-works_mobile">
        <h2>How It Works</h2>
        <div class="steps-container_mobile">
            <div class="step_mobile">
                <div class="step-number_mobile">1</div>
                <h3>Create an Account</h3>
                <p>Sign up quickly and start your journey to better organization.</p>
            </div>
            <div class="step_mobile">
                <div class="step-number_mobile">2</div>
                <h3>Add Your Favorites</h3>
                <p>Easily add movies, books, recipes, or goals to your personalized list.</p>
            </div>
            <div class="step_mobile">
                <div class="step-number_mobile">3</div>
                <h3>Stay Organized</h3>
                <p>Access and update your collections anytime, anywhere.</p>
            </div>
        </div>
    </div>

    <div class="faq-container">
        <h2>Frequently Asked Questions</h2>
        <div class="faq-item">
            <button class="faq-question">What is your refund policy?</button>
            <div class="faq-answer">
            <p>We offer a full refund within 30 days of purchase. Conditions apply.</p>
            </div>
        </div>
        <div class="faq-item">
            <button class="faq-question">How can I track my order?</button>
            <div class="faq-answer">
            <p>You can track your order using the tracking link in your confirmation email.</p>
            </div>
        </div>
        <div class="faq-item">
            <button class="faq-question">Do you ship internationally?</button>
            <div class="faq-answer">
            <p>Yes, we ship to over 50 countries worldwide. Shipping times vary by region.</p>
            </div>
        </div>
    </div>

    




    <footer class="fixed_footer">
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
                <a href="https://www.facebook.com" target="_blank" class="social-icon"><i class="fab fa-facebook"></i></a>
                <a href="https://www.linkedin.com" target="_blank" class="social-icon"><i class="fab fa-linkedin"></i></a>
                <a href="https://www.instagram.com" target="_blank" class="social-icon"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
	<script src="https://cdn.jsdelivr.net/gh/studio-freight/lenis@1.0.0/bundled/lenis.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/MotionPathPlugin.min.js"></script>
    <script src="assets/js/header_footer.js"></script>
    <script src="assets/js/index.js"></script>
</body>
</html>
