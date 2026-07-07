<?php

$page_title = "Registration";

include 'includes/header.php';

include 'includes/navbar.php';

if (isset ($_SESSION['username'])){
	echo "Logged in with name '" . $_SESSION['username'] . "'. You can <a href='/logout.php'>logout</a>";
}
else{

?>
<section id="hero" class="hero section">
    <div class="hero-bg">
      <img src="/assets/img/Picture1.jpg" alt="">
    </div>
        <div class="container-fluid loginouter">
            <div class="card bg-light mb-3">
                <div class="card-body">
    <form action="/check_registration.php" method="POST">
        Registration username:<br>
        <input type="text" name="username"><br>
        Registration password:<br>
        <input type="password" name="pass"><br><br>
        <input type="submit" class="btn btn-outline-primary" value="Submit">
    </form>
</div>
</div>
    </div> <!-- end of <div class="container-left"> -->
</div>

</section><!-- /Hero Section -->
<?php

include 'includes/footer.html';

}

?>