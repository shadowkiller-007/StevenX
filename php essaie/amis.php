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

$success = false;
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Gestion du déblocage
   if (isset($_POST['debloquer_user']) && isset($_POST['user_id_a_debloquer'])) {
        $user_id_a_debloquer = $_POST['user_id_a_debloquer'];
        
        try {
            // Vérifier d'abord si le blocage existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs_bloques WHERE bloqueur_id = ? AND bloque_id = ?");
            $stmt->execute([$_SESSION['user_id'], $user_id_a_debloquer]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                // Supprimer le blocage
                $stmt = $pdo->prepare("DELETE FROM utilisateurs_bloques WHERE bloqueur_id = ? AND bloque_id = ?");
                $result = $stmt->execute([$_SESSION['user_id'], $user_id_a_debloquer]);
                $affected_rows = $stmt->rowCount();
                
                if ($affected_rows > 0) {
                    $success = true;
                } else {
                    $error = "Aucune ligne supprimée";
                }
            } else {
                $error = "Ce blocage n'existe pas";
            }
        } catch (PDOException $e) {
            $error = "Erreur base de données: " . $e->getMessage();
        }
    }
    
    // Autres traitements...
    if (isset($_POST['action']) && isset($_POST['demande_id'])) {
        $demande_id = $_POST['demande_id'];
        $action = $_POST['action'];
        
        if ($action == 'accepter') {
            $stmt = $pdo->prepare("UPDATE demandes_amis SET statut = 'accepte', date_reponse = NOW() WHERE id = ? AND receveur_id = ?");
            $stmt->execute([$demande_id, $_SESSION['user_id']]);
            
            $stmt = $pdo->prepare("SELECT demandeur_id FROM demandes_amis WHERE id = ?");
            $stmt->execute([$demande_id]);
            $demandeur_id = $stmt->fetchColumn();
            
            $message = $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'] . ' a accepté votre demande d\'ami.';
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, element_id) VALUES (?, 'ami_accepte', ?, ?)");
            $stmt->execute([$demandeur_id, $message, $_SESSION['user_id']]);
            
        } elseif ($action == 'refuser') {
            $stmt = $pdo->prepare("UPDATE demandes_amis SET statut = 'refuse', date_reponse = NOW() WHERE id = ? AND receveur_id = ?");
            $stmt->execute([$demande_id, $_SESSION['user_id']]);
        }
    }
    
    if (isset($_POST['bloquer_ami']) && isset($_POST['ami_id'])) {
        $ami_id = $_POST['ami_id'];
        
        try {
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs_bloques WHERE bloqueur_id = ? AND bloque_id = ?");
            $stmt->execute([$_SESSION['user_id'], $ami_id]);
            
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO utilisateurs_bloques (bloqueur_id, bloque_id, date_creation) VALUES (?, ?, NOW())");
                $stmt->execute([$_SESSION['user_id'], $ami_id]);
                
                $stmt = $pdo->prepare("DELETE FROM demandes_amis WHERE ((demandeur_id = ? AND receveur_id = ?) OR (demandeur_id = ? AND receveur_id = ?)) AND statut = 'accepte'");
                $stmt->execute([$_SESSION['user_id'], $ami_id, $ami_id, $_SESSION['user_id']]);
                
                $success = true;
            }
        } catch (PDOException $e) {
            $error = "Erreur lors du blocage : " . $e->getMessage();
        }
    }
}

