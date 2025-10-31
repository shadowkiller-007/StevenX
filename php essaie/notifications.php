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

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Marquer toutes les notifications comme lues
    if (isset($_POST['marquer_lues'])) {
        try {
            $stmt = $pdo->prepare("UPDATE notifications SET lu = TRUE WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $success = true;
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour des notifications.";
        }
    }
    
    // Marquer une notification spécifique comme lue
    if (isset($_POST['marquer_lue']) && isset($_POST['notification_id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE notifications SET lu = TRUE WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['notification_id'], $_SESSION['user_id']]);
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour de la notification.";
        }
    }
    
    // Supprimer une notification
    if (isset($_POST['supprimer_notification']) && isset($_POST['notification_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['notification_id'], $_SESSION['user_id']]);
            $success = true;
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression de la notification.";
        }
    }
    
    // Supprimer toutes les notifications lues
    if (isset($_POST['supprimer_lues'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND lu = TRUE");
            $stmt->execute([$_SESSION['user_id']]);
            $success = true;
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression des notifications.";
        }
    }
}

try {
    // Récupérer les notifications avec les informations des utilisateurs
    $stmt = $pdo->prepare("
        SELECT n.*, 
               u.nom, u.prenom, u.mail,
               p.contenu as publication_contenu,
               p.image as publication_image,
               p.user_id as publication_auteur_id
        FROM notifications n
        LEFT JOIN user u ON n.element_id = u.id AND n.type IN ('demande_ami', 'ami_accepte')
        LEFT JOIN publications p ON n.element_id = p.id AND n.type IN ('like', 'dislike', 'commentaire', 'signalement', 'suppression')
        WHERE n.user_id = ? 
        ORDER BY n.date_creation DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Compter les notifications non lues
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND lu = FALSE");
    $stmt->execute([$_SESSION['user_id']]);
    $nb_non_lues = $stmt->fetchColumn();
    
    // Compter les notifications lues
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND lu = TRUE");
    $stmt->execute([$_SESSION['user_id']]);
    $nb_lues = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $notifications = [];
    $nb_non_lues = 0;
    $nb_lues = 0;
    $error = "Erreur lors du chargement des notifications.";
}

// Fonction pour obtenir l'icône selon le type de notification
function getNotificationIcon($type) {
    switch($type) {
        case 'like': return '👍';
        case 'dislike': return '👎';
        case 'commentaire': return '💬';
        case 'signalement': return '🚩';
        case 'suppression': return '🗑️';
        case 'demande_ami': return '👤';
        case 'ami_accepte': return '✅';
        case 'message': return '✉️';
        case 'nouvelle_publication': return '📝';
        default: return '📢';
    }
}

// Fonction pour obtenir la couleur de la bordure selon le type
function getNotificationBorderColor($type) {
    switch($type) {
        case 'like': return 'border-success';
        case 'dislike': return 'border-danger';
        case 'commentaire': return 'border-primary';
        case 'signalement': return 'border-warning';
        case 'suppression': return 'border-dark';
        case 'demande_ami': return 'border-info';
        case 'ami_accepte': return 'border-success';
        case 'message': return 'border-secondary';
        default: return 'border-light';
    }
}

// Fonction pour formater le temps écoulé
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'À l\'instant';
    if ($time < 3600) return floor($time/60) . ' min';
    if ($time < 86400) return floor($time/3600) . ' h';
    if ($time < 2592000) return floor($time/86400) . ' j';
    if ($time < 31536000) return floor($time/2592000) . ' mois';
    return floor($time/31536000) . ' an';
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    .notification-card {
        transition: all 0.3s ease;
    }

    .notification-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .notification-unread {
        background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
        border-left: 4px solid #007bff;
    }

    .notification-icon {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-right: 15px;
    }

    .notification-content {
        flex: 1;
    }

    .notification-time {
        font-size: 0.85em;
        color: #6c757d;
    }

    .notification-preview {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 10px;
        margin-top: 8px;
        border-left: 3px solid #dee2e6;
    }
    </style>
</head>

<body class="color bg-secondary">
    <nav class=" navbar navbar-expand-lg navbar-dark bg-primary">
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
                        <a class="nav-link active" href="notifications.php">
                            Notifications
                            <?php if ($nb_non_lues > 0): ?>
                            <span class="badge bg-danger"><?php echo $nb_non_lues; ?></span>
                            <?php endif; ?>
                        </a>
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
        <!-- Messages de succès/erreur -->
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            ✅ Action effectuée avec succès !
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            ❌ <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- En-tête des notifications -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2>🔔 Mes notifications sur <strong>STEVEN X</strong></h2>
                        <div class="d-flex gap-2 mt-2">
                            <span class="badge bg-warning fs-6"><?php echo $nb_non_lues; ?> non lue(s)</span>
                            <span class="badge bg-success fs-6"><?php echo $nb_lues; ?> lue(s)</span>
                            <span class="badge bg-info fs-6"><?php echo count($notifications); ?> total</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <?php if ($nb_non_lues > 0): ?>
                        <form method="POST" class="d-inline">
                            <button type="S'inscrire" name="marquer_lues" class="btn btn-primary">
                                ✅ Marquer toutes comme lues
                            </button>
                        </form>
                        <?php endif; ?>

                        <?php if ($nb_lues > 0): ?>
                        <form method="POST" class="d-inline">
                            <button type="S'inscrire" name="supprimer_lues" class="btn btn-danger"
                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer toutes les notifications lues ?')">
                                🗑️ Supprimer les lues
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des notifications -->
        <div class="row">
            <div class="col-12">
                <?php if (empty($notifications)): ?>
                <div class="alert alert-info text-center py-5">
                    <div class="fs-1 mb-3">📭</div>
                    <h4>Aucune notification</h4>
                    <p class="mb-0">Vous n'avez pas encore de notifications. Interagissez avec d'autres utilisateurs
                        pour en recevoir !</p>
                    <a href="dash.php" class="btn btn-primary mt-3">Explorer les publications</a>
                </div>
                <?php else: ?>

                <!-- Notifications non lues -->
                <?php 
                $notifications_non_lues = array_filter($notifications, function($notif) { return !$notif['lu']; });
                if (!empty($notifications_non_lues)): 
                ?>
                <div class="mb-5">
                    <h4 class="text-primary mb-4">
                        🆕 Nouvelles notifications (<?php echo count($notifications_non_lues); ?>)
                    </h4>

                    <?php foreach ($notifications_non_lues as $notif): ?>
                    <div
                        class="card notification-card notification-unread mb-3 <?php echo getNotificationBorderColor($notif['type']); ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <!-- Icône de notification -->
                                <div class="notification-icon bg-primary text-white">
                                    <?php echo getNotificationIcon($notif['type']); ?>
                                </div>

                                <!-- Contenu de la notification -->
                                <div class="notification-content">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold">
                                                <?php echo htmlspecialchars($notif['message']); ?>
                                            </h6>
                                            <div class="notification-time">
                                                📅 <?php echo timeAgo($notif['date_creation']); ?>
                                                • <?php echo date('d/m/Y à H:i', strtotime($notif['date_creation'])); ?>
                                            </div>

                                            <!-- Aperçu du contenu selon le type -->
                                            <?php if ($notif['type'] == 'like' || $notif['type'] == 'dislike' || $notif['type'] == 'commentaire' || $notif['type'] == 'signalement'): ?>
                                            <?php if (!empty($notif['publication_contenu'])): ?>
                                            <div class="notification-preview mt-2">
                                                <small class="text-muted">Publication concernée :</small><br>
                                                <span class="fw-medium">
                                                    <?php echo htmlspecialchars(substr($notif['publication_contenu'], 0, 100)); ?>
                                                    <?php if (strlen($notif['publication_contenu']) > 100) echo '...'; ?>
                                                </span>
                                                <?php if (!empty($notif['publication_image'])): ?>
                                                <div class="mt-2">
                                                    <img src="uploads/<?php echo htmlspecialchars($notif['publication_image']); ?>"
                                                        class="img-thumbnail"
                                                        style="max-width: 100px; max-height: 60px;" alt="Image">
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Actions -->
                                        <div class="d-flex gap-1 ms-3">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="notification_id"
                                                    value="<?php echo $notif['id']; ?>">
                                                <button type="S'inscrire" name="marquer_lue"
                                                    class="btn btn-sm btn-outline-primary" title="Marquer comme lue">
                                                    👁️
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="notification_id"
                                                    value="<?php echo $notif['id']; ?>">
                                                <button type="S'inscrire" name="supprimer_notification"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Supprimer cette notification ?')"
                                                    title="Supprimer">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Liens d'action selon le type -->
                                    <div class="mt-2">
                                        <?php if ($notif['type'] == 'like' || $notif['type'] == 'dislike' || $notif['type'] == 'commentaire'): ?>
                                        <a href="commentaire_pub.php?id=<?php echo $notif['element_id']; ?>"
                                            class="btn btn-sm btn-primary">
                                            👀 Voir la publication
                                        </a>
                                        <?php elseif ($notif['type'] == 'demande_ami'): ?>
                                        <a href="amis.php" class="btn btn-sm btn-success">
                                            👥 Gérer les demandes d'amis
                                        </a>
                                        <?php elseif ($notif['type'] == 'ami_accepte'): ?>
                                        <a href="amis.php" class="btn btn-sm btn-success">
                                            👥 Voir mes amis
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Notifications lues -->
                <?php 
                $notifications_lues = array_filter($notifications, function($notif) { return $notif['lu']; });
                if (!empty($notifications_lues)): 
                ?>
                <div class="mb-4">
                    <h4 class="text-dark mb-4">
                        📂 Notifications lues (<?php echo count($notifications_lues); ?>)
                    </h4>

                    <?php foreach ($notifications_lues as $notif): ?>
                    <div class="card notification-card mb-2 <?php echo getNotificationBorderColor($notif['type']); ?>"
                        style="opacity: 0.7;">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-start">
                                <!-- Icône de notification -->
                                <div class="notification-icon bg-secondary text-white"
                                    style="width: 35px; height: 35px; font-size: 16px;">
                                    <?php echo getNotificationIcon($notif['type']); ?>
                                </div>

                                <!-- Contenu de la notification -->
                                <div class="notification-content">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <span class="mb-1">
                                                <?php echo htmlspecialchars($notif['message']); ?>
                                            </span>
                                            <div class="notification-time">
                                                📅 <?php echo timeAgo($notif['date_creation']); ?>
                                            </div>
                                        </div>

                                        <!-- Action de suppression -->
                                        <form method="POST" class="d-inline ms-3">
                                            <input type="hidden" name="notification_id"
                                                value="<?php echo $notif['id']; ?>">
                                            <button type="S'inscrire" name="supprimer_notification"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Supprimer cette notification ?')"
                                                title="Supprimer">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>

        <!-- Footer avec statistiques -->
        <?php if (!empty($notifications)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <strong>
                            <h6 class="card-title">📊 Statistiques de vos notifications</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="border-end">
                                        <div class="fs-4 text-danger"><?php echo $nb_non_lues; ?></div>
                                        <small class="text-muted">Non lues</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border-end">
                                        <div class="fs-4 text-primary"><?php echo $nb_lues; ?></div>
                                        <small class="text-muted">Lues</small>
                                    </div>

                                </div>
                                <div class="col-md-3">
                                    <div class="border-end">
                                        <div class="fs-4 text-warning"><?php echo count($notifications); ?></div>
                                        <small class="text-muted">Total</small>
                                    </div>
                                </div>
                                <div class="col-md-3">

                                    <div class="fs-4 text-success">
                                        <?php echo count($notifications) > 0 ? round(($nb_lues / count($notifications)) * 100) : 0; ?>%
                                    </div>
                                    <small class="text-muted">Taux de lecture</small>

                                </div>
                            </div>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>