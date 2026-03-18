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
    <title>Historique Livraison</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container wide">

    <div class="topbar">
        <div class="brand">
            <img src="assets/logo.png" class="logo-img">
        </div>

        <div class="actions">
            <a class="btn primary" href="livraison_search.php">← Livraison</a>
            <a class="btn primary" href="livraison.php">📦 Commandes en attente</a>
        </div>
    </div>

    <h2>Historique des Livraisons</h2>

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
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['ref']) ?></td>
                        <td><?= (int)$r['livraison'] ?></td>
                        <td><?= $r['date_livraison'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

</div>

</body>
</html>