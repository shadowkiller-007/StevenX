<?php
session_start();
include('bdd.php');


$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération des données du formulaire
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $date_naissance = $_POST['date_naissance'];
    $telephone = trim($_POST['telephone']);
    $sexe = $_POST['sexe'];
    $adresse = trim($_POST['adresse']);
    $mail = trim($_POST['mail']);
    $mot_de_passe = $_POST['mot_de_passe'];
    $confirmer_mot_de_passe = $_POST['confirmer_mot_de_passe'];
    
    // Validation des champs
    if (empty($nom)) $errors[] = "Le nom est requis";
    if (empty($prenom)) $errors[] = "Le prénom est requis";
    if (empty($date_naissance)) $errors[] = "La date de naissance est requise";
    if (empty($telephone)) $errors[] = "Le téléphone est requis";
    if (empty($sexe)) $errors[] = "Le sexe est requis";
    if (empty($adresse)) $errors[] = "L'adresse est requise";
    if (empty($mail)) $errors[] = "Le mail est requis";
    if (empty($mot_de_passe)) $errors[] = "Le mot de passe est requis";
    if (empty($confirmer_mot_de_passe)) $errors[] = "La confirmation du mot de passe est requise";
    
    // Vérification de l'âge (minimum 15 ans)
    if (!empty($date_naissance)) {
        $date_naissance_obj = new DateTime($date_naissance);
        $date_actuelle = new DateTime();
        $age = $date_actuelle->diff($date_naissance_obj)->y;
        
        if ($age < 15) {
            $errors[] = "Vous devez avoir au moins 15 ans pour vous inscrire sur ce site";
        }
    }
    
    // Validation du mot de passe (8 caractères minimum avec caractères spéciaux)
    if (!empty($mot_de_passe)) {
        if (strlen($mot_de_passe) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $mot_de_passe)) {
            $errors[] = "Le mot de passe doit contenir au moins un caractère spécial (!@#$%^&*(),.?\":{}|<>)";
        }
    }
    
    // Vérification que les mots de passe correspondent
    if ($mot_de_passe !== $confirmer_mot_de_passe) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    // Validation de l'email
    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Le format de l'email n'est pas valide";
    }
    
    // Si pas d'erreurs, traitement de l'inscription
    if (empty($errors)) {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE mail = ?");
            $stmt->execute([$mail]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Cet email est déjà utilisé";
            } else {
                // Crypter le mot de passe
                $mot_de_passe_crypte = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                
                // Insérer dans la base de données
                $stmt = $pdo->prepare("INSERT INTO user (nom, prenom, date_naissance, telephone, sexe, adresse, mail, mdp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nom, $prenom, $date_naissance, $telephone, $sexe, $adresse, $mail, $mot_de_passe_crypte]);
                
                $success = true;
                header("Location: dash.php");
            }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-secondary">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="text-center fw-bolder text-black display-1"><img src="logo/StevenX.png" alt="" class="m-2"
                        style="width: 80px; height: 80px; border-radius: 50%;">STEVEN X</h1>
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="text-center">Inscription</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            Inscription réussie ! Vous pouvez maintenant vous connecter.
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="nom" name="nom" pattern="[A-Z]{1,15}"
                                        title="Le nom d'utilisateur ne doit contenir que des lettres majuscules Ex:SHADOW"
                                        value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>"
                                        required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="prenom" class="form-label">Prénom</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom"
                                        pattern="[A-z]{1,15}"
                                        title="Le nom d'utilisateur ne doit contenir une lettre majuscule au début de chaque prénom Ex:Hunter"
                                        value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>"
                                        required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date_naissance" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control" id="date_naissance" name="date_naissance"
                                        value="<?php echo isset($_POST['date_naissance']) ? $_POST['date_naissance'] : ''; ?>"
                                        required>
                                    <small class="form-text text-muted">Vous devez avoir au moins 15 ans</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone"
                                        value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>"
                                        required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="sexe" class="form-label">Sexe</label>
                                    <select class="form-control" id="sexe" name="sexe" required>
                                        <option value="">Sélectionner</option>
                                        <option value="Homme"
                                            <?php echo (isset($_POST['sexe']) && $_POST['sexe'] == 'Homme') ? 'selected' : ''; ?>>
                                            Homme</option>
                                        <option value="Femme"
                                            <?php echo (isset($_POST['sexe']) && $_POST['sexe'] == 'Femme') ? 'selected' : ''; ?>>
                                            Femme</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="adresse" class="form-label">Adresse</label>
                                    <input type="text" class="form-control" id="adresse" name="adresse"
                                        value="<?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : ''; ?>"
                                        required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="mail" class="form-label">Mail</label>
                                <input type="mail" class="form-control" id="mail" name="mail"
                                    value="<?php echo isset($_POST['mail']) ? htmlspecialchars($_POST['mail']) : ''; ?>"
                                    required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="mot_de_passe" class="form-label">Mot de passe</label>
                                    <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe"
                                        required>
                                    <small class="form-text text-muted">Au moins 8 caractères avec un caractère
                                        spécial</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirmer_mot_de_passe" class="form-label">Confirmer mot de
                                        passe</label>
                                    <input type="password" class="form-control" id="confirmer_mot_de_passe"
                                        name="confirmer_mot_de_passe" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <button type="S'inscrire" class="btn btn-primary me-md-2">S'inscrire</button>
                                <a href="connection.php" class="btn btn-secondary">Se connecter</a>
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