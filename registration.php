<?php

$page_title = "Registration";

include 'includes/header.php';

include 'includes/navbar.php';

if (isset ($_SESSION['username'])){
	echo "Logged in with name '" . $_SESSION['username'] . "'. You can <a href='/logout.php'>logout</a>";
}
else{
    include 'includes/registration.html';

?>


<?php

include 'includes/footer.html';

}

?>