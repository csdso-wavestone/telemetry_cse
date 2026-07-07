<?php

$page_title = 'Login';

include 'includes/header.php';

include 'includes/navbar.php';

if (isset ($_SESSION['username'])){
	echo "You are already logged in as ". $_SESSION['username'] ."! You can " . "<a href='logout.php'>" . "logout" . "</a>";
}
else{
    include 'mysqli_connect.php';

    if (isset($_POST['username']) && isset($_POST['pass'])) {
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
            $error = "Username or password incorrect. Please try again.";
        }
    }

    mysqli_close($connection);
?>
<section id="hero" class="hero section">
    <div class="hero-bg">
      <img src="/assets/img/Picture1.jpg" alt="">
    </div>
        <div class="container-fluid loginouter">
            <div class="card bg-light mb-3">
                <div class="card-body">
                <?php
                if (isset($error)) {
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php 
                        echo $error;
                    ?>
                </div>
                <?php
                }
                ?>
            <form action="/login.php" method="POST">
                Username:<br>
                <input type="text" name="username"><br>
                Password:<br>
                <input type="password" name="pass"><br><br>
                <input type="submit" class="btn btn-outline-primary" value="Submit">
            </form>
        </div>
        </div>
            </div> <!-- end of <div class="container-left"> -->
        </div>
  
</section><!-- /Hero Section -->
<?php
}

include 'includes/footer.html';
?>

