<?php

$page_title = "Home";

include 'includes/header.php';

include 'includes/navbar.php';

if (isset ($_SESSION ['username'])){
	echo "Logged in with name '" . $_SESSION['username'] . "'. You can <a href='logout.php'>logout</a>";
}
else{

  include 'includes/welcome_unauthenticated.html';
?>




<?php

}

include 'includes/footer.html';
?>