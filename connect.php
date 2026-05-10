<?php
    $connection = new mysqli('localhost', 'root', '', 'dbcit_stickerapp');

    if(!$connection){
        die(mysqli_error($mysqli));
    }

?>