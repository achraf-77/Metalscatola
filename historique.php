<?php
require __DIR__ . "/cnx.php";

$search = $_GET['search'] ?? '';

if ($search !== '') {
    $stmt = $conn->prepare("
        SELECT * FROM appro_historique
        WHERE ref LIKE :search
           OR description LIKE :search
           OR client LIKE :search
        ORDER BY date_import DESC
    ");
    $stmt->execute([':search' => "%$search%"]);
} else {
    $stmt = $conn->query("
        SELECT * FROM appro_historique
        ORDER BY date_import DESC
    ");
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des imports — Metalscatola</title>
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
        <a href="table_last.php" class="btn primary">Dernier Excel</a>
        <a href="livraison_search.php" class="btn primary">Livraison</a>
        <a href="historique.php" class="btn primary active">Historique</a>
    </nav>

    <h2>📋 Historique des imports</h2>

    <!-- Search bar -->
    <form method="get" style="margin-bottom:16px;display:flex;gap:10px;align-items:center;">
        <input
            type="text"
            name="search"
            placeholder="Rechercher REF / Description / Client"
            value="<?= htmlspecialchars($search) ?>"
            style="padding:8px 12px;width:280px;">
        <button class="btn primary" type="submit">Rechercher</button>
        <?php if ($search !== ''): ?>
            <a class="btn" href="historique.php">Effacer</a>
        <?php endif; ?>
    </form>

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
                    <th>Date Import</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="11" style="text-align:center;color:var(--text-muted);padding:30px;">Aucune donnée trouvée.</td></tr>
                <?php else: ?>
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
                            <td><?= htmlspecialchars((string)$r['date_import']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>