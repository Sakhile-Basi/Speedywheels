<?php
declare(strict_types=1);

function check_login_error(){
    if (isset($_SESSION["error_login"])){
        $error = $_SESSION["error_login"];

        echo "<br>";
        foreach($error as $err){
            echo '<div class="alert alert-danger text-center" role="alert">' . htmlspecialchars($err) . '</div>';
        }
        unset($_SESSION["error_login"]);
    }

    else if (isset($_GET['login']) && $_GET['login'] === 'success'){

        echo "<br>";
        echo '<div class="alert alert-success text-center" role="alert">You have successfully logged in!</div>';
    }
}