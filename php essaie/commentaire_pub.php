<?php
session_start();
include('bdd.php');
// Afficher le succès après redirection
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = true;
}
if (!isset($_SESSION['user_id'])) {
    header('Location: connection.php');
    exit();
}

// Récupérer les informations de l'utilisateur connecté pour la navbar
$current_user = null;
$stmt = $pdo->prepare("SELECT photo_profil FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

$publication = null;
$commentaires = [];
$success = false;
$error = '';

if (!isset($_GET['id'])) {
    header('Location: dash.php');
    exit();
}

$pub_id = $_GET['id'];

// Récupérer la publication
$stmt = $pdo->prepare("SELECT p.*, u.nom, u.prenom FROM publications p JOIN user u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$pub_id]);
$publication = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$publication) {
    header('Location: dash.php');
    exit();
}

// Récupérer les commentaires
$stmt = $pdo->prepare("
    SELECT c.*, u.nom, u.prenom,
           (SELECT COUNT(*) FROM signalements s WHERE s.type = 'commentaire' AND s.element_id = c.id AND s.user_id = ?) as user_signaled
    FROM commentaires c 
    JOIN user u ON c.user_id = u.id 
    WHERE c.publication_id = ? 
    ORDER BY c.date_creation ASC
");
$stmt->execute([$_SESSION['user_id'], $pub_id]);
$commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Traitement de l'ajout de commentaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['contenu'])) {
        $contenu = trim($_POST['contenu']);
        
        if (empty($contenu)) {
            $error = "Le commentaire ne peut pas être vide";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO commentaires (publication_id, user_id, contenu) VALUES (?, ?, ?)");
                $stmt->execute([$pub_id, $_SESSION['user_id'], $contenu]);
                
                // Créer une notification pour l'auteur de la publication
                $stmt = $pdo->prepare("SELECT user_id FROM publications WHERE id = ?");
                $stmt->execute([$pub_id]);
                $auteur_id = $stmt->fetchColumn();
                
                if ($auteur_id != $_SESSION['user_id']) {
                    $message = $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'] . ' a commenté votre publication.';
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, element_id) VALUES (?, 'commentaire', ?, ?)");
                    $stmt->execute([$auteur_id, $message, $pub_id]);
                }
                
                $success = true;
                
                // Récupérer les commentaires mis à jour
                $stmt = $pdo->prepare("
                    SELECT c.*, u.nom, u.prenom,
                           (SELECT COUNT(*) FROM signalements s WHERE s.type = 'commentaire' AND s.element_id = c.id AND s.user_id = ?) as user_signaled
                    FROM commentaires c 
                    JOIN user u ON c.user_id = u.id 
                    WHERE c.publication_id = ? 
                    ORDER BY c.date_creation ASC
                ");
                $stmt->execute([$_SESSION['user_id'], $pub_id]);
                $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $error = "Erreur lors de l'ajout du commentaire";
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
    <title>Commentaires</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-secondary">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <img src="logo/StevenX.png" alt="" class="m-2" style="width: 40px; height: 40px; border-radius: 50%;">
        <b><a class="navbar-brand" href="dash.php">STEVEN X</a></b>
        <div class="container">
            <a class="navbar-brand" href="dash.php">Mon Site</a>
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
                    <li class="nav-item">
                        <?php include '_navbar_notifications.php'; ?>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profil.php">Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul><!-- Affichage de la photo de profil dans la navbar -->
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
                <!-- Publication originale -->
                <?php if ($publication): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary">
                        <h4>Publication de
                            <?php echo htmlspecialchars($publication['prenom'] . ' ' . $publication['nom']); ?></h4>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($publication['contenu'])); ?></p>
                        <small class="text-muted">
                            Publié le <?php echo date('d/m/Y', strtotime($publication['date_ajout'])); ?>
                            <?php if ($publication['date_modification']): ?>
                            - Modifié le <?php echo date('d/m/Y H:i', strtotime($publication['date_modification'])); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>

                <!-- Section des commentaires -->
                <div class="card">
                    <div class="card-header bg-primary">
                        <h5>Commentaires (<?php echo count($commentaires); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            Commentaire ajouté avec succès !
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <!-- Formulaire d'ajout de commentaire -->
                        <form method="POST" class="mb-4">
                            <div class="mb-3">
                                <label for="contenu" class="form-label">Ajouter un commentaire</label>
                                <textarea class="form-control" id="contenu" name="contenu" rows="3"
                                    placeholder="Écrivez votre commentaire..." required></textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="dash.php" class="btn btn-secondary">Retour</a>
                                <button type="S'inscrire" class="btn btn-primary">Publier le commentaire</button>
                            </div>
                        </form>

                        <hr>

                        <!-- Liste des commentaires -->
                        <?php if (empty($commentaires)): ?>
                        <div class="text-center text-muted">
                            <p>Aucun commentaire pour cette publication.</p>
                            <p>Soyez le premier à commenter !</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($commentaires as $commentaire): ?>
                        <div class="border-start border-3 border-primary ps-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">
                                        <?php echo htmlspecialchars($commentaire['prenom'] . ' ' . $commentaire['nom']); ?>
                                    </h6>
                                    <small
                                        class="text-muted"><?php echo date('d/m/Y à H:i', strtotime($commentaire['date_creation'])); ?></small>
                                </div>
                                <div>
                                    <?php if ($commentaire['user_id'] != $_SESSION['user_id'] && $commentaire['user_signaled'] == 0): ?>
                                    <a href="signaler_commentaire.php?id=<?php echo $commentaire['id']; ?>&pub_id=<?php echo $pub_id; ?>"
                                        class="btn btn-outline-warning btn-sm">
                                        🚩
                                    </a>
                                    <?php elseif ($commentaire['user_signaled'] > 0): ?>
                                    <small class="text-muted">Signalé</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($commentaire['contenu'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>