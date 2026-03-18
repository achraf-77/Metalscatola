<?php
require __DIR__ . "/cnx.php";

$error   = '';
$success = '';

/* ───────────────────────────────────────────────────────────
   POST — process a livraison
─────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $commande_id = (int)($_POST['commande_id'] ?? 0);
    $livraison   = (int)($_POST['livraison']   ?? 0);

    /* 1. Fetch the commande row */
    $stmt = $conn->prepare("SELECT * FROM commandes WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $commande_id]);
    $cmd = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cmd) {
        $error = "Commande introuvable (id={$commande_id}).";
    } elseif ($livraison <= 0) {
        $error = "La quantité livrée doit être supérieure à 0.";
    } elseif ($livraison > (int)$cmd['stock_pf']) {
        /* ── BLOCKED: livraison exceeds stock_pf ── */
        $error = "⛔ Livraison impossible : la quantité saisie ({$livraison}) dépasse le stock PF disponible ({$cmd['stock_pf']}) pour la ref <strong>" . htmlspecialchars($cmd['ref']) . "</strong>.";
    } else {
        /* 2. Fetch fresh stock from appro_historique */
        $s = $conn->prepare("
            SELECT stock_pf, stock_fb, stock, arrivage, cde_italie
            FROM appro_historique
            WHERE ref = :ref
            ORDER BY date_import DESC
            LIMIT 1
        ");
        $s->execute([':ref' => $cmd['ref']]);
        $row = $s->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $new_stock_pf  = max(0, (int)$row['stock_pf'] - $livraison);
            $new_stock     = max(0, (int)$row['stock']    - $livraison);
            $new_couverture = $new_stock_pf + (int)$row['stock_fb'] + (int)$row['arrivage'] + (int)$row['cde_italie'];

            /* 3. Update appro_historique (latest row only) */
            $upd = $conn->prepare("
                UPDATE appro_historique
                SET stock_pf = :stock_pf,
                    stock    = :stock,
                    couverture = :couv
                WHERE ref = :ref
                  AND date_import = (
                      SELECT MAX(date_import)
                      FROM appro_historique
                      WHERE ref = :ref
                  )
            ");
            $upd->execute([
                ':stock_pf' => $new_stock_pf,
                ':stock'    => $new_stock,
                ':couv'     => $new_couverture,
                ':ref'      => $cmd['ref'],
            ]);

            /* 4. Insert into livraison_historique */
            $ins = $conn->prepare("
                INSERT INTO livraison_historique (ref, livraison, date_livraison)
                VALUES (:ref, :livraison, :date_liv)
            ");
            $ins->execute([
                ':ref'      => $cmd['ref'],
                ':livraison'=> $livraison,
                ':date_liv' => date('Y-m-d'),
            ]);
        }

        /* 5. Delete the commande (fully delivered) */
        $del = $conn->prepare("DELETE FROM commandes WHERE id = :id");
        $del->execute([':id' => $commande_id]);

        header("Location: livraison.php?success=1");
        exit;
    }
}

/* ───────────────────────────────────────────────────────────
   GET — list all pending commandes
─────────────────────────────────────────────────────────── */
$commandes = $conn->query("
    SELECT * FROM commandes ORDER BY date_livraison ASC, created_at ASC
")->fetchAll(PDO::FETCH_ASSOC);

$successMsg = '';
if (isset($_GET['success'])) {
    $successMsg = "✔ Livraison enregistrée avec succès. Le stock a été mis à jour.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Livraison — Commandes en attente</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .alert-error {
            background: #fff5f5;
            border: 1.5px solid #ffa8a8;
            color: #c92a2a;
            border-radius: var(--radius-sm);
            padding: 14px 18px;
            font-size: .92rem;
            font-weight: 600;
            margin-bottom: 18px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert-success {
            background: #f0fff4;
            border: 1.5px solid #8ce99a;
            color: #2f9e44;
            border-radius: var(--radius-sm);
            padding: 14px 18px;
            font-size: .92rem;
            font-weight: 600;
            margin-bottom: 18px;
        }
        .livraison-input {
            width: 90px !important;
            text-align: center;
            font-weight: 700;
        }
        .empty-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 60px 20px;
            text-align: center;
        }
        .empty-icon { font-size: 3rem; margin-bottom: 12px; }
        .empty-text { color: var(--text-muted); font-size: 1rem; margin-bottom: 20px; }
        .date-badge {
            display: inline-block;
            font-size: .82rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 12px;
            background: var(--gray-100);
            color: var(--gray-700);
        }
        .date-badge.urgent {
            background: #fff3cd;
            color: #856404;
        }
        .date-badge.overdue {
            background: #fff5f5;
            color: #c92a2a;
        }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .count-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--brand-red-2);
            color: #fff;
            font-size: .82rem;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 20px;
        }
    </style>
