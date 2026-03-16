<?php
require __DIR__ . "/cnx.php"; 


if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $ref = $_POST['ref'];
    $livraison = (int)($_POST['livraison'] ?? 0);


    $stmt = $conn->prepare("SELECT stock_pf, stock_fb, arrivage, cde_italie FROM appro_historique WHERE ref=:ref ORDER BY date_import DESC LIMIT 1");
    $stmt->execute([':ref'=>$ref]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if($row){
        $new_stock_pf = max(0, $row['stock_pf'] - $livraison);
        $new_stock = $new_stock_pf + $row['stock_fb'];
        $new_couverture = $new_stock + $row['arrivage'] + $row['cde_italie'];

        $update = $conn->prepare("
            UPDATE appro_historique
            SET stock_pf=:stock_pf, stock=:stock, couverture=:couv
            WHERE ref=:ref AND date_import=(SELECT MAX(date_import) FROM appro_historique WHERE ref=:ref)
        ");
        $update->execute([
            ':stock_pf'=>$new_stock_pf,
            ':stock'=>$new_stock,
            ':couv'=>$new_couverture,
            ':ref'=>$ref
        ]);
    }

    header("Location: livraison.php");
    exit;
}


$stmt = $conn->query("SELECT * FROM appro_historique h1
                      WHERE date_import = (SELECT MAX(date_import) FROM appro_historique h2 WHERE h2.ref = h1.ref)
                      ORDER BY ref");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Livraison</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container wide">
<h2>Gestion des Livraisons</h2>
<div class="table-wrapper">
<table>
<thead>
<tr>
<th>REF</th><th>Description</th><th>Stock PF</th><th>Stock FB</th><th>Stock</th><th>Arrivage</th><th>Cde Italie</th><th>Couverture</th><th>Livraison</th>
</tr>
</thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
<td><?= htmlspecialchars($r['ref']) ?></td>
<td><?= htmlspecialchars($r['description']) ?></td>
<td><?= (int)$r['stock_pf'] ?></td>
<td><?= (int)$r['stock_fb'] ?></td>
<td><?= (int)$r['stock'] ?></td>
<td><?= (int)$r['arrivage'] ?></td>
<td><?= (int)$r['cde_italie'] ?></td>
<td><?= (int)$r['couverture'] ?></td>
<td>
<form method="post">
<input type="hidden" name="ref" value="<?= htmlspecialchars($r['ref']) ?>">
<input type="number" name="livraison" value="0" style="width:60px;">
<button class="btn green" type="submit">Appliquer</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</body>
</html>