<?php
// front-controller.php

$request = $_SERVER['REQUEST_URI'];

// Remove query string and leading/trailing slashes
$request = trim(preg_replace('/\?.*/', '', $request), '/');
$request = ltrim($request, '/');
$request = rtrim($request, '/');

// Map the request to the appropriate controller
switch ($request) {
    case '':
        require 'index.php';
        break;
    case 'profile':
        require 'profile.php';
        break;
    case 'useraccess':
        require 'useraccess.php';
        break;
    case 'view-only':
        require 'view-only.php';
        break;
    case 'file-view-only':
        require 'file-view-only.php';
        break;
    case 'register':
        require 'register.php';
        break;
    case 'update-details':
        require 'update-details.php';
        break;
    case 'logout':
        require 'logout.php';
        break;
    case 'action':
        require 'action.php';
        break;
    case 'new-password':
        require 'new-password.php';
        break;
    case 'verify-otp':
        require 'verify-otp.php';
        break;
    case 'logout':
        require 'logout.php';
        break;
    case 'forget-password-otp':
        require 'forget-password-otp.php';
        break;
}
