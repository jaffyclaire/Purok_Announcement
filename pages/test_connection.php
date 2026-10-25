<?php
require_once __DIR__ . '/../config/Database.php';

$database = new Database();
$db = $database->connect();

if ($db) {
    echo "Connected to the database successfully!";
} else {
    echo "Connection failed.";
}