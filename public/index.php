<?php
require_once __DIR__ . '/config.php';

$db = getDB();
$counts = [
    'teams'         => (int) $db->query("SELECT COUNT(*) FROM teams")->fetchColumn(),
    'players'       => (int) $db->query("SELECT COUNT(*) FROM players")->fetchColumn(),
    'championships' => (int) $db->query("SELECT COUNT(*) FROM championships")->fetchColumn(),
    'matches'       => (int) $db->query("SELECT COUNT(*) FROM matches")->fetchColumn(),
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="row mt-3">
    <div class="col-12 text-center">
        <h1>Καλώς ήρθατε στο Σύστημα Διαχείρισης Πρωταθλήματος</h1>
        <p class="lead text-muted">Διαχείριση ποδοσφαιρικών ομάδων, παικτών και πρωταθλημάτων.</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h3><a href="teams.php">Ομάδες</a></h3>
                <p class="text-muted mb-0"><?= $counts['teams'] ?> καταχωρημένες</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h3><a href="players.php">Παίκτες</a></h3>
                <p class="text-muted mb-0"><?= $counts['players'] ?> καταχωρημένοι</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h3><a href="championships.php">Πρωταθλήματα</a></h3>
                <p class="text-muted mb-0"><?= $counts['championships'] ?> καταχωρημένα</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h3><a href="draw.php">Κλήρωση</a></h3>
                <p class="text-muted mb-0"><?= $counts['matches'] ?> αγώνες</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
