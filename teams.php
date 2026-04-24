<?php
require_once 'config.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $city = $_POST['city'];
    $logo = '';

    if (!empty($_FILES['logo']['name'])) {
        $upload = uploadImage($_FILES['logo'], 'logos');
        if (!isset($upload['error'])) {
            $logo = $upload['path'];
        }
    }

    $stmt = $db->prepare("INSERT INTO teams (name, city, logo) VALUES (?, ?, ?)");
    $stmt->execute([$name, $city, $logo]);
    echo "<div class='alert alert-success'>Η ομάδα προστέθηκε!</div>";
}

$teams = $db->query("SELECT * FROM teams")->fetchAll();
?>
<?php require_once 'includes/header.php'; ?>

<h2>Διαχείριση Ομάδων</h2>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Νέα Ομάδα</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <label>Όνομα Ομάδας</label>
                    <input type="text" name="name" class="form-control" required>
                    <br>
                    <label>Πόλη</label>
                    <input type="text" name="city" class="form-control" required>
                    <br>
                    <label>Σήμα</label>
                    <input type="file" name="logo" class="form-control">
                    <br>
                    <button type="submit" class="btn btn-primary">Αποθήκευση</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Λογότυπο</th>
                    <th>Όνομα</th>
                    <th>Πόλη</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($teams as $t) { ?>
                <tr>
                    <td>
                        <?php if ($t['logo']) { ?>
                            <img src="<?php echo $t['logo']; ?>" width="50">
                        <?php } else { echo "Χωρίς Σήμα"; } ?>
                    </td>
                    <td><?php echo $t['name']; ?></td>
                    <td><?php echo $t['city']; ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>