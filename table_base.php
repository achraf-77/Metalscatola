<?php
require __DIR__ . "/cnx.php";

function render_table_page(PDO $conn, string $title, string $whereSql, array $params = []): void
{
  $sql = "SELECT ref, description, format, client, stock_pf, stock_fb, stock, arrivage, cde_italie, couverture
          FROM appro
          $whereSql
          ORDER BY format, ref";
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $t = strtoupper($title);
  $isD109 = str_contains($t, 'D109');
  $isD180 = str_contains($t, 'D180');
  $isD305 = str_contains($t, 'D305');
  $isAutres = str_contains(strtolower($title), 'autres');
?>
  <!doctype html>
  <html lang="fr">

  <head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="style.css">
  </head>

  <body>
    <div class="container wide">

      <nav>
        <a href="form.php">+ Ajouter</a>
        <a href="table_d109.php" class="<?= $isD109 ? 'active' : '' ?>">D109</a>
        <a href="table_d180.php" class="<?= $isD180 ? 'active' : '' ?>">D180</a>
        <a href="table_d305.php" class="<?= $isD305 ? 'active' : '' ?>">D305</a>
        <a href="table_autres.php" class="<?= $isAutres ? 'active' : '' ?>">Autres</a>
        <a class="btn primary" href="import_excel.php">Importer Excel</a>
        <a class="btn primary" href="table_last.php">Dernier Excel</a>
        <a class="btn primary" href="historique.php">Historique</a>
        <a class="btn primary" href="livraison.php">livraison</a>
      </nav>

      <h2><?= htmlspecialchars($title) ?> (<?= count($rows) ?>)</h2>

      <?php if (!$rows): ?>
        <div class="table-wrapper">
          <div class="empty-state">Aucune donnée.</div>
        </div>
      <?php else: ?>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>REF</th>
                <th>DESCRIPTION</th>
                <th>FORMAT</th>
                <th>CLIENT</th>
                <th>STOCK PF</th>
                <th>STOCK FB</th>
                <th>STOCK</th>
                <th>ARRIVAGE</th>
                <th>CDE ITALIE</th>
                <th>COUVERTURE</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td title="<?= htmlspecialchars($r['ref']) ?>"><?= htmlspecialchars($r['ref']) ?></td>
                  <td title="<?= htmlspecialchars((string)$r['description']) ?>"><?= htmlspecialchars((string)$r['description']) ?></td>
                  <td title="<?= htmlspecialchars((string)$r['format']) ?>"><?= htmlspecialchars((string)$r['format']) ?></td>
                  <td title="<?= htmlspecialchars((string)$r['client']) ?>"><?= htmlspecialchars((string)$r['client']) ?></td>
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
<?php } ?>