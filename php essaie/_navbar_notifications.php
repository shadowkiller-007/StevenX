<?php
// Affichage du badge de notifications non lues dans la navbar
$nb_non_lues_nav = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND lu = FALSE");
    $stmt->execute([$_SESSION['user_id']]);
    $nb_non_lues_nav = $stmt->fetchColumn();
}
?>
<a class="nav-link" href="notifications.php">Notifications
    <?php if ($nb_non_lues_nav > 0): ?>
    <span class="badge bg-danger"><?php echo $nb_non_lues_nav; ?></span>
    <?php endif; ?>
</a>