<?php
require_once __DIR__ . '/config.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        flash('danger', 'Μη έγκυρο token. Δοκιμάστε ξανά.');
        header('Location: teams.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');

    $errors = [];
    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Το όνομα πρέπει να έχει 1–100 χαρακτήρες.';
    }
    if ($city === '' || mb_strlen($city) > 100) {
        $errors[] = 'Η πόλη πρέπει να έχει 1–100 χαρακτήρες.';
    }

    $logoPath = null;
    if (!empty($_FILES['logo']['name'])) {
        $up = uploadImage($_FILES['logo'], 'teams');
        if (isset($up['error'])) {
            $errors[] = $up['error'];
        } else {
            $logoPath = $up['path'];
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO teams (name, city, logo_path) VALUES (?, ?, ?)");
            $stmt->execute([$name, $city, $logoPath]);
            flash('success', 'Η ομάδα προστέθηκε με επιτυχία.');
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                flash('danger', 'Υπάρχει ήδη ομάδα με αυτό το όνομα.');
            } else {
                flash('danger', 'Σφάλμα αποθήκευσης.');
                error_log($e->getMessage());
            }
        }
    } else {
        foreach ($errors as $err) flash('danger', $err);
    }

    header('Location: teams.php');
    exit;
}

$teams = $db->query("SELECT * FROM teams ORDER BY name")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h2>Διαχείριση Ομάδων</h2>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Νέα Ομάδα</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <div class="mb-2">
                        <label class="form-label">Όνομα Ομάδας</label>
                        <input type="text" name="name" class="form-control" maxlength="100" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Πόλη</label>
                        <input type="text" name="city" class="form-control" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Σήμα</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <small class="text-muted">JPG/PNG/GIF/WebP, μέγιστο 2&nbsp;MB.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Αποθήκευση</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Σήμα</th>
                    <th>Όνομα</th>
                    <th>Πόλη</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teams)): ?>
                    <tr><td colspan="3" class="text-center text-muted">Δεν υπάρχουν ακόμα ομάδες.</td></tr>
                <?php else: foreach ($teams as $t): ?>
                <tr>
                    <td>
                        <?php if ($t['logo_path']): ?>
                            <img src="<?= e($t['logo_path']) ?>" alt="" class="team-logo">
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($t['name']) ?></td>
                    <td><?= e($t['city']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
