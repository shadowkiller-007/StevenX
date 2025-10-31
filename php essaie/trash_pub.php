<?php
session_start();
include('bdd.php');
if (!isset($_SESSION['user_id'])) {
    header('Location: connection.php');
    exit();
}

// $host = 'localhost';
// $dbname = 'essai';
// $username = 'root';
// $password = '';


$error = '';
$publication = null;

// Récupérer les infos de l'utilisateur connecté pour la navbar
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT photo_profil FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!isset($_GET['id'])) {
    header('Location: list_pubs.php');
    exit();
}

$pub_id = $_GET['id'];

// try {
//     $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    
    $stmt = $pdo->prepare("SELECT * FROM publications WHERE id = ? AND user_id = ?");
    $stmt->execute([$pub_id, $_SESSION['user_id']]);
    $publication = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$publication) {
        header('Location: list_pubs.php');
        exit();
    }
    
// } catch (PDOException $e) {
//     $error = "Erreur de base de données";
// }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmer'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM publications WHERE id = ? AND user_id = ?");
        $stmt->execute([$pub_id, $_SESSION['user_id']]);
        
        header('Location: list_pubs.php?deleted=1');
        exit();
        
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer publication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dash.php">Mon Site</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dash.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="new_pub.php">New Pub</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="list_pubs.php">List des Pubs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="explorer.php">Explorer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="amis.php">Amis</a>
                    </li>
                    <li class="nav-item">
                        <?php include '_navbar_notifications.php'; ?>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profil.php">Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <!-- Affichage de la photo de profil dans la navbar -->
                <div class="d-flex align-items-center me-3">
                    <?php if ($current_user && $current_user['photo_profil'] && file_exists($current_user['photo_profil'])): ?>
                    <img src="<?php echo htmlspecialchars($current_user['photo_profil']); ?>" alt="Photo de profil"
                        class="rounded-circle me-2" style="width: 35px; height: 35px; object-fit: cover;">
                    <?php else: ?>
                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                        style="width: 35px; height: 35px; font-size: 14px;">
                        <?php echo strtoupper(substr($_SESSION['user_prenom'], 0, 1) . substr($_SESSION['user_nom'], 0, 1)); ?>
                    </div>
                    <?php endif; ?>
                    <span class="text-white small"><?php echo htmlspecialchars($_SESSION['user_prenom']); ?></span>
                </div>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                    data-bs-target="#confirmLogoutModal">Logout</button>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h3>Supprimer la publication<?php echo $publication['id']; ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($publication): ?>
                        <div class="alert alert-warning">
                            <h5>⚠ Attention !</h5>
                            Vous êtes sur le point de supprimer définitivement cette publication. Cette action est
                            irréversible.
                        </div>

                        <div class="card">
                            <div class="card-header bg-primary">
                                <h5>Aperçu de la publication</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>ID:</strong> <?php echo $publication['id']; ?></p>
                                <p><strong>Contenu:</strong></p>
                                <div class="border p-3 mb-3 bg-light">
                                    <?php echo nl2br(htmlspecialchars($publication['contenu'])); ?>
                                </div>
                                <p><strong>Date d'ajout:</strong>
                                    <?php echo date('d/m/Y', strtotime($publication['date_ajout'])); ?></p>
                                <?php if ($publication['date_modification']): ?>
                                <p><strong>Dernière modification:</strong>
                                    <?php echo date('d/m/Y H:i', strtotime($publication['date_modification'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h5>Confirmer la suppression</h5>
                            <p>Êtes-vous sûr de vouloir supprimer cette publication ?</p>

                            <form method="POST" class="d-inline">
                                <button type="S'inscrire" name="confirmer" class="btn btn-danger me-2">
                                    Oui, supprimer définitivement
                                </button>
                            </form>
                            <a href="list_pubs.php" class="btn btn-secondary">
                                Non, annuler
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>