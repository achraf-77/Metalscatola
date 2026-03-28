<?php
require __DIR__ . "/cnx.php";

$stmt = $conn->query("
    SELECT *
    FROM livraison_historique
    ORDER BY date_livraison DESC
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des Livraisons — Metalscatola</title>
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
        <a href="livraison_search.php" class="btn primary">Livraison</a>
        <a href="livraison.php" class="btn primary">📦 Commandes</a>
        <a href="historique_livraison.php" class="btn primary active">Hist. livraison</a>
    </nav>

    <h2>📦 Historique des Livraisons</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>REF</th>
                    <th>Quantité livrée</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:30px;">Aucune livraison enregistrée.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['ref']) ?></td>
                            <td><?= (int)$r['livraison'] ?></td>
                            <td><?= htmlspecialchars((string)$r['date_livraison']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>