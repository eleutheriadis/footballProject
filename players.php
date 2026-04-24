<?php
require_once 'config.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $position = $_POST['position'];
    $team_id = $_POST['team_id'];
    $photo = '';

    if (!empty($_FILES['photo']['name'])) {
        $upload = uploadImage($_FILES['photo'], 'photos');
        if (!isset($upload['error'])) {
            $photo = $upload['path'];
        }
    }

    $stmt = $db->prepare("INSERT INTO players (name, position, team_id, photo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $position, $team_id, $photo]);
    echo "<div class='alert alert-success'>Ο παίκτης αποθηκεύτηκε!</div>";
}

$teams = $db->query("SELECT * FROM teams")->fetchAll();
$players = $db->query("SELECT players.*, teams.name as team_name FROM players JOIN teams ON players.team_id = teams.id")->fetchAll();

require_once 'includes/header.php';
?>

<h2>Διαχείριση Παικτών</h2>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Νέος Παίκτης</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-2">
                        <label>Όνομα Παίκτη</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Θέση</label>
                        <select name="position" class="form-control" required>
                            <option value="Τερματοφύλακας">Τερματοφύλακας</option>
                            <option value="Αμυντικός">Αμυντικός</option>
                            <option value="Μέσος">Μέσος</option>
                            <option value="Επιθετικός">Επιθετικός</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Ομάδα</label>
                        <select name="team_id" class="form-control" required>
                            <?php foreach($teams as $t) { ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo $t['name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Φωτογραφία</label>
                        <input type="file" name="photo" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Προσθήκη</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <table class="table table-striped">
            <tr>
                <th>Φωτογραφία</th>
                <th>Όνομα</th>
                <th>Θέση</th>
                <th>Ομάδα</th>
            </tr>
            <?php foreach($players as $p) { ?>
            <tr>
                <td>
                    <?php if ($p['photo']) { ?>
                        <img src="<?php echo $p['photo']; ?>" width="50">
                    <?php } else { echo "Χωρίς Φωτό"; } ?>
                </td>
                <td><?php echo $p['name']; ?></td>
                <td><?php echo $p['position']; ?></td>
                <td><?php echo $p['team_name']; ?></td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>