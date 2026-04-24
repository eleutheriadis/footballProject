<?php require_once 'config.php'; ?>
<?php require_once 'includes/header.php'; ?>

<div class="row mt-5">
    <div class="col-12 text-center">
        <h1>Καλώς ήρθατε στο Σύστημα Διαχείρισης Πρωταθλήματος</h1>
        <p>Αυτή είναι η εργασία μας για τη διαχείριση ποδοσφαιρικών ομάδων και πρωταθλημάτων.</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h3><a href="teams.php">Ομάδες</a></h3>
                <p>Διαχείριση ομάδων</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h3><a href="players.php">Παίκτες</a></h3>
                <p>Διαχείριση παικτών</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h3><a href="championships.php">Πρωταθλήματα</a></h3>
                <p>Δημιουργία πρωταθλήματος</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h3><a href="draw.php">Κλήρωση</a></h3>
                <p>Πρόγραμμα αγώνων</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>