<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit;
}
$child_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$child_id) {
    header('Location: ../children.php');
    exit;
}
header('Location: ../child_details.php?id=' . $child_id);
exit;
