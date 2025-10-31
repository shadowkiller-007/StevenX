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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ancien_mot_de_passe = $_POST['ancien_mot_de_passe'];
    $nouveau_mot_de_passe = $_POST['nouveau_mot_de_passe'];
    $confirmer_mot_de_passe = $_POST['confirmer_mot_de_passe'];
    
    // Validation des champs
    $errors = [];
    
    if (empty($ancien_mot_de_passe)) {
        $errors[] = "L'ancien mot de passe est requis";
    }
    
    if (empty($nouveau_mot_de_passe)) {
        $errors[] = "Le nouveau mot de passe est requis";
    } else {
        // Validation complète du nouveau mot de passe
        if (strlen($nouveau_mot_de_passe) < 8) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères";
        }
        if (!preg_match('/[A-Z]/', $nouveau_mot_de_passe)) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins une lettre majuscule";
        }
        if (!preg_match('/[a-z]/', $nouveau_mot_de_passe)) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins une lettre minuscule";
        }
        if (!preg_match('/[0-9]/', $nouveau_mot_de_passe)) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins un chiffre";
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $nouveau_mot_de_passe)) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins un caractère spécial (!@#$%^&*(),.?\":{}|<>)";
        }
    }
    
    if (empty($confirmer_mot_de_passe)) {
        $errors[] = "La confirmation du mot de passe est requise";
    }
    
    if ($nouveau_mot_de_passe !== $confirmer_mot_de_passe) {
        $errors[] = "Les nouveaux mots de passe ne correspondent pas";
    }
    
    if (empty($errors)) {
        // try {
            // // $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Vérifier l'ancien mot de passe
            $stmt = $pdo->prepare("SELECT mdp FROM user WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($ancien_mot_de_passe, $user['mdp'])) {
                $error = "L'ancien mot de passe est incorrect";
            } else {
                // Mettre à jour le mot de passe
                $nouveau_mot_de_passe_crypte = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE user SET mdp = ? WHERE id = ?");
                $stmt->execute([$nouveau_mot_de_passe_crypte, $_SESSION['user_id']]);
                
                $success = true;
            }
        // } catch (PDOException $e) {
            // $error = "Erreur lors de la mise à jour du mot de passe";
        // }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le mot de passe</title>
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
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3>Modifier le mot de passe</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            Mot de passe modifié avec succès ! <a href="profil.php">Retour au profil</a>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="ancien_mot_de_passe" class="form-label">Ancien mot de passe </label>
                                <input type="password" class="form-control" id="ancien_mot_de_passe"
                                    name="ancien_mot_de_passe" required>
                                <div class="form-text">Saisissez votre mot de passe actuel</div>
                            </div>

                            <div class="mb-3">
                                <label for="nouveau_mot_de_passe" class="form-label">Nouveau mot de passe </label>
                                <input type="password" class="form-control" id="nouveau_mot_de_passe"
                                    name="nouveau_mot_de_passe" required minlength="8"
                                    pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?\ :{}|<>]).{8,}$"
                                    title="Au moins 8 caractères avec majuscule, minuscule, chiffre et caractère spécial">
                                <div class="form-text">
                                    <strong>Requis :</strong> 8 caractères minimum 😁
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="confirmer_mot_de_passe" class="form-label">Confirmer le nouveau mot de passe
                                    *</label>
                                <input type="password" class="form-control" id="confirmer_mot_de_passe"
                                    name="confirmer_mot_de_passe" required>
                                <div class="form-text">Doit être identique au nouveau mot de passe</div>
                            </div>

                            <div class="alert alert-info">
                                <strong>⚠️ Important :</strong> Après la modification, vous devrez vous reconnecter avec
                                votre nouveau mot de passe.
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="profil.php" class="btn btn-secondary me-md-2">Annuler</a>
                                <button type="S'inscrire" class="btn btn-primary">🔐 Modifier le mot de passe</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>