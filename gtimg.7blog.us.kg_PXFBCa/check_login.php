<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');
echo json_encode(['logged_in' => isLoggedIn()]); 