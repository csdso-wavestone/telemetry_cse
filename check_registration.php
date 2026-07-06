<?php

$page_title = "Check registration";

include 'mysqli_connect.php';

include 'includes/header.php';

include 'includes/navbar.php';

if (isset ($_SESSION['username'])){
	header('location: index.php');
}
else{
	
    $username = $_POST['username'];
    $password = $_POST['pass'];


    $sql1 = "SELECT users_username, users_password FROM users WHERE users_username = '{$username}' AND users_password = '{$password}'";
    $sql2 = "INSERT INTO users (users_username, users_password) VALUES ('$username', '$password')";

    $result1 = mysqli_query ($connection, $sql1) or die (mysqli_error ($connection));

    if (mysqli_num_rows ($result1) == 0){
        if (mysqli_query ($connection, $sql2)){
            include 'includes/new_registration.php';
        }
        else{
            include 'includes/error.php';
            
        }

    }
    else{
        include 'includes/notregistered.php';
    }
}

mysqli_close ($connection);

include 'includes/footer.html';

?>