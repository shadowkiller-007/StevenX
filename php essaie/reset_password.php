<?php
// reset_password.php
require_once "connection.php";

$message = "";
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_reset_tokens WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset && strtotime($reset['expires_at']) > time()) {
        // Hasher et enregistrer le nouveau mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $conn->prepare("UPDATE user SET password = ? WHERE id = ?")
             ->execute([$hashedPassword, $reset['user_id']]);

        // Supprimer le token
        $conn->prepare("DELETE FROM password_reset_tokens WHERE token = ?")
             ->execute([$token]);

        $message = "Mot de passe mis à jour. Vous pouvez maintenant vous connecter.";
    } else {
        $message = "Lien expiré ou invalide.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Réinitialiser le mot de passe</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-secondary">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">Nouveau mot de passe</div>
                    <div class="card-body">
                        <?php if (!empty($message)) : ?>
                        <div class="alert alert-info"> <?= htmlspecialchars($message) ?> </div>
                        <?php endif; ?>

                        <?php if ($token && empty($message)) : ?>
                        <form method="POST">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="S'inscrire" class="btn btn-success">Réinitialiser</button>
                        </form>
                        <?php endif; ?>
                        <a href="index.php" class="d-block mt-3">Retour à la connexion</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>