<?php
session_start();
include('bdd.php');

$success = false;
$error = '';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $sujet = trim($_POST['sujet']);
    $message = trim($_POST['message']);
    
    // Validation
    if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
        $error = "Tous les champs sont requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide";
    } else {
            // Enregistrer le message de contact
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $stmt = $pdo->prepare("INSERT INTO contacts (user_id, nom, email, sujet, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $nom, $email, $sujet, $message]);
            
            // Préparer l'email pour l'admin
            $sujet_email = "Nouveau message de contact : " . $sujet;
            $contenu_email = "
Nouveau message de contact depuis STEVEN X reçu :

Nom : $nom
Email : $email
Sujet : $sujet

Message :
$message

---
Envoyé depuis le formulaire de contact de STEVEN X
Date : " . date('d/m/Y H:i:s') . "
";

            // Envoi de l'e-mail avec PHPMailer
            try {
                $mail = new PHPMailer(true);
                
                // Configuration du serveur SMTP
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';  // Utiliser Gmail SMTP
                $mail->SMTPAuth = true;
                $mail->Username = 'stevenamorin202@gmail.com';
                $mail->Password = 'yopitkiepmqkjctq';  // Mot de passe d'application
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                // Options SSL/TLS
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
                
                // Configuration de l'email
                $mail->setFrom($email, $nom);
                $mail->addAddress('stevenamorin202@gmail.com', 'Admin STEVEN X');
                $mail->addReplyTo($email, $nom);
                
                // Contenu de l'email
                $mail->isHTML(false);  // Texte brut
                $mail->Subject = $sujet_email;
                $mail->Body = $contenu_email;
                
                // Envoyer l'email
                $mail->send();
                $success = true;
                
            } catch (Exception $e) {
                $error = "Erreur lors de l'envoi de l'email : " . $mail->ErrorInfo;
            }
            
    }
}

// Préremplir les champs si l'utilisateur est connecté
$nom_defaut = '';
$email_defaut = '';
if (isset($_SESSION['user_id'])) {
    $nom_defaut = $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'];
    $email_defaut = $_SESSION['user_mail'];
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-secondary">
    <?php if (isset($_SESSION['user_id'])): ?>

    <?php endif; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="text-center fw-bolder text-black display-1"><img src="logo/StevenX.png" alt="" class="m-2"
                        style="width: 80px; height: 80px; border-radius: 50%;">STEVEN X</h1>
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="text-center">Contactez-nous</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success text-center">
                            <h5>✅ Message envoyé avec succès !</h5>
                            <p>Merci pour votre message. Notre équipe vous répondra dans les plus brefs délais.</p>
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="dash.php" class="btn btn-primary">Retour à l'accueil</a>
                            <?php else: ?>
                            <a href="connection.php" class="btn btn-primary">Se connecter</a>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom complet</label>
                                    <input type="text" class="form-control" id="nom" name="nom"
                                        value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : htmlspecialchars($nom_defaut); ?>"
                                        required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Adresse email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($email_defaut); ?>"
                                        required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="sujet" class="form-label">Sujet</label>
                                <select class="form-control" id="sujet" name="sujet" required>
                                    <option value="">Sélectionnez un sujet</option>
                                    <option value="Suggestion"
                                        <?php echo (isset($_POST['sujet']) && $_POST['sujet'] == 'Suggestion') ? 'selected' : ''; ?>>
                                        Suggestion d'amélioration</option>
                                    <option value="Probleme technique"
                                        <?php echo (isset($_POST['sujet']) && $_POST['sujet'] == 'Probleme technique') ? 'selected' : ''; ?>>
                                        Problème technique</option>
                                    <option value="Signalement"
                                        <?php echo (isset($_POST['sujet']) && $_POST['sujet'] == 'Signalement') ? 'selected' : ''; ?>>
                                        Signalement de contenu</option>
                                    <option value="Question"
                                        <?php echo (isset($_POST['sujet']) && $_POST['sujet'] == 'Question') ? 'selected' : ''; ?>>
                                        Question générale</option>
                                    <option value="Autre"
                                        <?php echo (isset($_POST['sujet']) && $_POST['sujet'] == 'Autre') ? 'selected' : ''; ?>>
                                        Autre</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="6"
                                    placeholder="Décrivez votre demande en détail..."
                                    required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                            </div>

                            <div class="text-center">
                                <button type="S'inscrire" class="btn btn-primary btn-lg">
                                    📧 Envoyer le message
                                </button>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="dash.php" class="btn btn-outline-secondary btn-lg ms-2">Annuler</a>
                                <?php else: ?>
                                <a href="connection.php" class="btn btn-outline-secondary btn-lg ms-2">Se connecter</a>
                                <?php endif; ?>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Informations de contact -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5>Autres moyens de nous contacter</h5>
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <h6>📧 Email</h6>
                                <p>stevenamorin202@gmail.com</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <h6>⏰ Horaires</h6>
                                <p>Lun-Ven : 9h-18h</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <h6>⚡ Réponse</h6>
                                <p>Sous 24-48h</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>