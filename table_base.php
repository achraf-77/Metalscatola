<?php
// table_base.php
require __DIR__ . "/cnx.php";

function render_table_page(PDO $conn, string $title, string $whereSql, array $params = []): void {
  $sql = "SELECT ref, description, format, client, stock_pf, stock_fb, stock, arrivage, cde_italie, couverture
          FROM v_appro
          $whereSql
          ORDER BY format, ref";
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  ?>
  <!doctype html>
  <html lang="fr">
  <head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
      body{font-family:Arial;margin:20px}
      table{border-collapse:collapse;width:100%;margin-bottom:20px}
      th,td{border:1px solid #ccc;padding:6px;font-size:13px}
      th{background:#f3f3f3}
      nav a{margin-right:12px}
    </style>
  </head>
  <body>
    <nav>
      <a href="form.php">+ Ajouter</a>
      <a href="table_d109.php">D109</a>
      <a href="table_d180.php">D180</a>
      <a href="table_d305.php">D305</a>
      <a href="table_autres.php">Autres</a>
    </nav>
    <hr>

    <h2><?= htmlspecialchars($title) ?> (<?= count($rows) ?>)</h2>

    <?php if (!$rows): ?>
      <p>Aucune donnée.</p>
    <?php else: ?>
      <table>
        <tr>
          <th>REF</th><th>Description</th><th>Format</th><th>Client</th>
          <th>Stock PF</th><th>Stock FB</th><th>Stock</th>
          <th>Arrivage</th><th>Cde Italie</th><th>Couverture</th>
        </tr>
        <?php foreach($rows as $r): ?>
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
            <td><?= htmlspecialchars((string)$r['couverture']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>

  </body>
  </html>
  <?php
}