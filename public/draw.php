<?php
require_once __DIR__ . '/config.php';

$db = getDB();

/**
 * Single round-robin schedule using the circle method.
 * Returns array of rounds; each round is an array of [home, away] pairs.
 * Alternates the fixed team's home/away across rounds for balance.
 */
function generateRoundRobin(array $teamIds): array {
    $n = count($teamIds);
    if ($n < 2 || $n % 2 !== 0) {
        throw new InvalidArgumentException('Χρειάζεται ζυγός αριθμός ομάδων (≥2).');
    }

    $arr    = $teamIds;
    $rounds = [];

    for ($r = 0; $r < $n - 1; $r++) {
        $round = [];
        for ($i = 0; $i < $n / 2; $i++) {
            $home = $arr[$i];
            $away = $arr[$n - 1 - $i];
            // Alternate the fixed team (position 0) to balance home/away
            if ($i === 0 && $r % 2 === 1) {
                [$home, $away] = [$away, $home];
            }
            $round[] = [$home, $away];
        }
        $rounds[] = $round;

        // Rotate everything except position 0
        $last = array_pop($arr);
        array_splice($arr, 1, 0, [$last]);
    }
    return $rounds;
}

// ============================================================
// Handle draw execution
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_draw'])) {
    if (!csrfValid()) {
        flash('danger', 'Μη έγκυρο token.');
        header('Location: draw.php');
        exit;
    }

    $champId = (int) ($_POST['champ_id'] ?? 0);

    $stmt = $db->prepare("SELECT id, status FROM championships WHERE id = ?");
    $stmt->execute([$champId]);
    $champ = $stmt->fetch();

    if (!$champ) {
        flash('danger', 'Δεν βρέθηκε το πρωτάθλημα.');
        header('Location: draw.php');
        exit;
    }
    if ($champ['status'] !== 'draft') {
        flash('warning', 'Η κλήρωση έχει ήδη γίνει.');
        header('Location: draw.php?id=' . $champId);
        exit;
    }

    $stmt = $db->prepare("SELECT team_id FROM championship_teams WHERE championship_id = ?");
    $stmt->execute([$champId]);
    $teamIds = array_map('intval', array_column($stmt->fetchAll(), 'team_id'));

    if (count($teamIds) < 2 || count($teamIds) % 2 !== 0) {
        flash('danger', 'Λάθος αριθμός ομάδων (απαιτείται ζυγός ≥2).');
        header('Location: draw.php?id=' . $champId);
        exit;
    }

    shuffle($teamIds);  // randomize the seed of the schedule

    try {
        $db->beginTransaction();

        $rounds = generateRoundRobin($teamIds);

        $insertMd    = $db->prepare("INSERT INTO matchdays (championship_id, number) VALUES (?, ?)");
        $insertMatch = $db->prepare(
            "INSERT INTO matches (matchday_id, home_team_id, away_team_id, status)
             VALUES (?, ?, ?, 'scheduled')"
        );

        foreach ($rounds as $idx => $round) {
            $insertMd->execute([$champId, $idx + 1]);
            $mdId = (int) $db->lastInsertId();
            foreach ($round as [$home, $away]) {
                $insertMatch->execute([$mdId, $home, $away]);
            }
        }

        $db->prepare("UPDATE championships SET status = 'active' WHERE id = ?")
           ->execute([$champId]);

        $db->commit();
        flash('success', 'Η κλήρωση ολοκληρώθηκε με επιτυχία.');
    } catch (Throwable $e) {
        $db->rollBack();
        error_log($e->getMessage());
        flash('danger', 'Σφάλμα κατά την κλήρωση.');
    }

    header('Location: draw.php?id=' . $champId);
    exit;
}

// ============================================================
// Display
// ============================================================
$champId = (int) ($_GET['id'] ?? 0);
$champ   = null;
$matches = [];

if ($champId > 0) {
    $stmt = $db->prepare("SELECT * FROM championships WHERE id = ?");
    $stmt->execute([$champId]);
    $champ = $stmt->fetch();

    if ($champ) {
        $stmt = $db->prepare("
            SELECT md.number AS round_number,
                   ht.name   AS home_name,
                   at.name   AS away_name
            FROM matchdays md
            JOIN matches m ON m.matchday_id = md.id
            JOIN teams ht ON ht.id = m.home_team_id
            JOIN teams at ON at.id = m.away_team_id
            WHERE md.championship_id = ?
            ORDER BY md.number, m.id
        ");
        $stmt->execute([$champId]);
        $matches = $stmt->fetchAll();
    }
}

$championships = $db->query("SELECT * FROM championships ORDER BY created_at DESC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h2>Κλήρωση Πρωταθλήματος</h2>

<?php if (!$champ): ?>
    <p>Επιλέξτε ένα πρωτάθλημα:</p>
    <?php if (empty($championships)): ?>
        <p class="text-muted">
            Δεν υπάρχουν πρωταθλήματα. Δημιουργήστε ένα από τη
            <a href="championships.php">σελίδα πρωταθλημάτων</a>.
        </p>
    <?php else: ?>
        <ul class="list-group" style="max-width: 600px;">
            <?php foreach ($championships as $c): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <strong><?= e($c['name']) ?></strong>
                        <small class="text-muted">(<?= e($c['season']) ?>)</small>
                    </span>
                    <span>
                        <span class="badge bg-secondary me-2"><?= e($c['status']) ?></span>
                        <a href="draw.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-primary">
                            Άνοιγμα
                        </a>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <h3 class="card-title">
                <?= e($champ['name']) ?>
                <small class="text-muted"><?= e($champ['season']) ?></small>
            </h3>

            <?php if ($champ['status'] === 'draft'): ?>
                <p>Η κλήρωση δεν έχει εκτελεστεί.</p>
                <form method="POST" onsubmit="return confirm('Επιβεβαίωση κλήρωσης;');">
                    <?= csrfField() ?>
                    <input type="hidden" name="champ_id" value="<?= (int) $champ['id'] ?>">
                    <button type="submit" name="do_draw" class="btn btn-danger">
                        Εκτέλεση Κλήρωσης
                    </button>
                </form>
            <?php else: ?>
                <p>Πρόγραμμα αγώνων:</p>
                <?php
                $byRound = [];
                foreach ($matches as $m) {
                    $byRound[(int) $m['round_number']][] = $m;
                }
                ?>
                <?php foreach ($byRound as $roundNum => $roundMatches): ?>
                    <h5 class="mt-3"><?= (int) $roundNum ?>η Αγωνιστική</h5>
                    <table class="table table-bordered table-sm mb-3">
                        <thead>
                            <tr>
                                <th>Γηπεδούχος</th>
                                <th class="text-center">vs</th>
                                <th>Φιλοξενούμενος</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roundMatches as $m): ?>
                                <tr>
                                    <td><?= e($m['home_name']) ?></td>
                                    <td class="text-center text-muted">vs</td>
                                    <td><?= e($m['away_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>

            <a href="draw.php" class="btn btn-secondary mt-3">Πίσω στη λίστα</a>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
