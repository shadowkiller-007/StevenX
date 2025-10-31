<?php
session_start();
include('bdd.php');
if (!isset($_SESSION['user_id'])) {
    header('Location: connection.php');
    exit();
}

// Récupérer les infos de l'utilisateur connecté pour la navbar
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT photo_profil FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
}
    
    // Traitement des actions
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action']) && isset($_POST['user_id'])) {
            $target_user_id = $_POST['user_id'];
            $action = $_POST['action'];
            
            if ($action == 'bloquer') {
                // Bloquer l'utilisateur
                $stmt = $pdo->prepare("INSERT IGNORE INTO utilisateurs_bloques (bloqueur_id, bloque_id) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $target_user_id]);
                
                // Supprimer toute relation d'amitié existante
                $stmt = $pdo->prepare("DELETE FROM demandes_amis WHERE (demandeur_id = ? AND receveur_id = ?) OR (demandeur_id = ? AND receveur_id = ?)");
                $stmt->execute([$_SESSION['user_id'], $target_user_id, $target_user_id, $_SESSION['user_id']]);
                
            } elseif ($action == 'ajouter') {
                // Envoyer une demande d'ami
                $stmt = $pdo->prepare("INSERT IGNORE INTO demandes_amis (demandeur_id, receveur_id) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $target_user_id]);
                
                // Créer une notification
                $message = $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'] . ' vous a envoyé une demande d\'ami.';
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, element_id) VALUES (?, 'demande_ami', ?, ?)");
                $stmt->execute([$target_user_id, $message, $_SESSION['user_id']]);
            }
        }
    }
    
    // Récupérer tous les utilisateurs sauf l'utilisateur connecté
    // Exclure les utilisateurs bloqués et ceux qui ont bloqué l'utilisateur connecté
    $stmt = $pdo->prepare("
        SELECT u.*, 
               da.statut as demande_statut,
               CASE 
                   WHEN da.demandeur_id = ? THEN 'envoyee'
                   WHEN da.receveur_id = ? THEN 'recue'
                   ELSE NULL
               END as type_demande
        FROM user u
        LEFT JOIN demandes_amis da ON 
            (da.demandeur_id = ? AND da.receveur_id = u.id) OR 
            (da.receveur_id = ? AND da.demandeur_id = u.id)
        WHERE u.id != ? 
        AND u.id NOT IN (
            SELECT bloque_id FROM utilisateurs_bloques WHERE bloqueur_id = ?
        )
        AND u.id NOT IN (
            SELECT bloqueur_id FROM utilisateurs_bloques WHERE bloque_id = ?
        )
        ORDER BY u.nom, u.prenom
    ");
    $stmt->execute([
        $_SESSION['user_id'], $_SESSION['user_id'], 
        $_SESSION['user_id'], $_SESSION['user_id'], 
        $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']
    ]);
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorer</title>
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
                        <a class="nav-link active" href="explorer.php">Explorer</a>
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
        <div class="row bg-primary rounded-bottom rounded-end">
            <div class="col-12">
                <h2>Explorer les utilisateurs sur <strong>STEVEN X</strong></h2>
                <p class="text-muted">Découvrez d'autres utilisateurs et ajoutez-les à vos amis.</p>

            </div>
        </div><br>

        <div class="row">
            <?php if (empty($utilisateurs)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <h5>Aucun utilisateur à découvrir sur <strong>STEVEN X</strong></h5>
                    <p>Il n'y a pas d'autres utilisateurs disponibles pour le moment.</p>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($utilisateurs as $user): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                style="width: 60px; height: 60px; font-size: 24px;">
                                <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                            </div>
                        </div>

                        <h5 class="card-title"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                        </h5>
                        <p class="card-text text-muted">
                            <small><?php echo htmlspecialchars($user['mail']); ?></small><br>
                            <small><?php echo $user['sexe']; ?></small>
                        </p>

                        <div class="d-grid gap-2">
                            <?php if ($user['demande_statut'] == 'accepte'): ?>
                            <span class="btn btn-success disabled">✅ Déjà ami</span>
                            <?php elseif ($user['type_demande'] == 'envoyee' && $user['demande_statut'] == 'en_attente'): ?>
                            <span class="btn btn-warning disabled">⏳ Demande envoyée</span>
                            <?php elseif ($user['type_demande'] == 'recue' && $user['demande_statut'] == 'en_attente'): ?>
                            <a href="amis.php" class="btn btn-info">📩 Répondre à la demande</a>
                            <?php else: ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="ajouter">
                                <button type="S'inscrire" class="btn btn-primary mb-2">
                                    👤 Ajouter comme ami
                                </button>
                            </form>
                            <?php endif; ?>

                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="bloquer">
                                <button type="S'inscrire" class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('Êtes-vous sûr de vouloir bloquer cet utilisateur ?')">
                                    🚫 Bloquer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>