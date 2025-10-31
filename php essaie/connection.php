<?php
session_start();
include('bdd.php');


$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mail = trim($_POST['mail']);
    $mot_de_passe = $_POST['mot_de_passe'];
    
    if (empty($mail) || empty($mot_de_passe)) {
        $error = "Veuillez remplir tous les champs";
    } else {
            // Rechercher l'utilisateur par email
            $stmt = $pdo->prepare("SELECT id, nom, prenom, mdp FROM user WHERE mail = ?");
            $stmt->execute([$mail]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($mot_de_passe, $user['mdp'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_prenom'] = $user['prenom'];
                $_SESSION['user_mail'] = $mail;
                
                header('Location: dash.php');
                exit();
            } else {
                $error = "Email ou mot de passe incorrect";
            }
        
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
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
                        <h3 class="text-center">Connexion</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="mail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="mail" name="mail"
                                    value="<?php echo isset($_POST['mail']) ? htmlspecialchars($_POST['mail']) : ''; ?>"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="mot_de_passe" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe"
                                    required>
                            </div>

                            <div class="d-grid">
                                <button type="S'inscrire" class="btn btn-primary">Se connecter</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <p>Pas encore de compte ? <a href="index.php">S'inscrire</a></p>
                            <p><a href="mot_de_passe_oublie.php">Mot de passe oublié ?</a></p>
                            <p><a href="contact.php">Contactez-nous</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>