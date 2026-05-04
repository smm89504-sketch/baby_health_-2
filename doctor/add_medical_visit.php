<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit;
}
$child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;
header('Location: ../medical_visits.php?child_id=' . $child_id);
exit;
