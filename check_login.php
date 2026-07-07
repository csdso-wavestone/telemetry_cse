<?php

$page_title = 'Check login';

include 'includes/header.php';
include 'includes/navbar.php';

include 'mysqli_connect.php';


if (isset ($_SESSION['username'])){
	header ('location: index.php');
}
else{
    $username = $_POST['username'];
    $password = $_POST['pass'];

    $sql1 = "SELECT users_username, users_password FROM users WHERE users_username = '{$username}' AND users_password = '{$password}'";

    $result = mysqli_query ($connection, $sql1) or die (mysqli_error ($connection));

    if (mysqli_num_rows ($result) > 0){
        $row = mysqli_fetch_assoc($result);
        $_SESSION['username'] = $row['users_username'];
        header('Location: index.php');
        exit;
    }
    else{
?>
<!-- Hero Section -->
<section id="hero" class="hero section">
    <div class="hero-bg">
        <img src="/assets/img/Picture1.jpg" alt="">
    </div>
    <div class="container text-center">
        <div class="d-flex flex-column justify-content-center align-items-center">
            <?php
            echo "Username or password incorrect. Please try again " . "<a href='/login.php'>here.</a>";
            ?>        
        </div>
    </div>
</section><!-- /Hero Section -->
<?php
    }
}

mysqli_close($connection);

include 'includes/footer.html';
?>