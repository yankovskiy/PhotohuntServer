<?php
require_once '../include/db.php';

$db = new Database();
try {
    $db->connect();
    $db->todayUpdate();
} catch (PDOException $e) {
    echo "Exception: $e";
}