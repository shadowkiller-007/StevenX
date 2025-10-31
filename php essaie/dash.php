<?php
session_start();
include('bdd.php');
if (!isset($_SESSION['user_id'])) {
    header('Location: connection.php');
    exit();
}

// Récupérer les informations de l'utilisateur connecté pour la photo
$stmt = $pdo->prepare("SELECT photo_profil FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
//Recup Pub
$sql = "
    SELECT 
        p.*, 
        u.nom, 
        u.prenom,
        u.photo_profil,
        (SELECT COUNT(*) FROM commentaires c WHERE c.publication_id = p.id) as nb_commentaires,
        (SELECT COUNT(*) FROM likes l WHERE l.publication_id = p.id AND l.type = 'like') as nb_likes,
        (SELECT COUNT(*) FROM likes l WHERE l.publication_id = p.id AND l.type = 'dislike') as nb_dislikes,
        (SELECT type FROM likes l WHERE l.publication_id = p.id AND l.user_id = ?) as user_reaction,
        (SELECT COUNT(*) FROM signalements s WHERE s.type = 'publication' AND s.element_id = p.id AND s.user_id = ?) as user_signaled
    FROM publications p 
    JOIN user u ON p.user_id = u.id 
";

if (!empty($search)) {
    $sql .= " WHERE p.contenu LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?";
    $stmt = $pdo->prepare($sql . " ORDER BY p.date_ajout DESC");
    $searchParam = '%' . $search . '%';
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $searchParam, $searchParam, $searchParam]);
} else {
    $stmt = $pdo->prepare($sql . " ORDER BY RAND()");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
}

