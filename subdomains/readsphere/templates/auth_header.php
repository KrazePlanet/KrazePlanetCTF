<?php
/**
 * En-tête spécifique pour les pages d'authentification
 */
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReadSphere - <?= $page_title ?? 'Authentification' ?></title>
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= url('public/assets/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('public/assets/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= url('public/assets/favicon/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= url('public/assets/favicon/site.webmanifest') ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8fafc;
            background-image: url('<?= url('public/assets/images/bg.avif') ?>');
            background-size: 300px;
            background-position: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            background-color: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(1px);
        }
        .auth-container {
            width: 100%;
            max-width: 28rem;
            margin: 0 auto;
        } 
        .auth-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(2px);
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .auth-header {
            padding: 2rem 2rem 0;
            text-align: center;
        }
        .auth-logo {
            height: 75px;
            margin-bottom: 1rem;
        }
        .auth-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
        }
        .auth-body {
            padding: 1.5rem 2rem 2rem;
        }
    </style>
</head>
<body>
    <div class="auth-container" style="position: relative; z-index: 1;">
        <div class="auth-card">
            <div class="auth-header">
                <img src="<?= url('public/assets/logo/logo_long.png') ?>" alt="ReadSphere Logo" class="auth-logo mx-auto">
                <h1 class="auth-title"><?= $page_title ?? '' ?></h1>
            </div>
            <div class="auth-body">
