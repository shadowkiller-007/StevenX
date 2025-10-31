<?php
session_start();
include('bdd.php');
if (!isset($_SESSION['user_id'])) {
    header('Location: connection.php');
    exit();
}

if (!isset($_GET['id']) || !isset($_GET['pub_id'])) {
    header('Location: dash.php');
    exit();
}

$commentaire_id = $_GET['id'];
$publication_id = $_GET['pub_id'];
$commentaire = null;
$success = false;
$error = '';
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT photo_profil FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
}
    
    // Récupérer le commentaire
    $stmt = $pdo->prepare("SELECT c.*, u.nom, u.prenom FROM commentaires c JOIN user u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->execute([$commentaire_id]);
    $commentaire = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$commentaire) {
        header('Location: dash.php');
        exit();
    }
    
    // Vérifier si l'utilisateur a déjà signalé ce commentaire
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM signalements WHERE user_id = ? AND type = 'commentaire' AND element_id = ?");
    $stmt->execute([$_SESSION['user_id'], $commentaire_id]);
    $deja_signale = $stmt->fetchColumn() > 0;
    
    if ($deja_signale) {
        $error = "Vous avez déjà signalé ce commentaire";
    }

// Traitement du signalement
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($deja_signale) {
        $error = "Vous avez déjà signalé ce commentaire";
    } else {
        $motif = trim($_POST['motif']);
        $raison_details = trim($_POST['raison_details']);
        
        if (empty($motif)) {
            $error = "Veuillez sélectionner un motif de signalement";
        } else {
            $raison_complete = $motif;
            if (!empty($raison_details)) {
                $raison_complete .= " - " . $raison_details;
            }
            
            try {
                // Enregistrer le signalement
                $stmt = $pdo->prepare("INSERT INTO signalements (user_id, type, element_id, raison) VALUES (?, 'commentaire', ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $commentaire_id, $raison_complete]);
                
                // Vérifier le nombre total de signalements
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM signalements WHERE type = 'commentaire' AND element_id = ?");
                $stmt->execute([$commentaire_id]);
                $nb_signalements = $stmt->fetchColumn();
                
                if ($nb_signalements >= 5) {
                    // Supprimer le commentaire
                    $stmt = $pdo->prepare("DELETE FROM commentaires WHERE id = ?");
                    $stmt->execute([$commentaire_id]);
                    
                    // Notifier l'auteur
                    $message = "Votre commentaire a été supprimé suite à plusieurs signalements.";
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, element_id) VALUES (?, 'suppression', ?, ?)");
                    $stmt->execute([$commentaire['user_id'], $message, $commentaire_id]);
                    
                    $success = true;
                    $message_succes = "Commentaire signalé et supprimé (5 signalements atteints)";
                } else {
                    // Notifier l'auteur du signalement
                    if ($commentaire['user_id'] != $_SESSION['user_id']) {
                        $message = "Votre commentaire a été signalé par un utilisateur pour : " . $motif;
                        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, element_id) VALUES (?, 'signalement', ?, ?)");
                        $stmt->execute([$commentaire['user_id'], $message, $commentaire_id]);
                    }
                    
                    $success = true;
                    $message_succes = "Commentaire signalé avec succès";
                }
                
            } catch (PDOException $e) {
                $error = "Erreur lors du signalement";
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
    <title>Signaler un commentaire</title>
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
                    <div class="card-header bg-warning text-dark">
                        <h3>🚩 Signaler un commentaire</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h5>✅ <?php echo $message_succes; ?></h5>
                            <p>Merci de nous aider à maintenir une communauté saine.</p>
                            <div class="text-center">
                                <a href="commentaire_pub.php?id=<?php echo $publication_id; ?>"
                                    class="btn btn-primary">Retour aux commentaires</a>
                                <a href="dash.php" class="btn btn-outline-primary ms-2">Retour à l'accueil</a>
                            </div>
                        </div>
                        <?php elseif (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <div class="text-center">
                            <a href="commentaire_pub.php?id=<?php echo $publication_id; ?>"
                                class="btn btn-secondary">Retour aux commentaires</a>
                        </div>
                        <?php else: ?>
                        <!-- Aperçu du commentaire -->
                        <div class="alert alert-info">
                            <h6>Commentaire à signaler :</h6>
                            <div class="border p-3 mt-2 bg-light">
                                <strong>Par :</strong>
                                <?php echo htmlspecialchars($commentaire['prenom'] . ' ' . $commentaire['nom']); ?><br>
                                <strong>Date :</strong>
                                <?php echo date('d/m/Y à H:i', strtotime($commentaire['date_creation'])); ?><br>
                                <strong>Commentaire :</strong><br>
                                <?php echo nl2br(htmlspecialchars($commentaire['contenu'])); ?>
                            </div>
                        </div>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="motif" class="form-label">Motif du signalement </label>
                                <select class="form-control" id="motif" name="motif" required>
                                    <option value="">Sélectionnez un motif</option>
                                    <option value="Contenu inapproprié"
                                        <?php echo (isset($_POST['motif']) && $_POST['motif'] == 'Contenu inapproprié') ? 'selected' : ''; ?>>
                                        Contenu inapproprié</option>
                                    <option value="Harcèlement"
                                        <?php echo (isset($_POST['motif']) && $_POST['motif'] == 'Harcèlement') ? 'selected' : ''; ?>>
                                        Harcèlement</option>
                                    <option value="Spam"
                                        <?php echo (isset($_POST['motif']) && $_POST['motif'] == 'Spam') ? 'selected' : ''; ?>>
                                        Spam</option>
                                    <option value="Langage offensant"
                                        <?php echo (isset($_POST['motif']) && $_POST['motif'] == 'Langage offensant') ? 'selected' : ''; ?>>
                                        Langage offensant</option>
                                    <option value="Contenu violent"
                                        <?php echo (isset($_POST['motif']) && $_POST['motif'] == 'Contenu violent') ? 'selected' : ''; ?>>
                                        Contenu violent</option>
                                    <option value="Contenu à caractère sexuel"
                                        <?php echo (isset($_POST['motif']) && $_POST['motif'] == 'Contenu à caractère sexuel') ? 'selected' : ''; ?>>
                                        Contenu à caractère sexuel</option>
                                    <option value="Fausses informations"
                                        <?php echo (isset($_POST['motif']) && $_POST['motif'] == 'Fausses informations') ? 'selected' : ''; ?>>
                                        Fausses informations</option>
                                    <option value="Autre"
                                        <?php echo (isset($_POST['motif']) && $_POST['motif'] == 'Autre') ? 'selected' : ''; ?>>
                                        Autre</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="raison_details" class="form-label">Détails supplémentaires
                                    (optionnel)</label>
                                <textarea class="form-control" id="raison_details" name="raison_details" rows="3"
                                    placeholder="Expliquez en quelques mots pourquoi vous signalez ce commentaire..."><?php echo isset($_POST['raison_details']) ? htmlspecialchars($_POST['raison_details']) : ''; ?></textarea>
                                <div class="form-text">Aidez-nous à mieux comprendre le problème</div>
                            </div>

                            <div class="alert alert-warning">
                                <h6>⚠️ Important :</h6>
                                <ul class="mb-0">
                                    <li>Les signalements abusifs peuvent entraîner des sanctions</li>
                                    <li>Ce commentaire sera supprimé automatiquement après 5 signalements</li>
                                    <li>L'auteur sera notifié de ce signalement</li>
                                </ul>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <a href="commentaire_pub.php?id=<?php echo $publication_id; ?>"
                                    class="btn btn-secondary me-md-2">❌ Annuler</a>
                                <button type="S'inscrire" class="btn btn-warning">🚩 Valider le signalement</button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>