<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Εργασία Πρωταθλήματος</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">Ποδοσφαιρικό Πρωτάθλημα</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="teams.php">Ομάδες</a></li>
                <li class="nav-item"><a class="nav-link" href="players.php">Παίκτες</a></li>
                <li class="nav-item"><a class="nav-link" href="championships.php">Πρωταθλήματα</a></li>
                <li class="nav-item"><a class="nav-link" href="draw.php">Κλήρωση</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
