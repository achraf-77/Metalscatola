-- =============================================================
-- METALSCATOLA — New Delivery Workflow
-- Run this SQL once in phpMyAdmin or MySQL shell
-- =============================================================

-- Table: commandes
-- Stores pending deliveries created via livraison_commande.php.
-- Each row represents one "commande" waiting to be physically delivered.
-- The row is DELETED once livraison is confirmed in livraison.php.

CREATE TABLE IF NOT EXISTS `commandes` (
    `id`             INT            NOT NULL AUTO_INCREMENT,
    `ref`            VARCHAR(100)   NOT NULL,
    `stock_pf`       INT            NOT NULL DEFAULT 0   COMMENT 'stock_pf at time of commande creation',
    `stock`          INT            NOT NULL DEFAULT 0   COMMENT 'stock total at time of commande creation',
    `commande`       INT            NOT NULL DEFAULT 0   COMMENT 'quantity ordered',
    `date_livraison` DATE           NOT NULL             COMMENT 'expected delivery date',
    `created_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ref` (`ref`),
    INDEX `idx_date` (`date_livraison`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pending deliveries — created in livraison_commande.php, deleted after livraison';