// Récupérer les demandes d'amis en attente
$stmt = $pdo->prepare("
    SELECT da.*, u.nom, u.prenom, u.mail, u.photo_profil 
    FROM demandes_amis da 
    JOIN user u ON da.demandeur_id = u.id 
    WHERE da.receveur_id = ? AND da.statut = 'en_attente'
    ORDER BY da.date_creation DESC
");
$stmt->execute([$_SESSION['user_id']]);
$demandes_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la liste des amis
$stmt = $pdo->prepare("
    SELECT u.*, 
           CASE 
               WHEN da.demandeur_id = ? THEN da.receveur_id
               ELSE da.demandeur_id
           END as ami_id,
           da.date_reponse as date_amitie
    FROM demandes_amis da
    JOIN user u ON (
        (da.demandeur_id = ? AND u.id = da.receveur_id) OR 
        (da.receveur_id = ? AND u.id = da.demandeur_id)
    )
    WHERE da.statut = 'accepte' 
    AND (da.demandeur_id = ? OR da.receveur_id = ?)
    AND u.id NOT IN (
        SELECT bloque_id FROM utilisateurs_bloques WHERE bloqueur_id = ?
    )
    ORDER BY da.date_reponse DESC
");
$stmt->execute([
    $_SESSION['user_id'], $_SESSION['user_id'], 
    $_SESSION['user_id'], $_SESSION['user_id'], 
    $_SESSION['user_id'], $_SESSION['user_id']
]);
$amis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les utilisateurs bloqués
$stmt = $pdo->prepare("
    SELECT u.id, u.nom, u.prenom, u.mail, u.photo_profil, ub.date_creation as date_blocage
    FROM utilisateurs_bloques ub
    JOIN user u ON ub.bloque_id = u.id
    WHERE ub.bloqueur_id = ?
    ORDER BY ub.date_creation DESC
");
$stmt->execute([$_SESSION['user_id']]);
$utilisateurs_bloques = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes amis</title>
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
                    <li class="nav-item"><a class="nav-link" href="dash.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="new_pub.php">New Pub</a></li>
                    <li class="nav-item"><a class="nav-link" href="list_pubs.php">List des Pubs</a></li>
                    <li class="nav-item"><a class="nav-link" href="explorer.php">Explorer</a></li>
                    <li class="nav-item"><a class="nav-link active" href="amis.php">Amis</a></li>
                    <li class="nav-item"><?php include '_navbar_notifications.php'; ?></li>
                    <li class="nav-item"><a class="nav-link" href="profil.php">Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
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

    <!-- Modal de déconnexion -->
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

        <!-- En-tête avec bouton amis bloqués -->
        <div class="row mb-4">
            <div class="col-12 bg-primary rounded-bottom rounded-end">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>👥 Gestion des amis sur <strong>STEVEN X</strong></h2>
                    <div>
                        <span class="badge bg-info me-2">User ID: <?php echo $_SESSION['user_id']; ?></span>
                        <?php if (!empty($utilisateurs_bloques)): ?>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                            data-bs-target="#utilisateursBloquesModal">
                            🚫 Amis bloqués (<?php echo count($utilisateurs_bloques); ?>)
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                            data-bs-target="#utilisateursBloquesModal">
                            🚫 Amis bloqués
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal des utilisateurs bloqués -->
        <div class="modal fade" id="utilisateursBloquesModal" tabindex="-1"
            aria-labelledby="utilisateursBloquesModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="utilisateursBloquesModalLabel">🚫 Utilisateurs bloqués</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (empty($utilisateurs_bloques)): ?>
                        <div class="alert alert-info text-center">
                            <h6>Aucun utilisateur bloqué</h6>
                            <p class="mb-0">Vous n'avez bloqué aucun utilisateur.</p>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <strong>Information :</strong> Vous avez bloqué <?php echo count($utilisateurs_bloques); ?>
                            utilisateur(s).
                        </div>

                        <div class="justify-content-center row">
                            <?php foreach ($utilisateurs_bloques as $user_bloque): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <?php if ($user_bloque['photo_profil'] && file_exists($user_bloque['photo_profil'])): ?>
                                            <img src="<?php echo htmlspecialchars($user_bloque['photo_profil']); ?>"
                                                alt="Photo de profil" class="rounded-circle"
                                                style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                            <div class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                                style="width: 50px; height: 50px; font-size: 20px;">
                                                <?php echo strtoupper(substr($user_bloque['prenom'], 0, 1) . substr($user_bloque['nom'], 0, 1)); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <h6 class="card-title">
                                            <?php echo htmlspecialchars($user_bloque['prenom'] . ' ' . $user_bloque['nom']); ?>
                                        </h6>
                                        <p class="card-text text-muted">
                                            <small><?php echo htmlspecialchars($user_bloque['mail']); ?></small><br>
                                            <small>Bloqué le
                                                <?php echo date('d/m/Y', strtotime($user_bloque['date_blocage'])); ?></small><br>
                                            <small class="text-info">ID: <?php echo $user_bloque['id']; ?></small>
                                        </p>

                                        <!-- Formulaire direct avec confirmation JavaScript -->
                                        <form method="POST"
                                            onS'inscrire="return confirm('Êtes-vous sûr de vouloir débloquer <?php echo htmlspecialchars($user_bloque['prenom'] . ' ' . $user_bloque['nom']); ?> ?')">
                                            <input type="hidden" name="user_id_a_debloquer"
                                                value="<?php echo $user_bloque['id']; ?>">
                                            <button type="S'inscrire" name="debloquer_user" class="btn btn-success btn-sm">
                                                🔓 Débloquer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section des demandes d'amis en attente -->
        <?php if (!empty($demandes_attente)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <h3>📋 Demandes d'amis en attente (<?php echo count($demandes_attente); ?>)</h3>
                <hr>
            </div>
            <?php foreach ($demandes_attente as $demande): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <?php if ($demande['photo_profil'] && file_exists($demande['photo_profil'])): ?>
                            <img src="<?php echo htmlspecialchars($demande['photo_profil']); ?>" alt="Photo de profil"
                                class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                            <div class="bg-warning text-dark rounded-circle d-inline-flex align-items-center justify-content-center"
                                style="width: 50px; height: 50px; font-size: 20px;">
                                <?php echo strtoupper(substr($demande['prenom'], 0, 1) . substr($demande['nom'], 0, 1)); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <h6 class="card-title">
                            <?php echo htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']); ?></h6>
                        <small class="text-muted d-block mb-3">
                            Demande envoyée le <?php echo date('d/m/Y', strtotime($demande['date_creation'])); ?>
                        </small>
                        <div class="d-grid gap-2 d-md-block">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="demande_id" value="<?php echo $demande['id']; ?>">
                                <input type="hidden" name="action" value="accepter">
                                <button type="S'inscrire" class="btn btn-success btn-sm">✅ Accepter</button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="demande_id" value="<?php echo $demande['id']; ?>">
                                <input type="hidden" name="action" value="refuser">
                                <button type="S'inscrire" class="btn btn-danger btn-sm">❌ Refuser</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Liste des amis -->
        <div class="row">
            <div class="col-12 bg-primary rounded-bottom rounded-end">
                <h3>👥 Mes amis (<?php echo count($amis); ?>)</h3>
            </div>
            <?php if (empty($amis)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <h5>Aucun ami pour le moment</h5>
                    <p>Vous n'avez pas encore d'amis. <a href="explorer.php">Explorer les utilisateurs</a> pour en
                        ajouter.</p>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($amis as $ami): ?>
            <div class="col-md-6 col-lg-4 mb-4"><br>
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <?php if ($ami['photo_profil'] && file_exists($ami['photo_profil'])): ?>
                            <img src="<?php echo htmlspecialchars($ami['photo_profil']); ?>" alt="Photo de profil"
                                class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                            <?php else: ?>
                            <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                style="width: 60px; height: 60px; font-size: 24px;">
                                <?php echo strtoupper(substr($ami['prenom'], 0, 1) . substr($ami['nom'], 0, 1)); ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <h5 class="card-title"><?php echo htmlspecialchars($ami['prenom'] . ' ' . $ami['nom']); ?></h5>
                        <p class="card-text text-muted">
                            <small><?php echo htmlspecialchars($ami['mail']); ?></small><br>
                            <small>Amis depuis le <?php echo date('d/m/Y', strtotime($ami['date_amitie'])); ?></small>
                        </p>

                        <div class="d-grid gap-2">
                            <a href="messagerie.php?user_id=<?php echo $ami['id']; ?>" class="btn btn-primary">
                                ✉ Envoyer un message
                            </a>

                            <form method="POST"
                                onS'inscrire="return confirm('Êtes-vous sûr de vouloir bloquer <?php echo htmlspecialchars($ami['prenom'] . ' ' . $ami['nom']); ?> ?')">
                                <input type="hidden" name="ami_id" value="<?php echo $ami['id']; ?>">
                                <button type="S'inscrire" name="bloquer_ami" class="btn btn-outline-danger btn-sm">🚫
                                    Bloquer</button>
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