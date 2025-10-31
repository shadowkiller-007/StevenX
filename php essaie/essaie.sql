-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 01 août 2025 à 17:22
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `essaie`
--

-- --------------------------------------------------------

--
-- Structure de la table `commentaires`
--

CREATE TABLE `commentaires` (
  `id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `contenu` varchar(50) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commentaires`
--

INSERT INTO `commentaires` (`id`, `publication_id`, `user_id`, `contenu`, `date_creation`) VALUES
(1, 1, 2, 'yes', '2025-07-30 12:07:50'),
(2, 1, 2, 'yo comment', '2025-07-30 12:42:29'),
(3, 2, 2, 'yoooo niga', '2025-07-31 00:13:37'),
(4, 3, 2, 'nice', '2025-07-31 10:34:43'),
(5, 1, 1, 'Niga broooooo', '2025-07-31 16:00:44');

-- --------------------------------------------------------

--
-- Structure de la table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `sujet` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `demandes_amis`
--

CREATE TABLE `demandes_amis` (
  `id` int(11) NOT NULL,
  `demandeur_id` int(11) NOT NULL,
  `receveur_id` int(11) NOT NULL,
  `statut` enum('en_attente','accepte','refuse') DEFAULT 'en_attente',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_reponse` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `demandes_amis`
--

INSERT INTO `demandes_amis` (`id`, `demandeur_id`, `receveur_id`, `statut`, `date_creation`, `date_reponse`) VALUES
(5, 3, 2, 'en_attente', '2025-08-01 01:02:34', NULL),
(6, 3, 1, 'accepte', '2025-08-01 01:02:35', '2025-08-01 17:05:08');

-- --------------------------------------------------------

--
-- Structure de la table `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('like','dislike') NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `likes`
--

INSERT INTO `likes` (`id`, `publication_id`, `user_id`, `type`, `date_creation`) VALUES
(1, 1, 1, 'like', '2025-07-30 11:56:00'),
(2, 1, 2, 'like', '2025-07-30 12:07:35'),
(3, 2, 1, 'like', '2025-07-30 12:11:47'),
(4, 2, 2, 'like', '2025-07-31 00:13:21'),
(5, 3, 2, 'like', '2025-07-31 10:34:28'),
(6, 4, 3, 'like', '2025-08-01 16:29:41');

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `expediteur_id` int(11) NOT NULL,
  `destinataire_id` int(11) NOT NULL,
  `contenu` longtext NOT NULL,
  `lu` tinyint(1) DEFAULT 0,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`id`, `expediteur_id`, `destinataire_id`, `contenu`, `lu`, `date_creation`) VALUES
(1, 1, 2, 'man', 1, '2025-07-30 12:11:37'),
(2, 2, 1, 'oui frere \r\ncomment', 1, '2025-07-30 12:12:50'),
(3, 2, 1, 'oui frère', 1, '2025-07-31 16:11:56'),
(4, 1, 2, 'hi \r\nyes 👽👽👽💻💻💻👽👽👽', 0, '2025-08-01 17:05:44');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `element_id` int(11) DEFAULT NULL,
  `lu` tinyint(1) DEFAULT 0,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `element_id`, `lu`, `date_creation`) VALUES
(1, 1, 'demande_ami', 'jean ANDERSON vous a envoyé une demande d\'ami.', 2, 1, '2025-07-30 12:07:25'),
(3, 1, 'commentaire', 'jean ANDERSON a commenté votre publication.', 1, 1, '2025-07-30 12:07:50'),
(4, 2, 'ami_accepte', 'Marc DUJARDIN a accepté votre demande d\'ami.', 1, 1, '2025-07-30 12:11:05'),
(5, 2, 'message', 'Marc DUJARDIN vous a envoyé un message.', 1, 1, '2025-07-30 12:11:37'),
(6, 2, 'like', 'Marc DUJARDIN a aimé votre publication.', 2, 1, '2025-07-30 12:11:47'),
(7, 1, 'message', 'jean ANDERSON vous a envoyé un message.', 2, 1, '2025-07-30 12:12:50'),
(8, 1, 'commentaire', 'jean ANDERSON a commenté votre publication.', 1, 1, '2025-07-30 12:42:29'),
(10, 1, 'ami_accepte', 'jean ANDERSON a accepté votre demande d\'ami.', 2, 1, '2025-07-31 10:33:19'),
(12, 1, 'like', 'jean ANDERSON a aimé votre publication.', 3, 1, '2025-07-31 10:34:28'),
(13, 1, 'commentaire', 'jean ANDERSON a commenté votre publication.', 3, 1, '2025-07-31 10:34:43'),
(14, 2, 'demande_ami', 'Marc DUJARDIN vous a envoyé une demande d\'ami.', 1, 1, '2025-07-31 16:01:24'),
(15, 1, 'ami_accepte', 'jean ANDERSON a accepté votre demande d\'ami.', 2, 0, '2025-07-31 16:02:28'),
(16, 1, 'message', 'jean ANDERSON vous a envoyé un message.', 2, 0, '2025-07-31 16:11:56'),
(17, 1, 'signalement', 'Votre publication a été signalée par jean ANDERSON.', 3, 0, '2025-07-31 16:12:40'),
(18, 1, 'signalement', 'Votre commentaire a été signalé par un utilisateur pour : Contenu inapproprié', 5, 0, '2025-07-31 16:13:40'),
(19, 1, 'like', 'jean ANDERSON a aimé votre publication.', 1, 0, '2025-07-31 21:04:32'),
(20, 1, 'demande_ami', 'jean ANDERSON vous a envoyé une demande d\'ami.', 2, 0, '2025-07-31 23:34:35'),
(21, 2, 'ami_accepte', 'Marc DUJARDIN a accepté votre demande d\'ami.', 1, 0, '2025-07-31 23:58:21'),
(22, 2, 'demande_ami', 'Eliote ANDERSON vous a envoyé une demande d\'ami.', 3, 0, '2025-08-01 01:02:34'),
(23, 1, 'demande_ami', 'Eliote ANDERSON vous a envoyé une demande d\'ami.', 3, 0, '2025-08-01 01:02:35'),
(24, 3, 'ami_accepte', 'Marc DUJARDIN a accepté votre demande d\'ami.', 1, 0, '2025-08-01 17:05:08'),
(25, 2, 'message', 'Marc DUJARDIN vous a envoyé un message.', 1, 0, '2025-08-01 17:05:44');

