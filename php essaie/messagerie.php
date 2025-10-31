<?php
session_start();
include('bdd.php');
if (!isset($_SESSION['user_id'])) {
    header('Location: connection.php');
    exit();
}

if (!isset($_GET['user_id'])) {
    header('Location: amis.php');
    exit();
}

// Récupérer les informations de l'utilisateur connecté pour la photo
$stmt = $pdo->prepare("SELECT photo_profil FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

$destinataire_id = $_GET['user_id'];
$destinataire = null;
$messages = [];
$error = '';
$success = false;

// Vérifier que les utilisateurs sont amis
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM demandes_amis 
    WHERE statut = 'accepte' 
    AND ((demandeur_id = ? AND receveur_id = ?) OR (demandeur_id = ? AND receveur_id = ?))
");
$stmt->execute([$_SESSION['user_id'], $destinataire_id, $destinataire_id, $_SESSION['user_id']]);
$sont_amis = $stmt->fetchColumn() > 0;

if (!$sont_amis) {
    header('Location: amis.php');
    exit();
}

// Récupérer les infos du destinataire avec sa photo
$stmt = $pdo->prepare("SELECT nom, prenom, photo_profil FROM user WHERE id = ?");
$stmt->execute([$destinataire_id]);
$destinataire = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$destinataire) {
    header('Location: amis.php');
    exit();
}

// Traitement d'envoi de message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['contenu'])) {
    $contenu = trim($_POST['contenu']);
    
    if (empty($contenu)) {
        $error = "Le message ne peut pas être vide";
    } else {
        $stmt = $pdo->prepare("INSERT INTO messages (expediteur_id, destinataire_id, contenu) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $destinataire_id, $contenu]);
        // Créer une notification
        $message_notif = $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'] . ' vous a envoyé un message.';
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, element_id) VALUES (?, 'message', ?, ?)");
        $stmt->execute([$destinataire_id, $message_notif, $_SESSION['user_id']]);
        // Redirection pour éviter la duplication du message si on rafraîchit
        header("Location: messagerie.php?user_id=" . $destinataire_id);
        exit();
    }
}

// Marquer les messages reçus comme lus
$stmt = $pdo->prepare("UPDATE messages SET lu = TRUE WHERE expediteur_id = ? AND destinataire_id = ?");
$stmt->execute([$destinataire_id, $_SESSION['user_id']]);

