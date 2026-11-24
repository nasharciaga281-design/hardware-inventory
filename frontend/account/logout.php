<?php

require_once __DIR__ . '/../../backend/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

auth_require_csrf();
auth_logout_session();
header('Location: login.php');
exit;
