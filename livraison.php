<?php
require __DIR__ . "/cnx.php";

$error   = '';
$success = '';

/* ═══════════════════════════════════════════════════════════
   POST — handle livraison OR reorder
═══════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'livrer';

    /* ─── Reorder: move a commande up or down ─────────────── */
    if ($action === 'move_up' || $action === 'move_down') {
        $id = (int)($_POST['commande_id'] ?? 0);

        // Fetch current row
        $cur = $conn->prepare("SELECT id, sort_order FROM commandes WHERE id = :id");
        $cur->execute([':id' => $id]);
        $curRow = $cur->fetch(PDO::FETCH_ASSOC);

        if ($curRow) {
            if ($action === 'move_up') {
                // Find the row immediately above (smaller sort_order)
                $adj = $conn->prepare("
                    SELECT id, sort_order FROM commandes
                    WHERE sort_order < :so
                    ORDER BY sort_order DESC LIMIT 1
                ");
            } else {
                // Find the row immediately below (larger sort_order)
                $adj = $conn->prepare("
                    SELECT id, sort_order FROM commandes
                    WHERE sort_order > :so
                    ORDER BY sort_order ASC LIMIT 1
                ");
            }
            $adj->execute([':so' => $curRow['sort_order']]);
            $adjRow = $adj->fetch(PDO::FETCH_ASSOC);

            if ($adjRow) {
                // Swap sort_order values
                $swap1 = $conn->prepare("UPDATE commandes SET sort_order = :so WHERE id = :id");
                $swap1->execute([':so' => $adjRow['sort_order'], ':id' => $curRow['id']]);
                $swap1->execute([':so' => $curRow['sort_order'], ':id' => $adjRow['id']]);
            }
        }

        header("Location: livraison.php");
        exit;
    }

    /* ─── Livraison ───────────────────────────────────────── */
    $commande_id = (int)($_POST['commande_id'] ?? 0);
    $livraison   = (int)($_POST['livraison']   ?? 0);

    // 1. Fetch the commande row
    $stmt = $conn->prepare("SELECT * FROM commandes WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $commande_id]);
    $cmd = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cmd) {
        $error = "Commande introuvable (id={$commande_id}).";
    } elseif ($livraison <= 0) {
        $error = "La quantité livrée doit être supérieure à 0.";
    } else {
        // 2. Fetch FRESH stock from appro_historique
        $s = $conn->prepare("
            SELECT stock_pf, stock_fb, stock, arrivage, cde_italie
            FROM appro_historique
            WHERE ref = :ref
            ORDER BY date_import DESC
            LIMIT 1
        ");
        $s->execute([':ref' => $cmd['ref']]);
        $freshRow = $s->fetch(PDO::FETCH_ASSOC);

        if (!$freshRow) {
            $error = "Impossible de trouver le stock actuel pour la ref <strong>"
                   . htmlspecialchars($cmd['ref']) . "</strong>.";
        } elseif ((int)$freshRow['stock_pf'] <= 0) {
            $error = "⛔ Livraison impossible : le stock PF est actuellement à 0 pour la ref <strong>"
                   . htmlspecialchars($cmd['ref']) . "</strong>.";
        } elseif ($livraison > (int)$freshRow['stock_pf']) {
            $error = "⛔ Livraison impossible : la quantité saisie ({$livraison}) dépasse "
                   . "le stock PF actuel ({$freshRow['stock_pf']}) pour la ref <strong>"
                   . htmlspecialchars($cmd['ref']) . "</strong>.";
        } else {
            // Delivered = what was entered (already validated <= stock_pf)
            $remaining = (int)$cmd['commande'];
            $delivered = $livraison;

            // 3. Calculate new stock values
            $new_stock_pf  = max(0, (int)$freshRow['stock_pf'] - $delivered);
            $new_stock     = max(0, (int)$freshRow['stock']    - $delivered);
            $new_couverture = $new_stock_pf
                            + (int)$freshRow['stock_fb']
                            + (int)$freshRow['arrivage']
                            + (int)$freshRow['cde_italie'];

            // 4. Update appro_historique (latest row for this ref)
            $updHist = $conn->prepare("
                UPDATE appro_historique
                SET stock_pf   = :stock_pf,
                    stock      = :stock,
                    couverture = :couv
                WHERE ref = :ref
                  AND date_import = (
                      SELECT MAX(date_import)
                      FROM   appro_historique
                      WHERE  ref = :ref2
                  )
            ");
            $updHist->execute([
                ':stock_pf' => $new_stock_pf,
                ':stock'    => $new_stock,
                ':couv'     => $new_couverture,
                ':ref'      => $cmd['ref'],
                ':ref2'     => $cmd['ref'],
            ]);

            // 5. Update the main appro table
            $updAppro = $conn->prepare("
                UPDATE appro
                SET stock_pf   = :stock_pf,
                    stock      = :stock,
                    couverture = :couv
                WHERE ref = :ref
            ");
            $updAppro->execute([
                ':stock_pf' => $new_stock_pf,
                ':stock'    => $new_stock,
                ':couv'     => $new_couverture,
                ':ref'      => $cmd['ref'],
            ]);

            // 6. Insert into livraison_historique
            $ins = $conn->prepare("
                INSERT INTO livraison_historique (ref, livraison, date_livraison)
                VALUES (:ref, :livraison, :date_liv)
            ");
            $ins->execute([
                ':ref'       => $cmd['ref'],
                ':livraison' => $delivered,
                ':date_liv'  => date('Y-m-d'),
            ]);

            // 7. Partial or full delivery?
            $newRemaining = $remaining - $delivered;
            if ($newRemaining <= 0) {
                // Fully delivered → delete the commande
                $del = $conn->prepare("DELETE FROM commandes WHERE id = :id");
                $del->execute([':id' => $commande_id]);
            } else {
                // Partial → update commande qty and also update stock snapshot
                $upd = $conn->prepare("
                    UPDATE commandes
                    SET commande  = :commande,
                        stock_pf  = :stock_pf,
                        stock     = :stock
                    WHERE id = :id
                ");
                $upd->execute([
                    ':commande' => $newRemaining,
                    ':stock_pf' => $new_stock_pf,
                    ':stock'    => $new_stock,
                    ':id'       => $commande_id,
                ]);
            }

            header("Location: livraison.php?success=1");
            exit;
        }
    }
}

/* ═══════════════════════════════════════════════════════════
   GET — list all pending commandes
═══════════════════════════════════════════════════════════ */
$commandes = $conn->query("
    SELECT * FROM commandes ORDER BY sort_order ASC, created_at ASC
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
    <title>Commandes en attente de livraison — Metalscatola</title>
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
        .livraison-form {
            display: flex;
            gap: 8px;
            align-items: center;
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
        .date-badge.urgent  { background: #fff3cd; color: #856404; }
        .date-badge.overdue { background: #fff5f5; color: #c92a2a; }
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
        /* Reorder arrow buttons */
        .reorder-btns {
            display: flex;
            flex-direction: column;
            gap: 3px;
            align-items: center;
        }
        .btn-arrow {
            background: var(--gray-100);
            border: 1px solid var(--gray-300);
            border-radius: 4px;
            width: 26px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: .8rem;
            line-height: 1;
            padding: 0;
            transition: background 0.15s;
        }
        .btn-arrow:hover {
            background: var(--gray-200);
            border-color: var(--gray-400);
        }
        .btn-arrow:disabled {
            opacity: 0.3;
            cursor: default;
        }
    </style>
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
        <a href="historique.php" class="btn primary">Historique</a>
        <a href="historique_livraison.php" class="btn primary">Hist. livraison</a>
        <a href="livraison.php" class="btn primary active">📦 Commandes</a>
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
                        <th style="width:36px;"></th><!-- reorder col -->
                        <th>#</th>
                        <th>REF</th>
                        <th>Stock PF</th>
                        <th>Stock Total</th>
                        <th>Commandé</th>
                        <th>Date livraison</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $today       = date('Y-m-d');
                    $in_two_days = date('Y-m-d', strtotime('+2 days'));
                    $total       = count($commandes);
                    foreach ($commandes as $i => $c):
                        $d         = $c['date_livraison'];
                        $dateClass = '';
                        if ($d < $today)            $dateClass = 'overdue';
                        elseif ($d <= $in_two_days) $dateClass = 'urgent';
                        $isFirst = ($i === 0);
                        $isLast  = ($i === $total - 1);
                    ?>
                        <tr>
                            <!-- Reorder arrows -->
                            <td style="padding:4px 6px;">
                                <div class="reorder-btns">
                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="action" value="move_up">
                                        <input type="hidden" name="commande_id" value="<?= (int)$c['id'] ?>">
                                        <button class="btn-arrow" type="submit" title="Monter" <?= $isFirst ? 'disabled' : '' ?>>▲</button>
                                    </form>
                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="action" value="move_down">
                                        <input type="hidden" name="commande_id" value="<?= (int)$c['id'] ?>">
                                        <button class="btn-arrow" type="submit" title="Descendre" <?= $isLast ? 'disabled' : '' ?>>▼</button>
                                    </form>
                                </div>
                            </td>
                            <td style="color:var(--text-muted);font-weight:400;"><?= (int)$c['id'] ?></td>
                            <td><?= htmlspecialchars($c['ref']) ?></td>
                            <td style="text-align:center;font-weight:700;"><?= number_format((int)$c['stock_pf'], 0, ',', ' ') ?></td>
                            <td style="text-align:center;font-weight:700;"><?= number_format((int)$c['stock'], 0, ',', ' ') ?></td>
                            <td style="text-align:center;font-weight:800;color:var(--brand-gray-1);"><?= number_format((int)$c['commande'], 0, ',', ' ') ?></td>
                            <td style="text-align:center;">
                                <span class="date-badge <?= $dateClass ?>"><?= htmlspecialchars($d) ?></span>
                            </td>
                            <td>
                                <form method="post" class="livraison-form">
                                    <input type="hidden" name="action" value="livrer">
                                    <input type="hidden" name="commande_id" value="<?= (int)$c['id'] ?>">
                                    <input
                                        type="number"
                                        name="livraison"
                                        class="livraison-input"
                                        min="1"
                                        max="<?= (int)$c['stock_pf'] ?>"
                                        placeholder="0"
                                        required
                                        title="Maximum : <?= (int)$c['stock_pf'] ?> (stock PF actuel)"
                                    >
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
            Une livraison partielle réduit la quantité commandée et reste dans la liste jusqu'à 0.
        </p>
    <?php endif; ?>

</div>

</body>
</html>