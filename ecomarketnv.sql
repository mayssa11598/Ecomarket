-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2026 at 11:01 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12
CREATE DATABASE IF NOT EXISTS ecomarket;
USE ecomarket;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecomarket`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icone` varchar(50) DEFAULT '?',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `description`, `icone`, `created_at`) VALUES
(1, 'Bio & Zéro Déchet', 'Produits biologiques et sans emballage plastique', '🌱', '2026-04-25 13:28:36'),
(2, 'Artisanat Local', 'Créations faites main par des artisans locaux', '🏺', '2026-04-25 13:28:36'),
(3, 'Vêtements Éco', 'Mode écoresponsable et fibres naturelles', '👚', '2026-04-25 13:28:36'),
(4, 'Maison Naturelle', 'Produits ménagers naturels et écologiques', '🏡', '2026-04-25 13:28:36'),
(5, 'Alimentation Bio', 'Épicerie fine biologique et équitable', '🥗', '2026-04-25 13:28:36');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `adresse` text DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `email`, `mot_de_passe`, `adresse`, `telephone`, `is_admin`, `created_at`) VALUES
(1, 'Admin EcoMarket', 'admin@ecomarket.tn', '$2y$10$DeIUlbtBLFw2nfXnoNtV6eY5HEgA7n4F42ZJf4IdOcJAbu5okQXyK', 'Tunis, Tunisie', '+216 70 000 000', 1, '2026-04-25 13:28:36'),
(2, 'Sana Ben Ali', 'sana@email.tn', '$2y$10$pmoS70WSXVbJ0iQIKWkmXeKbl4GCSI41tt2rf2HFVOu9hOaio1Ueu', '12 Rue de la République, Tunis', '+216 22 111 222', 0, '2026-04-25 13:28:36'),
(3, 'Karim Trabelsi', 'karim@email.tn', '$2y$10$TTxKlAP2l2zNRtx7dmC7NuEAk1BCwQssBuqNeHkj8zxgDKm9y9QSm', '45 Avenue Habib Bourguiba, Sfax', '+216 25 333 444', 0, '2026-04-25 13:28:36'),
(4, 'Mayssa Ahmed', 'mayssa@gmail.com', '$2y$10$ZEXVm1a8cnF9MlLyDfx3C..VhxeIo/O0OywynsIutQa0UPbafgMn.', 'mourouj 1', '+216 96788033', 0, '2026-04-25 19:38:22');

-- --------------------------------------------------------

--
-- Table structure for table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `date_cmd` timestamp NOT NULL DEFAULT current_timestamp(),
  `statut` enum('en_attente','validee','expediee','livree','annulee') DEFAULT 'en_attente',
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commandes`
--

INSERT INTO `commandes` (`id`, `client_id`, `date_cmd`, `statut`, `total`, `notes`) VALUES
(1, 2, '2026-04-25 13:28:36', 'validee', 83.40, NULL),
(2, 3, '2026-04-25 13:28:36', 'expediee', 79.50, NULL),
(3, 2, '2026-04-25 13:28:36', 'en_attente', 59.00, NULL),
(4, 2, '2026-04-27 18:41:05', 'en_attente', 24.50, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `commandes_produits`
--

CREATE TABLE `commandes_produits` (
  `id` int(10) UNSIGNED NOT NULL,
  `commande_id` int(10) UNSIGNED NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL,
  `quantite` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `prix_unitaire` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commandes_produits`
--

INSERT INTO `commandes_produits` (`id`, `commande_id`, `produit_id`, `quantite`, `prix_unitaire`) VALUES
(1, 1, 1, 2, 18.90),
(2, 1, 2, 1, 24.50),
(3, 1, 7, 1, 12.50),
(4, 2, 6, 1, 45.00),
(5, 2, 3, 1, 59.00),
(6, 3, 3, 1, 59.00),
(7, 4, 2, 1, 24.50);

-- --------------------------------------------------------

--
-- Table structure for table `expeditions`
--

