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
$publication = null;

if (!isset($_GET['id'])) {
    header('Location: list_pubs.php');
    exit();
}

$pub_id = $_GET['id'];

// Créer le dossier uploads s'il n'existe pas
if (!file_exists('uploads')) {
    mkdir('uploads', 0755, true);
}

    // Récupérer la publication
    $stmt = $pdo->prepare("SELECT * FROM publications WHERE id = ? AND user_id = ?");
    $stmt->execute([$pub_id, $_SESSION['user_id']]);
    $publication = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$publication) {
        header('Location: list_pubs.php');
        exit();
    }
    

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contenu = trim($_POST['contenu']);
    $date_ajout = $_POST['date_ajout'];
    $nouvelle_image = null;
    $supprimer_image = isset($_POST['supprimer_image']);
    
    if (empty($contenu)) {
        $error = "Le contenu est requis";
    } elseif (empty($date_ajout)) {
        $error = "La date d'ajout est requise";
    } else {
        // Traitement de la nouvelle image
        if (isset($_FILES['nouvelle_image']) && $_FILES['nouvelle_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['nouvelle_image']['type'], $allowed_types)) {
                $error = "Type d'image non autorisé. Utilisez JPEG, PNG ou GIF.";
            } elseif ($_FILES['nouvelle_image']['size'] > $max_size) {
                $error = "L'image est trop grande. Taille maximum : 5MB.";
            } else {
                $extension = pathinfo($_FILES['nouvelle_image']['name'], PATHINFO_EXTENSION);
                $nouvelle_image = uniqid() . '.' . $extension;
                $chemin_destination = 'uploads/' . $nouvelle_image;
                
                if (!move_uploaded_file($_FILES['nouvelle_image']['tmp_name'], $chemin_destination)) {
                    $error = "Erreur lors du téléchargement de l'image";
                    $nouvelle_image = null;
                }
            }
        }
        
        if (empty($error)) {
            try {
                // Déterminer quelle image utiliser
                $image_finale = $publication['image']; // Garder l'image actuelle par défaut
                
                if ($supprimer_image) {
                    // Supprimer l'image actuelle
                    if ($publication['image'] && file_exists('uploads/' . $publication['image'])) {
                        unlink('uploads/' . $publication['image']);
                    }
                    $image_finale = null;
                }
                
                if ($nouvelle_image) {
                    // Supprimer l'ancienne image si elle existe
                    if ($publication['image'] && file_exists('uploads/' . $publication['image'])) {
                        unlink('uploads/' . $publication['image']);
                    }
                    $image_finale = $nouvelle_image;
                }
                
                // Mettre à jour la publication
                $stmt = $pdo->prepare("UPDATE publications SET contenu = ?, date_ajout = ?, image = ?, date_modification = NOW() WHERE id = ? AND user_id = ?");
                $stmt->execute([$contenu, $date_ajout, $image_finale, $pub_id, $_SESSION['user_id']]);
                
                $success = true;
                
                header("Location: dash.php");
                
                // Récupérer la publication mise à jour
                $stmt = $pdo->prepare("SELECT * FROM publications WHERE id = ? AND user_id = ?");
                $stmt->execute([$pub_id, $_SESSION['user_id']]);
                $publication = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $error = "Erreur lors de la modification";
                // Supprimer la nouvelle image si erreur BD
                if ($nouvelle_image && file_exists('uploads/' . $nouvelle_image)) {
                    unlink('uploads/' . $nouvelle_image);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier publication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-secondary">
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
                        <h3>✏ Modifier la publication<?php echo $publication['id']; ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            ✅ Publication modifiée avec succès ! <a href="list_pubs.php">Retour à la liste</a>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($publication): ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="contenu" class="form-label">Contenu</label>
                                <textarea class="form-control" id="contenu" name="contenu" rows="5"
                                    required><?php echo htmlspecialchars($publication['contenu']); ?></textarea>
                            </div>

                            <!-- Gestion de l'image -->
                            <div class="mb-3">
                                <label class="form-label">Image actuelle</label>
                                <?php if ($publication['image']): ?>
                                <div class="card">
                                    <div class="card-body text-center">
                                        <img src="uploads/<?php echo htmlspecialchars($publication['image']); ?>"
                                            alt="Image actuelle" class="img-fluid mb-3" style="max-height: 200px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="supprimer_image"
                                                name="supprimer_image">
                                            <label class="form-check-label" for="supprimer_image">
                                                🗑 Supprimer cette image
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">Aucune image pour cette publication</p>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="nouvelle_image" class="form-label">Nouvelle image (optionnelle)</label>
                                <input type="file" class="form-control" id="nouvelle_image" name="nouvelle_image"
                                    accept="image/jpeg,image/png,image/gif">
                                <div class="form-text">
                                    Formats acceptés : JPEG, PNG, GIF. Taille maximum : 5MB.
                                    <?php if ($publication['image']): ?>
                                    <br><strong>Note :</strong> Une nouvelle image remplacera l'image actuelle.
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="date_ajout" class="form-label">Date d'ajout</label>
                                <input type="date" class="form-control" id="date_ajout" name="date_ajout"
                                    value="<?php echo $publication['date_ajout']; ?>" required>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="list_pubs.php" class="btn btn-secondary me-md-2">Annuler</a>
                                <button type="S'inscrire" class="btn btn-primary">💾 Modifier</button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Aperçu de la nouvelle image sélectionnée -->
                <div class="card mt-3" id="imagePreview" style="display: none;">
                    <div class="card-header">
                        <h6>Aperçu de la nouvelle image</h6>
                    </div>
                    <div class="card-body text-center">
                        <img id="previewImg" src="" alt="Aperçu" class="img-fluid" style="max-height: 300px;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Aperçu de la nouvelle image sélectionnée
    document.getElementById('nouvelle_image').addEventListener('change', function(e) {
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

    // Masquer l'aperçu si on coche "supprimer image"
    document.getElementById('supprimer_image')?.addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('nouvelle_image').value = '';
        }
    });
    </script>
</body>

</html>