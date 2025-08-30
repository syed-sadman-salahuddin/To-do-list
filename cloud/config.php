<?php


$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "admin@123"; 
$DB_NAME = "blogdb";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
