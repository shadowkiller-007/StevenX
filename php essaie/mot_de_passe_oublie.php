<?php
session_start();
include('bdd.php');

// Importer PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

 require 'src/Exception.php';
 require 'src/PHPMailer.php';
 require 'src/SMTP.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide";
    } else {
            
            // Vérifier si l'email existe
            $stmt = $pdo->prepare("SELECT id FROM user WHERE mail = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Générer un nouveau mot de passe conforme aux nouvelles règles
                $minuscules = 'abcdefghijklmnopqrstuvwxyz';
                $majuscules = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $chiffres = '0123456789';
                $speciaux = '!@#$%^&*';
                
                // Assurer au moins un caractère de chaque type
                $nouveau_mdp = '';
                $nouveau_mdp .= $minuscules[rand(0, strlen($minuscules) - 1)]; // 1 minuscule
                $nouveau_mdp .= $majuscules[rand(0, strlen($majuscules) - 1)]; // 1 majuscule
                $nouveau_mdp .= $chiffres[rand(0, strlen($chiffres) - 1)]; // 1 chiffre
                $nouveau_mdp .= $speciaux[rand(0, strlen($speciaux) - 1)]; // 1 spécial
                
                // Compléter avec 4 caractères aléatoires supplémentaires
                $tous_caracteres = $minuscules . $majuscules . $chiffres . $speciaux;
                for ($i = 0; $i < 4; $i++) {
                    $nouveau_mdp .= $tous_caracteres[rand(0, strlen($tous_caracteres) - 1)];
                }
                
                // Mélanger les caractères pour éviter un pattern prévisible
                $nouveau_mdp = str_shuffle($nouveau_mdp);
                
                $nouveau_mdp_crypte = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
                
                // Mettre à jour le mot de passe
                $stmt = $pdo->prepare("UPDATE user SET mdp = ? WHERE id = ?");
                $stmt->execute([$nouveau_mdp_crypte, $user['id']]);
                
                // Envoi d'email avec PHPMailer
                $mail = new PHPMailer(true);
                
                try {
                    // Configuration du serveur SMTP
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; // Changez selon votre fournisseur
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'stevenamorin202@gmail.com'; // Votre email
                    $mail->Password   = 'yopitkiepmqkjctq'; // Mot de passe d'application
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    
                    // Configuration de l'encodage
                    $mail->CharSet = 'UTF-8';
                    
                    // Destinataires
                    $mail->setFrom('stevenamorin202@gmail.com', 'STEVEN X');
                    $mail->addAddress($email);
                    
                    // Contenu de l'email
                    $mail->isHTML(true);
                    $mail->Subject = 'Nouveau mot de passe - STEVEN X';
                    
                    $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                            .content { padding: 20px; background-color: #f8f9fa; }
                            .password { background-color: #e9ecef; padding: 10px; font-family: monospace; font-size: 18px; font-weight: bold; text-align: center; margin: 10px 0; }
                            .footer { padding: 20px; text-align: center; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h1>STEVEN X</h1>
                                <h2>Nouveau mot de passe</h2>
                            </div>
                            <div class="content">
                                <p>Bonjour,</p>
                                <p>Vous avez demandé la réinitialisation de votre mot de passe. Voici votre nouveau mot de passe :</p>
                                <div class="password">' . htmlspecialchars($nouveau_mdp) . '</div>
                                <p><strong>Important :</strong> Nous vous recommandons fortement de changer ce mot de passe après votre première connexion pour des raisons de sécurité.</p>
                                <p>Si vous n\'avez pas demandé cette réinitialisation, veuillez nous contacter immédiatement.</p>
                            </div>
                            <div class="footer">
                                <p>Cordialement,<br>L\'équipe STEVEN X</p>
                            </div>
                        </div>
                    </body>
                    </html>';
                    
                    $mail->AltBody = "Bonjour,\n\nVotre nouveau mot de passe est : $nouveau_mdp\n\nNous vous recommandons de le changer après votre connexion.\n\nCordialement,\nL'équipe STEVEN X";
                    
                    $mail->send();
                    $success = true;
                    
                } catch (Exception $e) {
                    $error = "Erreur lors de l'envoi de l'email. Veuillez réessayer plus tard.";
            
                }
                
            } else {
                $error = "Aucun compte trouvé avec cet email";
            }
            
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-secondary">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h1 class="text-center fw-bolder text-black display-1"><img src="logo/StevenX.png" alt="" class="m-2"
                        style="width: 80px; height: 80px; border-radius: 50%;">STEVEN X</h1>
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="text-center">Mot de passe oublié</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h5>Nouveau mot de passe envoyé !</h5>
                            <p>Un nouveau mot de passe a été envoyé à votre adresse email.</p>
                            <p class="mb-0">Vérifiez votre boîte de réception (et vos spams si nécessaire).</p>
                            <div class="text-center mt-3">
                                <a href="connection.php" class="btn btn-primary">Se connecter</a>
                            </div>
                        </div>
                        <?php else: ?>
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <p class="text-muted text-center mb-4">
                            Entrez votre adresse email pour recevoir un nouveau mot de passe.
                        </p>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                    required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="S'inscrire" class="btn btn-primary">Envoyer nouveau mot de passe</button>
                                <a href="connection.php" class="btn btn-outline-secondary">Retour à la connexion</a>
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