CREATE TABLE `expeditions` (
  `id` int(10) UNSIGNED NOT NULL,
  `commande_id` int(10) UNSIGNED NOT NULL,
  `date_expedition` date DEFAULT NULL,
  `adresse_livraison` text NOT NULL,
  `statut` enum('preparation','en_transit','livre','echec') DEFAULT 'preparation',
  `transporteur` varchar(100) DEFAULT NULL,
  `numero_suivi` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expeditions`
--

INSERT INTO `expeditions` (`id`, `commande_id`, `date_expedition`, `adresse_livraison`, `statut`, `transporteur`, `numero_suivi`) VALUES
(1, 2, '2025-04-10', '45 Avenue Habib Bourguiba, Sfax', 'en_transit', 'Aramex', 'ARX-TN-20250410-002'),
(2, 4, NULL, '12 Rue de la République, Tunis', 'preparation', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `produits`
--

CREATE TABLE `produits` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL CHECK (`prix` >= 0),
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT 'default.jpg',
  `categorie_id` int(10) UNSIGNED NOT NULL,
  `en_vedette` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produits`
--

INSERT INTO `produits` (`id`, `nom`, `description`, `prix`, `stock`, `image`, `categorie_id`, `en_vedette`, `created_at`) VALUES
(1, 'Savon Artisanal Argan', 'Savon 100% naturel à l\'huile d\'argan, fait à la main à Tunis.', 18.90, 50, 'https://savonnerieblanchou.ca/cdn/shop/files/savon-a-lhuile-dargan_612x408.jpg?v=1750017853', 2, 1, '2026-04-25 13:28:36'),
(2, 'Tote Bag Coton Bio', 'Sac réutilisable en coton biologique certifié GOTS, couture renforcée.', 24.50, 79, 'https://kadimage.fr/1048543-large_default/tote-bag-en-coton-bio-100-g-m2.jpg', 3, 1, '2026-04-25 13:28:36'),
(3, 'Kit Zéro Déchet', 'Ensemble complet : brosse bambou, gourde inox, paille réutilisable.', 59.00, 30, 'https://i.etsystatic.com/18244890/r/il/1f1a6d/3046281385/il_fullxfull.3046281385_nunt.jpg', 1, 1, '2026-04-25 13:28:36'),
(4, 'Huile d\'Olive Biologique', 'Huile d\'olive vierge extra, pressée à froid, certification bio.', 35.00, 100, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=400&q=80', 5, 1, '2026-04-25 13:28:36'),
(5, 'Bougies Cire d\'Abeille', 'Bougies artisanales à la cire d\'abeille, sans paraffine.', 22.00, 40, 'https://lafabriquedelabeille.fr/cdn/shop/files/bougie-cire-abeille-naturelle-francaise.jpg?v=1705656566&width=1500', 2, 0, '2026-04-25 13:28:36'),
(6, 'T-Shirt Lin Naturel', 'T-shirt en lin cultivé localement, teinture végétale, coupe unisexe.', 45.00, 60, 'https://images.unsplash.com/photo-1581655353564-df123a1eb820?w=400&q=80', 3, 1, '2026-04-25 13:28:36'),
(7, 'Savon Vaisselle Solide', 'Bloc de savon vaisselle zéro déchet, 200 lavages, 100% végétal.', 12.50, 90, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTnjumRFVRhdq_EHfiHFwBYpBozpkalEWTxmg&s', 4, 0, '2026-04-25 13:28:36'),
(8, 'Miel Artisanal des Monts', 'Miel pur de montagne, apiculture raisonnée, pot en verre consignable.', 28.00, 35, 'https://images.unsplash.com/photo-1471943311424-646960669fbc?w=400&q=80', 5, 0, '2026-04-25 13:28:36'),
(9, 'Panier Osier Traditionnel', 'Panier tressé à la main, fibres naturelles, durable et élégant.', 55.00, 20, 'https://image.made-in-china.com/202f0j00ripMCnOEZkoY/Rustic-Shallow-Elegant-Charming-Traditional-Wicker-Willow-Rattan-Basket.webp', 2, 0, '2026-04-25 13:28:36'),
(10, 'Crème Visage Aloé Vera', 'Crème hydratante bio à l\'aloé vera, sans conservateurs chimiques.', 32.00, 45, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTjVlROVDR36NQTuEz_Lbjk2VWPFJuQiQ0Vjg&s', 4, 1, '2026-04-25 13:28:36'),
(11, 'Savon Artisanal Argan', '', 29.99, 10, 'https://savonnerieblanchou.ca/cdn/shop/files/savon-a-lhuile-dargan_612x408.jpg?v=1750017853', 4, 0, '2026-04-28 20:49:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_commande_client` (`client_id`);

--
-- Indexes for table `commandes_produits`
--
ALTER TABLE `commandes_produits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cp_commande` (`commande_id`),
  ADD KEY `fk_cp_produit` (`produit_id`);

--
-- Indexes for table `expeditions`
--
ALTER TABLE `expeditions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `commande_id` (`commande_id`);

--
-- Indexes for table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_produit_categorie` (`categorie_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `commandes_produits`
--
ALTER TABLE `commandes_produits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `expeditions`
--
ALTER TABLE `expeditions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `fk_commande_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `commandes_produits`
--
ALTER TABLE `commandes_produits`
  ADD CONSTRAINT `fk_cp_commande` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cp_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `expeditions`
--
ALTER TABLE `expeditions`
  ADD CONSTRAINT `fk_expedition_commande` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `fk_produit_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
