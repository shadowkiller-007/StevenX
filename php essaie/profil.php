<?php
session_start();
include('bdd.php');
if (!isset($_SESSION['user_id'])) {
    header('Location: connection.php');
    exit();
}

$user = null;
$success = false;
$error = '';
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Mise à jour des informations personnelles
    if (isset($_POST['modifier_profil'])) {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $date_naissance = $_POST['date_naissance'];
        $telephone = trim($_POST['telephone']);
        $sexe = $_POST['sexe'];
        $adresse = trim($_POST['adresse']);
        $mail = trim($_POST['mail']);
        
        // Validation des champs (CORRIGÉES)
        $errors = [];
        
        if (empty($nom)) {
            $errors[] = "Le nom est requis";
        } elseif (strlen($nom) < 2) {
            $errors[] = "Le nom doit contenir au moins 2 caractères";
        } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/u", $nom)) {
            $errors[] = "Le nom ne doit contenir que des lettres, espaces, apostrophes et tirets";
        }
        
        if (empty($prenom)) {
            $errors[] = "Le prénom est requis";
        } elseif (strlen($prenom) < 2) {
            $errors[] = "Le prénom doit contenir au moins 2 caractères";
        } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/u", $prenom)) {
            $errors[] = "Le prénom ne doit contenir que des lettres, espaces, apostrophes et tirets";
        }
        
        if (empty($date_naissance)) {
            $errors[] = "La date de naissance est requise";
        } else {
            // Vérification de l'âge (minimum 13 ans)
            $date_naissance_obj = new DateTime($date_naissance);
            $date_actuelle = new DateTime();
            $age = $date_actuelle->diff($date_naissance_obj)->y;
            
            if ($age < 13) {
                $errors[] = "Vous devez avoir au moins 15 ans";
            }
        }
        
        if (empty($telephone)) {
            $errors[] = "Le téléphone est requis";
        } elseif (!preg_match("/^(\+?[1-9]\d{7,14})$/", $telephone)) {
            $errors[] = "Le numéro de téléphone doit contenir entre 8 et 15 chiffres";
        }
        
        if (empty($sexe) || !in_array($sexe, ['Homme', 'Femme'])) {
            $errors[] = "Le sexe est requis";
        }
        
        if (empty($adresse)) {
            $errors[] = "L'adresse est requise";
        } elseif (strlen($adresse) < 5) {
            $errors[] = "L'adresse doit contenir au moins 5 caractères";
        }
        
        if (empty($mail)) {
            $errors[] = "L'email est requis";
        } elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Le format de l'email n'est pas valide";
        }
        
        if (empty($errors)) {
            try {
                // Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE mail = ? AND id != ?");
                $stmt->execute([$mail, $_SESSION['user_id']]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = "Cet email est déjà utilisé par un autre utilisateur";
                } else {
                    // Mettre à jour les informations
                    $stmt = $pdo->prepare("UPDATE user SET nom = ?, prenom = ?, date_naissance = ?, telephone = ?, sexe = ?, adresse = ?, mail = ? WHERE id = ?");
                    $stmt->execute([$nom, $prenom, $date_naissance, $telephone, $sexe, $adresse, $mail, $_SESSION['user_id']]);
                    
                    // Mettre à jour la session
                    $_SESSION['user_nom'] = $nom;
                    $_SESSION['user_prenom'] = $prenom;
                    $_SESSION['user_mail'] = $mail;
                    
                    $success = true;
                    
                    // Récupérer les données mises à jour
                    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                $error = "Erreur lors de la mise à jour : " . $e->getMessage();
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    /* ...garder le style utile pour la page... */
    </style>
</head>

<body class="color bg-secondary">
    <nav class=" navbar navbar-expand-lg navbar-dark bg-primary">
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
                        <a class="nav-link active" href="profil.php">Profil</a>
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
            <div class="col-md-10">
                <!-- Messages de succès/erreur -->
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    ✅ Profil mis à jour avec succès !
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    ❌ <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($user): ?>
                <!-- Section Informations Personnelles -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">👤 Informations personnelles</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="nom" name="nom"
                                        value="<?php echo htmlspecialchars($user['nom']); ?>" required minlength="2"
                                        maxlength="50">
                                    <small class="text-muted">Minimum 2 caractères</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="prenom" class="form-label">Prénom</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom"
                                        value="<?php echo htmlspecialchars($user['prenom']); ?>" required minlength="2"
                                        maxlength="50">
                                    <small class="text-muted">Minimum 2 caractères</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date_naissance" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control" id="date_naissance" name="date_naissance"
                                        value="<?php echo $user['date_naissance']; ?>" required>
                                    <small class="text-muted">Vous devez avoir au moins 13 ans</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone"
                                        value="<?php echo htmlspecialchars($user['telephone']); ?>" required
                                        pattern="^\+?[1-9]\d{7,14}$">
                                    <small class="text-muted">8 à 15 chiffres, avec ou sans indicatif</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="sexe" class="form-label">Sexe</label>
                                    <select class="form-select" id="sexe" name="sexe" required>
                                        <option value="">Choisir...</option>
                                        <option value="Homme"
                                            <?php echo ($user['sexe'] == 'Homme') ? 'selected' : ''; ?>>Homme</option>
                                        <option value="Femme"
                                            <?php echo ($user['sexe'] == 'Femme') ? 'selected' : ''; ?>>Femme</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="mail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="mail" name="mail"
                                        value="<?php echo htmlspecialchars($user['mail']); ?>" required>
                                    <small class="text-muted">Format valide requis</small>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="adresse" class="form-label">Adresse</label>
                                <textarea class="form-control" id="adresse" name="adresse" rows="3" required
                                    minlength="5"
                                    maxlength="200"><?php echo htmlspecialchars($user['adresse']); ?></textarea>
                                <small class="text-muted">Minimum 5 caractères</small>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                                <a href="edit_password.php" class="btn btn-warning">
                                    🔐 Modifier le mot de passe
                                </a>
                                <div>
                                    <a href="dash.php" class="btn btn-secondary me-2">❌ Annuler</a>
                                    <button type="S'inscrire" name="modifier_profil" class="btn btn-primary">
                                        ✅ Mettre à jour
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Informations sur le compte -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">ℹ️ Informations du compte</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ID utilisateur:</strong> <?php echo $user['id']; ?></p>
                                <p><strong>Membre depuis:</strong>
                                    <?php echo date('d/m/Y', strtotime($user['date_creation'] ?? 'now')); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Dernière modification:</strong>
                                    <?php echo date('d/m/Y H:i', strtotime($user['date_modification'] ?? 'now')); ?>
                                </p>
                                <p><strong>Statut:</strong> <span class="badge bg-success">Actif</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>