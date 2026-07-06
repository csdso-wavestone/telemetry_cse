<?php

$page_title = 'Login';

include 'includes/header.php';

include 'includes/navbar.php';

if (isset ($_SESSION['username'])){
	echo "You are logged! You can " . "<a href='logout.php'>" . "logout" . "</a>";
}
else{
    include 'includes/login.php';
?>


<?php
}

include 'includes/footer.html';



?>