$publications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $publication_id = $_POST['publication_id'];
    $action = $_POST['action'];
    
    if (in_array($action, ['like', 'dislike'])) {
        try {
            // Récupérer l'auteur de la publication
            $stmt = $pdo->prepare("SELECT user_id FROM publications WHERE id = ?");
            $stmt->execute([$publication_id]);
            $auteur_id = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT type FROM likes WHERE user_id = ? AND publication_id = ?");
            $stmt->execute([$_SESSION['user_id'], $publication_id]);
            $existing_reaction = $stmt->fetchColumn();
            
            $notification_needed = false;
            
            if ($existing_reaction) {
                if ($existing_reaction == $action) {
                    // Supprimer le like/dislike
                    $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND publication_id = ?");
                    $stmt->execute([$_SESSION['user_id'], $publication_id]);
                } else {
                    // Changer de like à dislike ou vice versa
                    $stmt = $pdo->prepare("UPDATE likes SET type = ? WHERE user_id = ? AND publication_id = ?");
                    $stmt->execute([$action, $_SESSION['user_id'], $publication_id]);
                    $notification_needed = true;
                }
            } else {
                // Nouveau like/dislike
                $stmt = $pdo->prepare("INSERT INTO likes (user_id, publication_id, type) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $publication_id, $action]);
                $notification_needed = true;
            }
            
            // Créer une notification pour l'auteur de la publication (seulement si ce n'est pas lui-même)
            if ($notification_needed && $auteur_id != $_SESSION['user_id']) {
                $type_notif = ($action == 'like') ? 'like' : 'dislike';
                $message = $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'] . ' a ' . ($action == 'like' ? 'aimé' : 'n\'a pas aimé') . ' votre publication.';
                
                // Supprimer l'ancienne notification du même type pour éviter les doublons
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND type IN ('like', 'dislike') AND element_id = ? AND message LIKE ?");
                $stmt->execute([$auteur_id, $publication_id, $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'] . '%']);
                
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, element_id, date_creation) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$auteur_id, $type_notif, $message, $publication_id]);
            }
            
            header('Location: dash.php' . (!empty($search) ? '?search=' . urlencode($search) : ''));
            exit();
            
        } catch (PDOException $e) {
            // Ignorer les erreurs
        }
    } elseif ($action == 'signaler') {
        try {
            // Vérifier si l'utilisateur a déjà signalé cette publication
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM signalements WHERE user_id = ? AND type = 'publication' AND element_id = ?");
            $stmt->execute([$_SESSION['user_id'], $publication_id]);
            $already_reported = $stmt->fetchColumn();
            
            if ($already_reported == 0) {
                // Récupérer la raison du signalement
                $raison = isset($_POST['raison_signalement']) ? $_POST['raison_signalement'] : 'Contenu inapproprié';
                
                $stmt = $pdo->prepare("INSERT INTO signalements (user_id, type, element_id, raison, date_creation) VALUES (?, 'publication', ?, ?, NOW())");
                $stmt->execute([$_SESSION['user_id'], $publication_id, $raison]);
                
                // Récupérer l'auteur de la publication
                $stmt = $pdo->prepare("SELECT user_id FROM publications WHERE id = ?");
                $stmt->execute([$publication_id]);
                $auteur_id = $stmt->fetchColumn();
                
                // Vérifier le nombre de signalements
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM signalements WHERE type = 'publication' AND element_id = ?");
                $stmt->execute([$publication_id]);
                $nb_signalements = $stmt->fetchColumn();
                
                if ($nb_signalements >= 5) {
                    // Supprimer la publication
                    $stmt = $pdo->prepare("DELETE FROM publications WHERE id = ?");
                    $stmt->execute([$publication_id]);
                    
                    // Notifier l'auteur de la suppression
                    if ($auteur_id != $_SESSION['user_id']) {
                        $message = "Votre publication a été supprimée suite à plusieurs signalements.";
                        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, element_id, date_creation) VALUES (?, 'suppression', ?, ?, NOW())");
                        $stmt->execute([$auteur_id, $message, $publication_id]);
                    }
                } else {
                    // Notifier l'auteur du signalement (seulement si ce n'est pas lui-même)
                    if ($auteur_id != $_SESSION['user_id']) {
                        $message = "Votre publication a été signalée par " . $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'] . " pour : " . $raison;
                        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, element_id, date_creation) VALUES (?, 'signalement', ?, ?, NOW())");
                        $stmt->execute([$auteur_id, $message, $publication_id]);
                    }
                }
            }
            
            header('Location: dash.php' . (!empty($search) ? '?search=' . urlencode($search) : ''));
            exit();
            
        } catch (PDOException $e) {
            // Ignorer les erreurs
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link active" href="dash.php">Accueil</a>
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
                    <li class="nav-item"><?php include '_navbar_notifications.php'; ?></li>
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

    <!-- Modal de signalement -->
    <div class="modal fade" id="signalementModal" tabindex="-1" aria-labelledby="signalementModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="signalementModalLabel">🚩 Signaler cette publication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" id="signalementForm">
                    <div class="modal-body">
                        <input type="hidden" name="publication_id" id="signalement_publication_id">
                        <input type="hidden" name="action" value="signaler">

                        <p>Pourquoi signalez-vous cette publication ?</p>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="raison_signalement" id="raison1"
                                value="Contenu inapproprié" checked>
                            <label class="form-check-label" for="raison1">
                                Contenu inapproprié
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="raison_signalement" id="raison2"
                                value="Spam ou publicité">
                            <label class="form-check-label" for="raison2">
                                Spam ou publicité
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="raison_signalement" id="raison3"
                                value="Harcèlement">
                            <label class="form-check-label" for="raison3">
                                Harcè_lement
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="raison_signalement" id="raison4"
                                value="Fausses informations">
                            <label class="form-check-label" for="raison4">
                                Fausses informations
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="raison_signalement" id="raison5"
                                value="Violence ou contenu choquant">
                            <label class="form-check-label" for="raison5">
                                Violence ou contenu choquant
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="raison_signalement" id="raison6"
                                value="Autre">
                            <label class="form-check-label" for="raison6">
                                Autre
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="S'inscrire" class="btn btn-warning">Signaler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2>Bienvenue, <?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?>!
                </h2>

                <!-- Barre de recherche -->
                <div class="row mt-3 mb-4">
                    <div class="col-md-8">
                        <form method="GET" class="d-flex">
                            <input type="text" class="form-control me-2" name="search"
                                placeholder="Rechercher des publications sur STEVEN X..."
                                value="<?php echo htmlspecialchars($search); ?>">
                            <button type="S'inscrire" class="btn btn-primary">Rechercher</button>
                            <?php if (!empty($search)): ?>
                            <a href="dash.php" class="btn btn-danger ms-2">Effacer</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>


            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h3>
                    <?php if (!empty($search)): ?>
                    Résultats de recherche pour "<?php echo htmlspecialchars($search); ?>" sur <strong>STEVEN X</strong>
                    <?php else: ?>
                    Publications récentes sur <strong>STEVEN X</strong>
                    <?php endif; ?>
                </h3>

                <?php if (empty($publications)): ?>
                <div class="alert alert-info">
                    <?php if (!empty($search)): ?>
                    Aucune publication trouvée pour votre recherche sur <strong>STEVEN X</strong>.
                    <?php else: ?>
                    Aucune publication disponible pour le moment sur <strong>STEVEN X</strong>.
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <?php foreach ($publications as $pub): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <!-- Photo de profil de l'auteur -->
                            <?php if ($pub['photo_profil'] && file_exists($pub['photo_profil'])): ?>
                            <img src="<?php echo htmlspecialchars($pub['photo_profil']); ?>" alt="Photo de profil"
                                class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                style="width: 50px; height: 50px; font-size: 20px;">
                                <?php echo strtoupper(substr($pub['prenom'], 0, 1) . substr($pub['nom'], 0, 1)); ?>
                            </div>
                            <?php endif; ?>

                            <div>
                                <h5 class="card-title mb-0">
                                    <?php echo htmlspecialchars($pub['prenom'] . ' ' . $pub['nom']); ?></h5>
                                <small class="text-muted">
                                    Publié le <?php echo date('d/m/Y à H:i', strtotime($pub['date_ajout'])); ?>
                                    <?php if ($pub['date_modification']): ?>
                                    - Modifié le
                                    <?php echo date('d/m/Y à H:i', strtotime($pub['date_modification'])); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>

                        <!-- Affichage des médias -->
                        <?php if ($pub['image']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($pub['image']); ?>" class="img-fluid mb-3"
                            style="max-height: 400px;" alt="Image de la publication">
                        <?php endif; ?>

                        <p class="card-text"><?php echo nl2br(htmlspecialchars($pub['contenu'])); ?></p>
                    </div>
                    <div class="card-footer">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex gap-2">
                                    <!-- Boutons Like/Dislike -->
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="publication_id" value="<?php echo $pub['id']; ?>">
                                        <input type="hidden" name="action" value="like">
                                        <button type="S'inscrire"
                                            class="btn btn-sm <?php echo ($pub['user_reaction'] == 'like') ? 'btn-success' : 'btn-outline-success'; ?>">
                                            👍 <?php echo $pub['nb_likes']; ?>
                                        </button>
                                    </form>

                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="publication_id" value="<?php echo $pub['id']; ?>">
                                        <input type="hidden" name="action" value="dislike">
                                        <button type="S'inscrire"
                                            class="btn btn-sm <?php echo ($pub['user_reaction'] == 'dislike') ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                            👎 <?php echo $pub['nb_dislikes']; ?>
                                        </button>
                                    </form>

                                    <!-- Commentaires -->
                                    <a href="commentaire_pub.php?id=<?php echo $pub['id']; ?>"
                                        class="btn btn-sm btn-outline-primary">
                                        💬 <?php echo $pub['nb_commentaires']; ?> commentaire(s)
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($pub['user_id'] != $_SESSION['user_id'] && $pub['user_signaled'] == 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal"
                                    data-bs-target="#signalementModal"
                                    onclick="document.getElementById('signalement_publication_id').value = '<?php echo $pub['id']; ?>'">
                                    🚩 Signaler
                                </button>
                                <?php elseif ($pub['user_signaled'] > 0): ?>
                                <small class="text-muted">Déjà signalé</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>