<?php
require_once 'config.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_draw'])) {
    $champ_id = $_POST['champ_id'];

    $teams = $db->query("SELECT team_id FROM championship_teams WHERE championship_id = $champ_id")->fetchAll();
    $ids = [];
    foreach ($teams as $t) {
        $ids[] = $t['team_id'];
    }
    
    shuffle($ids);
    $n = count($ids);
    $rounds = $n - 1;
    $half = $n / 2;

    for ($r = 0; $r < $rounds; $r++) {
        $db->query("INSERT INTO matchdays (championship_id, round_number) VALUES ($champ_id, " . ($r + 1) . ")");
        $matchday_id = $db->lastInsertId();

        $circle = array_merge([$ids[0]], array_slice($ids, 1));
        $rotating = array_slice($ids, 1);
        $rotating = array_merge(
            array_slice($rotating, $r % ($n - 1)),
            array_slice($rotating, 0, $r % ($n - 1))
        );
        $circle = array_merge([$ids[0]], $rotating);

        for ($m = 0; $m < $half; $m++) {
            $home = $circle[$m];
            $away = $circle[$n - 1 - $m];
            
            // Για να υπάρχει δίκαιη εναλλαγή έδρας
            if ($r % 2 === 0) {
                $db->query("INSERT INTO matches (matchday_id, home_team_id, away_team_id) VALUES ($matchday_id, $home, $away)");
            } else {
                $db->query("INSERT INTO matches (matchday_id, home_team_id, away_team_id) VALUES ($matchday_id, $away, $home)");
            }
        }
    }
    
    $db->query("UPDATE championships SET status='drawn' WHERE id=$champ_id");
    echo "<div class='alert alert-success'>Η κλήρωση ολοκληρώθηκε με επιτυχία!</div>";
}

$champ_id = $_GET['id'] ?? 0;
$champ = null;
$matches = [];

if ($champ_id > 0) {
    $champ = $db->query("SELECT * FROM championships WHERE id = $champ_id")->fetch();
    if ($champ) {
        $matches = $db->query("
            SELECT md.round_number, ht.name as home, at.name as away 
            FROM matchdays md 
            JOIN matches m ON m.matchday_id = md.id 
            JOIN teams ht ON ht.id = m.home_team_id 
            JOIN teams at ON at.id = m.away_team_id 
            WHERE md.championship_id = $champ_id 
            ORDER BY md.round_number
        ")->fetchAll();
    }
}
$championships = $db->query("SELECT * FROM championships")->fetchAll();

require_once 'includes/header.php';
?>

<h2>Κλήρωση Πρωταθλήματος</h2>

<?php if (!$champ) { ?>
    <h4>Επιλέξτε Πρωτάθλημα:</h4>
    <ul>
    <?php foreach($championships as $c) { ?>
        <li><a href="draw.php?id=<?php echo $c['id']; ?>"><?php echo $c['name']; ?></a> (<?php echo $c['status']; ?>)</li>
    <?php } ?>
    </ul>
<?php } else { ?>
    <div class="card p-3 bg-light">
        <h3>Πρωτάθλημα: <?php echo $champ['name']; ?></h3>
        
        <?php if ($champ['status'] == 'draft') { ?>
            <p>Το πρωτάθλημα δεν έχει κληρωθεί ακόμα.</p>
            <form method="POST">
                <input type="hidden" name="champ_id" value="<?php echo $champ['id']; ?>">
                <button type="submit" name="do_draw" class="btn btn-danger">Εκτέλεση Κλήρωσης</button>
            </form>
        <?php } else { ?>
            <p>Η κλήρωση έχει ολοκληρωθεί. Παρακάτω το πρόγραμμα:</p>
            <table class="table table-bordered table-striped mt-3 bg-white">
                <thead>
                    <tr>
                        <th>Αγωνιστική</th>
                        <th>Γηπεδούχος</th>
                        <th>Φιλοξενούμενος</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($matches as $m) { ?>
                    <tr>
                        <td><?php echo $m['round_number']; ?>η</td>
                        <td><?php echo $m['home']; ?></td>
                        <td><?php echo $m['away']; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>
    
    <br>
    <a href="draw.php" class="btn btn-secondary">Πίσω στη λίστα</a>
<?php } ?>

<?php require_once 'includes/footer.php'; ?>