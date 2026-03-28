<?php
require __DIR__ . "/cnx.php";

/* Fetch all rows from the most recent import batch
   (any row imported within 10 seconds of the very last date_import). */
$stmt = $conn->query("
    SELECT *
    FROM appro_historique
    WHERE date_import >= (
        SELECT MAX(date_import) FROM appro_historique
    ) - INTERVAL 10 SECOND
    ORDER BY format, ref
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dernier Excel importé — Metalscatola</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container wide">

    <nav>
        <a href="form.php">+ Ajouter</a>
        <a href="table_d109.php">D109</a>
        <a href="table_d180.php">D180</a>
        <a href="table_d305.php">D305</a>
        <a href="table_autres.php">Autres</a>
        <a href="import_excel.php" class="btn primary">📥 Importer Excel</a>
        <a href="table_last.php" class="btn primary active">Dernier Excel</a>
        <a href="livraison_search.php" class="btn primary">Livraison</a>
        <a href="historique.php" class="btn primary">Historique</a>
    </nav>

    <h2>📊 Dernier Excel importé (<?= count($rows) ?> ligne<?= count($rows) !== 1 ? 's' : '' ?>)</h2>

    <?php if (empty($rows)): ?>
        <div class="table-wrapper">
            <div class="empty-state">Aucun import trouvé. Importez d'abord un fichier Excel.</div>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>REF</th>
                        <th>Description</th>
                        <th>Format</th>
                        <th>Client</th>
                        <th>Stock PF</th>
                        <th>Stock FB</th>
                        <th>Stock</th>
                        <th>Arrivage</th>
                        <th>Cde Italie</th>
                        <th>Couverture</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['ref']) ?></td>
                            <td><?= htmlspecialchars((string)$r['description']) ?></td>
                            <td><?= htmlspecialchars((string)$r['format']) ?></td>
                            <td><?= htmlspecialchars((string)$r['client']) ?></td>
                            <td><?= (int)$r['stock_pf'] ?></td>
                            <td><?= (int)$r['stock_fb'] ?></td>
                            <td><?= (int)$r['stock'] ?></td>
                            <td><?= (int)$r['arrivage'] ?></td>
                            <td><?= (int)$r['cde_italie'] ?></td>
                            <td><?= (int)$r['couverture'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
</body>
</html>