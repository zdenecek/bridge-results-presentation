<?php

$config = include(__DIR__ . '/config.php');
$config = $config['db'];

$host = $config['host'];
$dbname = $config['dbname'];
$username = $config['username'];
$password = $config['password'];

// Connect to the database using PDO
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

// Set PDO error mode to exception
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

return $pdo;

