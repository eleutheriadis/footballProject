<?php
require_once 'config.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $champ_name = $_POST['champ_name'];
    $season = $_POST['season'];
    $team_ids = $_POST['team_ids'] ?? [];

    if (count($team_ids) % 2 !== 0 || count($team_ids) == 0) {
        echo "<div class='alert alert-danger'>Πρέπει να επιλέξετε ζυγό αριθμό ομάδων! (τουλάχιστον 2)</div>";
    } else {
        $stmt = $db->prepare("INSERT INTO championships (name, season) VALUES (?, ?)");
        $stmt->execute([$champ_name, $season]);
        $champ_id = $db->lastInsertId();

        foreach ($team_ids as $tid) {
            $stmt2 = $db->prepare("INSERT INTO championship_teams (championship_id, team_id) VALUES (?, ?)");
            $stmt2->execute([$champ_id, $tid]);
        }
        echo "<div class='alert alert-success'>Το πρωτάθλημα δημιουργήθηκε!</div>";
    }
}

$teams = $db->query("SELECT * FROM teams")->fetchAll();
$championships = $db->query("SELECT * FROM championships")->fetchAll();

require_once 'includes/header.php';
?>

<h2>Δημιουργία Πρωταθλήματος</h2>
<hr>

<form method="POST">
    <label>Όνομα Πρωταθλήματος:</label>
    <input type="text" name="champ_name" class="form-control" required>
    <br>
    
    <label>Σεζόν:</label>
    <input type="text" name="season" class="form-control" required>
    <br>
    
    <h4>Επιλογή Ομάδων:</h4>
    <div class="border p-3 mb-3">
        <?php foreach($teams as $t) { ?>
            <input type="checkbox" name="team_ids[]" value="<?php echo $t['id']; ?>"> <?php echo $t['name']; ?><br>
        <?php } ?>
    </div>
    
    <button type="submit" class="btn btn-success">Δημιουργία</button>
</form>

<hr>
<h3>Λίστα Πρωταθλημάτων</h3>
<table class="table table-bordered">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Όνομα</th>
            <th>Σεζόν</th>
            <th>Κατάσταση</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($championships as $c) { ?>
        <tr>
            <td><?php echo $c['id']; ?></td>
            <td><?php echo $c['name']; ?></td>
            <td><?php echo $c['season']; ?></td>
            <td><?php echo $c['status']; ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>

<?php require_once 'includes/footer.php'; ?>