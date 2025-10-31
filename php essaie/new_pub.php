<?php
session_start();
include('bdd.php');
if (!isset($_SESSION['user_id'])) {
    header('Location: connection.php');
    exit();
}


$success = false;
$error = '';
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT photo_profil FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!file_exists('uploads')) {  
    mkdir('uploads', 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contenu = trim($_POST['contenu']);
    $date_ajout = $_POST['date_ajout'];
    $image_nom = null;
    
    if (empty($contenu)) {
        $error = "Le contenu est requis";
    } elseif (empty($date_ajout)) {
        $error = "La date d'ajout est requise";
    } else {
        // Traitement de l'image
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $error = "Type d'image non autorisé. Utilisez JPEG, PNG ou GIF.";
            } elseif ($_FILES['image']['size'] > $max_size) {
                $error = "L'image est trop grande. Taille maximum : 5MB.";
            } else {
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_nom = uniqid() . '.' . $extension;
                $chemin_destination = 'uploads/' . $image_nom;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $chemin_destination)) {
                    $error = "Erreur lors du téléchargement de l'image";
                    $image_nom = null;
                }
            }
        }
        
        if (empty($error)) {
            $stmt = $pdo->prepare("INSERT INTO publications (user_id, contenu, date_ajout, image) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $contenu, $date_ajout, $image_nom]);
            $success = true;
            // Redirection pour éviter la duplication de publication si on rafraîchit
            header("Location: dash.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle publication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="color bg-secondary">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <img src="logo/StevenX.png" alt="" class="m-2" style="width: 40px; height: 40px; border-radius: 50%;">
        <b><a class="navbar-brand" href="dash.php">STEVEN X</a></b>
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dash.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="new_pub.php">New Pub</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="list_pubs.php">List des Pubs</a>
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

    <!-- Modal de confirmation de déconnexion -->
    <div class="modal fade" id="confirmLogoutModal" tabindex="-1" aria-labelledby="confirmLogoutLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title" id="confirmLogoutLabel">Confirmer votre déconnexion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p>Vouliez vous vraiment vous deconnecter 🥺 ?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="logout.php" class="btn btn-danger">Oui, me déconnecter</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3>📝 Nouvelle publication</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            ✅ Publication créée avec succès !
                            <a href="list_pubs.php">Voir mes publications</a> |
                            <a href="dash.php">Retour à l'accueil</a>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="contenu" class="form-label">Contenu </label>
                                <textarea class="form-control" id="contenu" name="contenu" rows="5"
                                    placeholder="Partagez vos pensées..."
                                    required><?php echo isset($_POST['contenu']) ? htmlspecialchars($_POST['contenu']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Image (optionnelle)</label>
                                <input type="file" class="form-control" id="image" name="image"
                                    accept="image/jpeg,image/png,image/gif">
                                <div class="form-text">
                                    Formats acceptés : JPEG, PNG, GIF. Taille maximum : 5MB.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="date_ajout" class="form-label">Date d'ajout </label>
                                <input type="date" class="form-control" id="date_ajout" name="date_ajout"
                                    value="<?php echo isset($_POST['date_ajout']) ? $_POST['date_ajout'] : date('Y-m-d'); ?>"
                                    required>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dash.php" class="btn btn-secondary me-md-2">Annuler</a>
                                <button type="S'inscrire" class="btn btn-primary">📤 Publier</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Aperçu de l'image sélectionnée -->
                <div class="card mt-3" id="imagePreview" style="display: none;">
                    <div class="card-header">
                        <h6>Aperçu de l'image</h6>
                    </div>
                    <div class="card-body text-center">
                        <img id="previewImg" src="" alt="Aperçu" class="img-fluid" style="max-height: 300px;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script>
    // Aperçu de l'image sélectionnée
    document.getElementById('image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });
    </script> -->
</body>

</html>