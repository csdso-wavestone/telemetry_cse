
    <!-- Hero Section -->
    <section id="hero" class="hero section">
        <div class="hero-bg">
          <img src="<?php echo (isset($base_url)) ? $base_url : '.'; ?>/assets/img/Picture1.jpg" alt="">
        </div>
        <div class="container text-center">
          <div class="d-flex flex-column justify-content-center align-items-center">

<?php
echo "You are logged in with name '" . $row['users_username'] . "', please go to " . '<a href="dashboard/dashboard.php">Dashboard</a>';

?>          </div>
</div>

</section><!-- /Hero Section -->