-- --------------------------------------------------------

--
-- Structure de la table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expire_at` datetime NOT NULL,
  `utilise` tinyint(1) DEFAULT 0,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `publications`
--

CREATE TABLE `publications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `publications`
--

INSERT INTO `publications` (`id`, `user_id`, `contenu`, `date_ajout`, `date_modification`, `image`) VALUES
(1, 1, 'you gotta be', '2025-07-30 22:00:00', '2025-07-30 09:56:23', '6889ec476f2e2.jpg'),
(2, 2, 'WhatsApp', '2000-12-09 23:00:00', '2025-07-30 13:29:14', '688a1e2a2344d.jpg'),
(3, 1, 'hummmmmmmmmmmm', '2025-07-29 22:00:00', '2025-07-30 12:41:48', '688a130c8514d.png'),
(4, 3, 'Les gars comment allez vous ? \r\nc\'est Mr ROBOT', '2025-07-31 22:00:00', '2025-08-01 14:29:24', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `signalements`
--

CREATE TABLE `signalements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('publication','commentaire') NOT NULL,
  `element_id` int(11) NOT NULL,
  `raison` varchar(255) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `signalements`
--

INSERT INTO `signalements` (`id`, `user_id`, `type`, `element_id`, `raison`, `date_creation`) VALUES
(1, 2, 'publication', 3, 'Contenu inapproprié', '2025-07-31 16:12:40'),
(2, 2, 'commentaire', 5, 'Contenu inapproprié', '2025-07-31 16:13:40');

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `nom` varchar(10) NOT NULL,
  `prenom` varchar(20) NOT NULL,
  `date_naissance` date NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `sexe` varchar(50) NOT NULL,
  `adresse` varchar(255) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `photo_profil` varchar(255) NOT NULL,
  `mdp` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `nom`, `prenom`, `date_naissance`, `telephone`, `sexe`, `adresse`, `mail`, `photo_profil`, `mdp`) VALUES
(1, 'DUJARDIN', 'Marc', '1999-08-05', '+33157895385', 'Homme', 'RUSSIE', 'touchandie007@gmail.com', '', '$2y$10$K.Xvf4XkvMY63A4QL2YjuO8R0Tb23TJ2IoRP1On2aK/zJahPh1nKy'),
(2, 'ANDERSON', 'jean', '1999-12-28', '+33157895385', 'Femme', 'USA, Floride', '007shadowkiller007@gmail.com', '', '$2y$10$ecKJ6y3TwtHsnFcezgWoxO4N5vffgNt27rXy5K7YO1v/ge7a9ZRLu'),
(3, 'ANDERSON', 'Eliote', '1889-08-01', '+75057918895', 'Homme', 'RUSSIE,Moscou', 'vjfbvkjhfhsf@gmail.com', '', '$2y$10$xcbUEqz8/ZieJOSL5YjsHeC1A1ZR8Si1ZJeDLGBsKpOiqihOo8XL6');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs_bloques`
--

CREATE TABLE `utilisateurs_bloques` (
  `id` int(11) NOT NULL,
  `bloqueur_id` int(11) NOT NULL,
  `bloque_id` int(11) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `publication_id` (`publication_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `demandes_amis`
--
ALTER TABLE `demandes_amis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_demande` (`demandeur_id`,`receveur_id`),
  ADD KEY `receveur_id` (`receveur_id`);

--
-- Index pour la table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_publication` (`user_id`,`publication_id`),
  ADD KEY `publication_id` (`publication_id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expediteur_id` (`expediteur_id`),
  ADD KEY `destinataire_id` (`destinataire_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `publications`
--
ALTER TABLE `publications`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `signalements`
--
ALTER TABLE `signalements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_signalement` (`user_id`,`type`,`element_id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `utilisateurs_bloques`
--
ALTER TABLE `utilisateurs_bloques`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_blocage` (`bloqueur_id`,`bloque_id`),
  ADD KEY `bloque_id` (`bloque_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `commentaires`
--
ALTER TABLE `commentaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `demandes_amis`
--
ALTER TABLE `demandes_amis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT pour la table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `publications`
--
ALTER TABLE `publications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `signalements`
--
ALTER TABLE `signalements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `utilisateurs_bloques`
--
ALTER TABLE `utilisateurs_bloques`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD CONSTRAINT `commentaires_ibfk_1` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaires_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `demandes_amis`
--
ALTER TABLE `demandes_amis`
  ADD CONSTRAINT `demandes_amis_ibfk_1` FOREIGN KEY (`demandeur_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `demandes_amis_ibfk_2` FOREIGN KEY (`receveur_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`expediteur_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`destinataire_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `signalements`
--
ALTER TABLE `signalements`
  ADD CONSTRAINT `signalements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `utilisateurs_bloques`
--
ALTER TABLE `utilisateurs_bloques`
  ADD CONSTRAINT `utilisateurs_bloques_ibfk_1` FOREIGN KEY (`bloqueur_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `utilisateurs_bloques_ibfk_2` FOREIGN KEY (`bloque_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
