<?php
require __DIR__ . "/cnx.php";

$stmt = $conn->query("
SELECT *
FROM appro_historique
WHERE date_import >= (
    SELECT MAX(date_import) FROM appro_historique
) - INTERVAL 10 SECOND;
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<head>
    <meta charset="UTF-8">
    <title>Metalscatola</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container wide">

        <div class="topbar">
            <div class="brand">
                <img src="assets/logo.png" alt="Metalscatola Afrique" class="logo-img">
            </div>
            <div class="actions">
                <a class="btn primary" href="form.php">+ Ajouter</a>
                <a class="btn primary" href="import_excel.php">Importer Excel</a>
                <a class="btn primary" href="historique.php">Historique</a>
            </div>
        </div>


<div class="container wide">
    <h2>Dernier Excel importé</h2>
    <div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>REF</th><th>Description</th><th>Format</th><th>Client</th>
                <th>Stock PF</th><th>Stock FB</th><th>Stock</th>
                <th>Arrivage</th><th>Cde Italie</th><th>Couverture</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['ref']) ?></td>
                <td><?= htmlspecialchars($r['description']) ?></td>
                <td><?= htmlspecialchars($r['format']) ?></td>
                <td><?= htmlspecialchars($r['client']) ?></td>
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
</body>