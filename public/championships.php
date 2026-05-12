<?php
require_once __DIR__ . '/config.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        flash('danger', 'Μη έγκυρο token.');
        header('Location: championships.php');
        exit;
    }

    $name    = trim($_POST['champ_name'] ?? '');
    $season  = trim($_POST['season'] ?? '');
    $teamIds = array_map('intval', $_POST['team_ids'] ?? []);
    $teamIds = array_values(array_unique(array_filter($teamIds, fn($id) => $id > 0)));

    $errors = [];
    if ($name === '' || mb_strlen($name) > 150) {
        $errors[] = 'Το όνομα πρέπει να έχει 1–150 χαρακτήρες.';
    }
    if ($season === '' || mb_strlen($season) > 20) {
        $errors[] = 'Η σεζόν πρέπει να έχει 1–20 χαρακτήρες (π.χ. 2025-26).';
    }
    if (count($teamIds) < 2) {
        $errors[] = 'Πρέπει να επιλέξετε τουλάχιστον 2 ομάδες.';
    } elseif (count($teamIds) % 2 !== 0) {
        $errors[] = 'Ο αριθμός ομάδων πρέπει να είναι ζυγός.';
    }

    if (empty($errors) && !empty($teamIds)) {
        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
        $stmt = $db->prepare("SELECT COUNT(*) FROM teams WHERE id IN ($placeholders)");
        $stmt->execute($teamIds);
        if ((int) $stmt->fetchColumn() !== count($teamIds)) {
            $errors[] = 'Μη έγκυρες ομάδες.';
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO championships (name, season) VALUES (?, ?)");
            $stmt->execute([$name, $season]);
            $champId = (int) $db->lastInsertId();

            $stmt = $db->prepare(
                "INSERT INTO championship_teams (championship_id, team_id) VALUES (?, ?)"
            );
            foreach ($teamIds as $tid) {
                $stmt->execute([$champId, $tid]);
            }

            $db->commit();
            flash('success', 'Το πρωτάθλημα δημιουργήθηκε.');
        } catch (PDOException $e) {
            $db->rollBack();
            if ((int) $e->errorInfo[1] === 1062) {
                flash('danger', 'Υπάρχει ήδη πρωτάθλημα με αυτό το όνομα/σεζόν.');
            } else {
                flash('danger', 'Σφάλμα δημιουργίας.');
                error_log($e->getMessage());
            }
        }
    } else {
        foreach ($errors as $err) flash('danger', $err);
    }

    header('Location: championships.php');
    exit;
}

$teams = $db->query("SELECT id, name FROM teams ORDER BY name")->fetchAll();
$championships = $db->query("
    SELECT c.*,
        (SELECT COUNT(*) FROM championship_teams WHERE championship_id = c.id) AS teams_count
    FROM championships c
    ORDER BY c.created_at DESC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h2>Πρωταθλήματα</h2>

<div class="card mb-4">
    <div class="card-header">Δημιουργία Πρωταθλήματος</div>
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Όνομα Πρωταθλήματος</label>
                    <input type="text" name="champ_name" class="form-control" maxlength="150" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Σεζόν</label>
                    <input type="text" name="season" class="form-control" maxlength="20" placeholder="π.χ. 2025-26" required>
                </div>
            </div>

            <label class="form-label">
                Επιλογή Ομάδων
                <small class="text-muted">(ζυγός αριθμός, τουλάχιστον 2)</small>
            </label>
            <div class="border rounded p-3 mb-3" style="max-height: 250px; overflow-y: auto;">
                <?php if (empty($teams)): ?>
                    <p class="text-muted mb-0">Δεν υπάρχουν ομάδες — δημιουργήστε πρώτα μερικές.</p>
                <?php else: foreach ($teams as $t): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="team_ids[]"
                               value="<?= (int) $t['id'] ?>" id="team-<?= (int) $t['id'] ?>">
                        <label class="form-check-label" for="team-<?= (int) $t['id'] ?>">
                            <?= e($t['name']) ?>
                        </label>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <button type="submit" class="btn btn-success" <?= empty($teams) ? 'disabled' : '' ?>>Δημιουργία</button>
        </form>
    </div>
</div>

<h3>Λίστα Πρωταθλημάτων</h3>
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>Όνομα</th>
            <th>Σεζόν</th>
            <th>Ομάδες</th>
            <th>Κατάσταση</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($championships)): ?>
            <tr><td colspan="6" class="text-center text-muted">Δεν υπάρχουν ακόμα πρωταθλήματα.</td></tr>
        <?php else: foreach ($championships as $c): ?>
        <tr>
            <td><?= (int) $c['id'] ?></td>
            <td><?= e($c['name']) ?></td>
            <td><?= e($c['season']) ?></td>
            <td><?= (int) $c['teams_count'] ?></td>
            <td><span class="badge bg-secondary"><?= e($c['status']) ?></span></td>
            <td>
                <a href="draw.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-primary">
                    Κλήρωση / Πρόγραμμα
                </a>
            </td>
        </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
