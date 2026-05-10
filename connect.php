<?php

$connection = new mysqli('localhost', 'root', '', 'dbcit_stickerapp');

if ($connection->connect_errno) {
    die('Database connection failed: ' . htmlspecialchars($connection->connect_error));
}

$connection->set_charset('utf8mb4');