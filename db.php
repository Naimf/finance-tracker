<?php
$host = "localhost";
$dbname = "finance_tracker";  // your database name
$username = "root";           // XAMPP default
$password = "";               // XAMPP default (no password)

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