// Récupérer tous les messages entre les deux utilisateurs avec photos
$stmt = $pdo->prepare("
    SELECT m.*, u.nom, u.prenom, u.photo_profil 
    FROM messages m 
    JOIN user u ON m.expediteur_id = u.id 
    WHERE (m.expediteur_id = ? AND m.destinataire_id = ?) 
       OR (m.expediteur_id = ? AND m.destinataire_id = ?)
    ORDER BY m.date_creation ASC
");
$stmt->execute([$_SESSION['user_id'], $destinataire_id, $destinataire_id, $_SESSION['user_id']]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie avec <?php echo htmlspecialchars($destinataire['prenom'] . ' ' . $destinataire['nom']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    .chat-container {
        height: 500px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1rem;
        background-color: #f8f9fa;
    }

    .message-sent {
        margin-left: 15%;
        margin-bottom: 15px;
        display: flex;
        justify-content: flex-end;
        align-items: flex-start;
    }

    .message-received {
        margin-right: 15%;
        margin-bottom: 15px;
        display: flex;
        justify-content: flex-start;
        align-items: flex-start;
    }

    .message-bubble {
        padding: 10px 15px;
        border-radius: 18px;
        max-width: 80%;
        word-wrap: break-word;
        position: relative;
    }

    .message-sent .message-bubble {
        background-color: #007bff;
        color: white;
    }

    .message-received .message-bubble {
        background-color: #e9ecef;
        color: #495057;
    }

    .message-avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        margin: 0 10px;
        flex-shrink: 0;
    }

    .message-sent .message-avatar {
        order: 2;
        margin-left: 10px;
        margin-right: 0;
    }

    .message-received .message-avatar {
        order: 1;
        margin-right: 10px;
        margin-left: 0;
    }

    .avatar-placeholder {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: bold;
        color: white;
    }
    </style>
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
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                        <div class="d-flex align-items-center">
                            <!-- Photo du destinataire dans l'en-tête -->
                            <?php if ($destinataire['photo_profil'] && file_exists($destinataire['photo_profil'])): ?>
                            <img src="<?php echo htmlspecialchars($destinataire['photo_profil']); ?>"
                                alt="Photo de profil" class="rounded-circle me-3"
                                style="width: 40px; height: 40px; object-fit: cover;">
                            <?php else: ?>
                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                style="width: 40px; height: 40px; font-size: 16px;">
                                <?php echo strtoupper(substr($destinataire['prenom'], 0, 1) . substr($destinataire['nom'], 0, 1)); ?>
                            </div>
                            <?php endif; ?>
                            <h5 class="mb-0">
                                💬 Conversation avec
                                <?php echo htmlspecialchars($destinataire['prenom'] . ' ' . $destinataire['nom']); ?>
                            </h5>
                        </div>
                        <a href="amis.php" class="btn btn-secondary btn-sm">← Retour aux amis</a>
                    </div>

                    <div class="card-body p-0">
                        <!-- Zone de chat -->
                        <div class="chat-container" id="chatContainer">
                            <?php if (empty($messages)): ?>
                            <div class="text-center text-muted mt-5">
                                <div class="mb-3">
                                    <?php if ($destinataire['photo_profil'] && file_exists($destinataire['photo_profil'])): ?>
                                    <img src="<?php echo htmlspecialchars($destinataire['photo_profil']); ?>"
                                        alt="Photo de profil" class="rounded-circle"
                                        style="width: 80px; height: 80px; object-fit: cover;">
                                    <?php else: ?>
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                        style="width: 80px; height: 80px; font-size: 32px;">
                                        <?php echo strtoupper(substr($destinataire['prenom'], 0, 1) . substr($destinataire['nom'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <p>Aucun message pour le moment.</p>
                                <p>Commencez la conversation avec
                                    <?php echo htmlspecialchars($destinataire['prenom']); ?> !</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                            <div
                                class="<?php echo ($msg['expediteur_id'] == $_SESSION['user_id']) ? 'message-sent' : 'message-received'; ?>">
                                <!-- Avatar -->
                                <?php if ($msg['photo_profil'] && file_exists($msg['photo_profil'])): ?>
                                <img src="<?php echo htmlspecialchars($msg['photo_profil']); ?>" alt="Photo de profil"
                                    class="message-avatar" style="object-fit: cover;">
                                <?php else: ?>
                                <div class="avatar-placeholder message-avatar"
                                    style="background-color: <?php echo ($msg['expediteur_id'] == $_SESSION['user_id']) ? '#007bff' : '#6c757d'; ?>;">
                                    <?php echo strtoupper(substr($msg['prenom'], 0, 1) . substr($msg['nom'], 0, 1)); ?>
                                </div>
                                <?php endif; ?>

                                <!-- Bulle de message -->
                                <div class="message-bubble">
                                    <div><?php echo nl2br(htmlspecialchars($msg['contenu'])); ?></div>
                                    <small class="d-block mt-1" style="opacity: 0.7; font-size: 0.75em;">
                                        <?php echo date('d/m/Y H:i', strtotime($msg['date_creation'])); ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-footer">
                        <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-3">
                            Message envoyé avec succès !
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger mb-3">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="input-group">
                                <textarea class="form-control" name="contenu" rows="2"
                                    placeholder="Tapez votre message..." required></textarea>
                                <button type="S'inscrire" class="btn btn-primary">
                                    📤 Envoyer
                                </button>
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