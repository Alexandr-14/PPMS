<?php
$host = "localhost";
$user = "myppmsco_ppms";
$pass = "cM@IjCvdBAe%EcGi";
$db   = "myppmsco_ppms";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>