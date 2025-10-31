<?php
session_start();
include('bdd.php'); // Utiliser votre fichier de connexion BDD existant

if (!isset($_SESSION['user_id'])) {
    header('Location: connection.php');
    exit();
}

// Récupérer les informations de l'utilisateur connecté pour la photo
$stmt = $pdo->prepare("SELECT photo_profil FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

$success = false;
$error = '';

try {
    // Traitement du déblocage
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['debloquer_user'])) {
        $user_id_a_debloquer = $_POST['user_id_a_debloquer'];
        
        try {
            // Supprimer le blocage
            $stmt = $pdo->prepare("DELETE FROM utilisateurs_bloques WHERE bloqueur_id = ? AND bloque_id = ?");
            $stmt->execute([$_SESSION['user_id'], $user_id_a_debloquer]);
            if ($stmt->rowCount() > 0) {
                $success = true;
            } else {
                $error = "Utilisateur non trouvé dans la liste des bloqués";
            }
        } catch (PDOException $e) {
            $error = "Erreur lors du déblocage : " . $e->getMessage();
        }
    }
    
    // Récupérer la liste des utilisateurs bloqués avec leurs photos
    $stmt = $pdo->prepare("
        SELECT u.id, u.nom, u.prenom, u.mail, u.photo_profil, ub.date_creation as date_blocage
        FROM utilisateurs_bloques ub
        JOIN user u ON ub.bloque_id = u.id
        WHERE ub.bloqueur_id = ?
        ORDER BY ub.date_creation DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $utilisateurs_bloques = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $utilisateurs_bloques = [];
    $error = "Erreur de base de données : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs bloqués</title>
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
                    <p>Voulez-vous vraiment vous déconnecter 🥺 ?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="logout.php" class="btn btn-danger">Oui, me déconnecter</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>🚫 Utilisateurs bloqués</h2>
                    <a href="amis.php" class="btn btn-outline-primary">← Retour aux amis</a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    ✅ Utilisateur débloqué avec succès !
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (empty($utilisateurs_bloques)): ?>
                <div class="alert alert-info text-center">
                    <h5>Aucun utilisateur bloqué</h5>
                    <p>Vous n'avez bloqué aucun utilisateur.</p>
                    <a href="amis.php" class="btn btn-primary">Retour aux amis</a>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    <strong>Information :</strong> Vous avez bloqué <?php echo count($utilisateurs_bloques); ?>
                    utilisateur(s).
                    Ces personnes ne peuvent plus vous voir ni interagir avec vous.
                </div>

                <div class="row">
                    <?php foreach ($utilisateurs_bloques as $user_bloque): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <?php if ($user_bloque['photo_profil'] && file_exists($user_bloque['photo_profil'])): ?>
                                    <img src="<?php echo htmlspecialchars($user_bloque['photo_profil']); ?>"
                                        alt="Photo de profil" class="rounded-circle"
                                        style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                    <div class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                        style="width: 60px; height: 60px; font-size: 24px;">
                                        <?php echo strtoupper(substr($user_bloque['prenom'], 0, 1) . substr($user_bloque['nom'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($user_bloque['prenom'] . ' ' . $user_bloque['nom']); ?>
                                </h5>
                                <p class="card-text text-muted">
                                    <small><?php echo htmlspecialchars($user_bloque['mail']); ?></small><br>
                                    <small>Bloqué le
                                        <?php echo date('d/m/Y', strtotime($user_bloque['date_blocage'])); ?></small>
                                </p>

                                <!-- Bouton pour déclencher le modal de confirmation -->
                                <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#confirmModal<?php echo $user_bloque['id']; ?>">
                                    🔓 Débloquer
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de confirmation pour chaque utilisateur -->
                    <div class="modal fade" id="confirmModal<?php echo $user_bloque['id']; ?>" tabindex="-1"
                        aria-labelledby="confirmModalLabel<?php echo $user_bloque['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title" id="confirmModalLabel<?php echo $user_bloque['id']; ?>">
                                        Confirmer le déblocage
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center">
                                        <?php if ($user_bloque['photo_profil'] && file_exists($user_bloque['photo_profil'])): ?>
                                        <img src="<?php echo htmlspecialchars($user_bloque['photo_profil']); ?>"
                                            alt="Photo de profil" class="rounded-circle mb-3"
                                            style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                            style="width: 50px; height: 50px; font-size: 20px;">
                                            <?php echo strtoupper(substr($user_bloque['prenom'], 0, 1) . substr($user_bloque['nom'], 0, 1)); ?>
                                        </div>
                                        <?php endif; ?>
                                        <h6><?php echo htmlspecialchars($user_bloque['prenom'] . ' ' . $user_bloque['nom']); ?>
                                        </h6>
                                    </div>
                                    <hr>
                                    <p><strong>Êtes-vous sûr de vouloir débloquer cet utilisateur ?</strong></p>
                                    <div class="alert alert-info">
                                        <small>
                                            <strong>Conséquences du déblocage :</strong>
                                            <ul class="mb-0">
                                                <li>Cette personne pourra à nouveau vous voir</li>
                                                <li>Elle pourra interagir avec vos publications</li>
                                                <li>Vous pourrez à nouveau être amis</li>
                                            </ul>
                                        </small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        ❌ Annuler
                                    </button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id_a_debloquer"
                                            value="<?php echo $user_bloque['id']; ?>">
                                        <button type="S'inscrire" name="debloquer_user" class="btn btn-success">
                                            🔓 Oui, débloquer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>