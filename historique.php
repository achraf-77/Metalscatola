<?php
require __DIR__ . "/cnx.php";

$search = $_GET['search'] ?? '';

if ($search != '') {
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
                <a class="btn primary" href="table_last.php">Dernier Excel</a>
                <a class="btn primary" href="livraison.php">livraison</a>
            </div>
        </div>

        <div class="container wide">

            <h2>Historique des imports</h2>

            <!-- SEARCH BAR -->
            <form method="get" style="margin-bottom:15px;">
                <input
                    type="text"
                    name="search"
                    placeholder="Rechercher REF / Description / Client"
                    value="<?= htmlspecialchars($search) ?>"
                    style="padding:8px;width:250px;">
                <button class="btn primary" type="submit">Search</button>
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

                        <?php foreach ($rows as $r): ?>

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
                                <td><?= $r['date_import'] ?></td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>
</body>