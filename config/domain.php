<?php
// config/domain.php

function startKrazeSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function getKrazeBaseDomain() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = preg_replace('/:\d+$/', '', $host);
    return $host;
}