</head>
<body>

<div class="container wide">

    <nav>
        <a href="form.php">+ Ajouter</a>
        <a href="import_excel.php">Importer Excel</a>
        <a href="table_last.php">Dernier Excel</a>
        <a href="historique.php">Historique</a>
        <a href="historique_livraison.php">Historique livraison</a>
        <a href="livraison.php" class="active">Commandes en attente</a>
        <a href="livraison_search.php">Livraison</a>
    </nav>

    <div class="page-header">
        <h2>📦 Commandes en attente de livraison</h2>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <?php if (count($commandes) > 0): ?>
                <span class="count-badge"><?= count($commandes) ?> commande<?= count($commandes) > 1 ? 's' : '' ?></span>
            <?php endif; ?>
            <a class="btn green" href="livraison_search.php">+ Nouvelle livraison</a>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert-success"><?= $successMsg ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-error"><span>⚠</span><span><?= $error ?></span></div>
    <?php endif; ?>

    <?php if (empty($commandes)): ?>
        <div class="empty-card">
            <div class="empty-icon">📭</div>
            <div class="empty-text">Aucune commande en attente de livraison.</div>
            <a class="btn green" href="livraison_search.php">+ Créer une commande</a>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>REF</th>
                        <th>Stock PF</th>
                        <th>Stock Total</th>
                        <th>Commandé</th>
                        <th>Date livraison</th>
                        <th>Quantité livrée</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $today = date('Y-m-d');
                    $in_two_days = date('Y-m-d', strtotime('+2 days'));
                    foreach ($commandes as $c):
                        $d = $c['date_livraison'];
                        $dateClass = '';
                        if ($d < $today)            $dateClass = 'overdue';
                        elseif ($d <= $in_two_days) $dateClass = 'urgent';
                    ?>
                        <tr>
                            <td style="color:var(--text-muted);font-weight:400;"><?= (int)$c['id'] ?></td>
                            <td><?= htmlspecialchars($c['ref']) ?></td>
                            <td style="text-align:center;font-weight:700;"><?= number_format((int)$c['stock_pf'], 0, ',', ' ') ?></td>
                            <td style="text-align:center;font-weight:700;"><?= number_format((int)$c['stock'], 0, ',', ' ') ?></td>
                            <td style="text-align:center;font-weight:800;color:var(--brand-gray-1);"><?= number_format((int)$c['commande'], 0, ',', ' ') ?></td>
                            <td style="text-align:center;">
                                <span class="date-badge <?= $dateClass ?>"><?= htmlspecialchars($d) ?></span>
                            </td>
                            <td>
                                <form method="post" style="display:flex;gap:8px;align-items:center;">
                                    <input type="hidden" name="commande_id" value="<?= (int)$c['id'] ?>">
                                    <input
                                        type="number"
                                        name="livraison"
                                        class="livraison-input"
                                        min="1"
                                        max="<?= (int)$c['stock_pf'] ?>"
                                        placeholder="0"
                                        required
                                        title="Maximum : <?= (int)$c['stock_pf'] ?> (stock PF)"
                                    >
                            </td>
                            <td>
                                    <button class="btn green btn-sm" type="submit">✔ Livrer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p style="font-size:.8rem;color:var(--text-muted);margin-top:8px;">
            🔴 En retard &nbsp;|&nbsp; 🟡 Dans les 2 prochains jours &nbsp;|&nbsp;
            La quantité livrée ne peut pas dépasser le <strong>Stock PF</strong>.
        </p>
    <?php endif; ?>

</div>

</body>
</html>