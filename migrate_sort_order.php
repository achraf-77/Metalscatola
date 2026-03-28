<?php
require __DIR__ . "/cnx.php";
try {
    $conn->exec("ALTER TABLE commandes ADD COLUMN IF NOT EXISTS sort_order INT NOT NULL DEFAULT 0");
    $conn->exec("UPDATE commandes SET sort_order = id WHERE sort_order = 0");
    echo "✅ Migration OK: sort_order column added/updated.";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
