<?php
require_once __DIR__ . '/config.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        flash('danger', 'Μη έγκυρο token.');
        header('Location: players.php');
        exit;
    }

    $name     = trim($_POST['name'] ?? '');
    $position = $_POST['position'] ?? '';
    $teamId   = (int) ($_POST['team_id'] ?? 0);

    $errors = [];
    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Το όνομα πρέπει να έχει 1–100 χαρακτήρες.';
    }
    if (!in_array($position, ['GK', 'DEF', 'MID', 'FWD'], true)) {
        $errors[] = 'Μη έγκυρη θέση.';
    }
    if ($teamId <= 0) {
        $errors[] = 'Πρέπει να επιλέξετε ομάδα.';
    } else {
        $check = $db->prepare("SELECT COUNT(*) FROM teams WHERE id = ?");
        $check->execute([$teamId]);
        if ((int) $check->fetchColumn() === 0) {
            $errors[] = 'Η ομάδα δεν βρέθηκε.';
        }
    }

    $photoPath = null;
    if (!empty($_FILES['photo']['name'])) {
        $up = uploadImage($_FILES['photo'], 'players');
        if (isset($up['error'])) {
            $errors[] = $up['error'];
        } else {
            $photoPath = $up['path'];
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare(
                "INSERT INTO players (name, position, team_id, photo_path) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$name, $position, $teamId, $photoPath]);
            flash('success', 'Ο παίκτης αποθηκεύτηκε.');
        } catch (PDOException $e) {
            flash('danger', 'Σφάλμα αποθήκευσης.');
            error_log($e->getMessage());
        }
    } else {
        foreach ($errors as $err) flash('danger', $err);
    }

    header('Location: players.php');
    exit;
}

$teams   = $db->query("SELECT id, name FROM teams ORDER BY name")->fetchAll();
$players = $db->query("
    SELECT p.*, t.name AS team_name
    FROM players p
    JOIN teams t ON t.id = p.team_id
    ORDER BY t.name, p.name
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h2>Διαχείριση Παικτών</h2>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Νέος Παίκτης</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <div class="mb-2">
                        <label class="form-label">Όνομα Παίκτη</label>
                        <input type="text" name="name" class="form-control" maxlength="100" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Θέση</label>
                        <select name="position" class="form-select" required>
                            <option value="GK">Τερματοφύλακας</option>
                            <option value="DEF">Αμυντικός</option>
                            <option value="MID">Μέσος</option>
                            <option value="FWD">Επιθετικός</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Ομάδα</label>
                        <select name="team_id" class="form-select" required>
                            <option value="">— επιλέξτε —</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($teams)): ?>
                            <small class="text-warning">Δεν υπάρχουν ομάδες — δημιουργήστε πρώτα μία.</small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Φωτογραφία</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <small class="text-muted">JPG/PNG/GIF/WebP, μέγιστο 2&nbsp;MB.</small>
                    </div>
                    <button type="submit" class="btn btn-primary" <?= empty($teams) ? 'disabled' : '' ?>>Προσθήκη</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Φωτογραφία</th>
                    <th>Όνομα</th>
                    <th>Θέση</th>
                    <th>Ομάδα</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($players)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Δεν υπάρχουν ακόμα παίκτες.</td></tr>
                <?php else: foreach ($players as $p): ?>
                <tr>
                    <td>
                        <?php if ($p['photo_path']): ?>
                            <img src="<?= e($p['photo_path']) ?>" alt="" class="player-photo">
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($p['name']) ?></td>
                    <td><?= e(positionLabel($p['position'])) ?></td>
                    <td><?= e($p['team_name']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
