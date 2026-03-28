<?php
require __DIR__ . "/cnx.php";

$ref        = '';
$result     = null;
$error      = '';
$searched   = false;
$alreadyPending = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref      = trim($_POST['ref'] ?? '');
    $searched = true;

    if ($ref !== '') {
        $stmt = $conn->prepare("
            SELECT h.*
            FROM appro_historique h
            WHERE h.ref = :ref
            ORDER BY h.date_import DESC
            LIMIT 1
        ");
        $stmt->execute([':ref' => $ref]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $error = "Aucun résultat trouvé pour la référence « " . htmlspecialchars($ref) . " ». Vérifiez la saisie.";
        } else {
            // Check if REF is already in pending commandes
            $chk = $conn->prepare("SELECT id FROM commandes WHERE ref = :ref LIMIT 1");
            $chk->execute([':ref' => $ref]);
            $alreadyPending = (bool)$chk->fetch(PDO::FETCH_ASSOC);
        }
    } else {
        $error = "Veuillez saisir une référence.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Livraison — Recherche REF</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .search-card {
            max-width: 520px;
            margin: 32px auto 0;
        }
        .ref-input-wrap {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        .ref-input-wrap > div { flex: 1; }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin: 20px 0;
        }
        .info-box {
            background: var(--gray-50);
            border: 1.5px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 18px 20px;
            text-align: center;
        }
        .info-box .label {
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        .info-box .value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--brand-red-2);
        }
        .alert-error {
            background: #fff5f5;
            border: 1.5px solid #ffa8a8;
            color: #c92a2a;
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            font-size: .9rem;
            font-weight: 600;
            margin-top: 16px;
        }
        .alert-warning {
            background: #fff9db;
            border: 1.5px solid #ffe066;
            color: #856404;
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            font-size: .9rem;
            font-weight: 600;
            margin-top: 16px;
        }
        .ref-badge {
            display: inline-block;
            background: var(--gray-100);
            color: var(--brand-gray-2);
            font-weight: 800;
            padding: 3px 14px;
            border-radius: 20px;
            font-size: .9rem;
            letter-spacing: .5px;
        }
        .result-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
            flex-wrap: wrap;
        }
        .desc-text {
            font-size: .88rem;
            color: var(--text-secondary);
            margin-bottom: 18px;
        }
        .step-indicator {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 28px;
            justify-content: center;
        }
        .step { display: flex; align-items: center; gap: 8px; }
        .step-num {
            width: 28px; height: 28px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem; font-weight: 800;
        }
        .step.active .step-num { background: var(--brand-red-2); color: #fff; }
        .step.done .step-num { background: var(--gray-300); color: var(--gray-600); }
        .step.pending .step-num { background: var(--gray-200); color: var(--gray-500); }
        .step-label {
            font-size: .78rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .step.active .step-label { color: var(--brand-red-2); }
        .step.pending .step-label { color: var(--text-muted); }
        .step-sep { width: 40px; height: 2px; background: var(--gray-200); margin: 0 4px; }
        .step-sep.done-sep { background: var(--brand-red-2); }
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
        <a href="livraison.php">Commandes en attente</a>
        <a href="livraison_search.php" class="active">Livraison</a>
    </nav>

    <h2>🚚 Nouvelle Livraison</h2>

    <!-- Step indicators -->
    <div class="step-indicator">
        <div class="step active">
            <div class="step-num">1</div>
            <div class="step-label">Recherche REF</div>
        </div>
        <div class="step-sep"></div>
        <div class="step pending">
            <div class="step-num">2</div>
            <div class="step-label">Créer commande</div>
        </div>
        <div class="step-sep"></div>
        <div class="step pending">
            <div class="step-num">3</div>
            <div class="step-label">Livrer</div>
        </div>
    </div>

    <div class="search-card">
        <div class="card">
            <h2>Étape 1 — Recherche par REF</h2>

            <form method="post" id="searchForm">
                <div class="ref-input-wrap">
                    <div>
                        <label for="ref">Référence produit</label>
                        <input
                            type="text"
                            name="ref"
                            id="ref"
                            value="<?= htmlspecialchars($ref) ?>"
                            placeholder="Ex: D109-007"
                            required
                            autofocus
                        >
                    </div>
                    <button class="btn green" type="submit">Rechercher</button>
                </div>
            </form>

            <?php if ($searched && $error): ?>
                <div class="alert-error">⚠ <?= $error ?></div>
            <?php endif; ?>

            <?php if ($result): ?>
                <hr>
                <div class="result-header">
                    <span class="ref-badge"><?= htmlspecialchars($result['ref']) ?></span>
                    <?php if (!empty($result['description'])): ?>
                        <span style="color:var(--text-secondary);font-size:.9rem;"><?= htmlspecialchars($result['description']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($result['date_import'])): ?>
                    <p class="desc-text">Données au : <strong><?= htmlspecialchars($result['date_import']) ?></strong></p>
                <?php endif; ?>

                <div class="info-grid">
                    <div class="info-box">
                        <div class="label">Stock PF</div>
                        <div class="value"><?= number_format((int)$result['stock_pf'], 0, ',', ' ') ?></div>
                    </div>
                    <div class="info-box">
                        <div class="label">Stock Total</div>
                        <div class="value"><?= number_format((int)$result['stock'], 0, ',', ' ') ?></div>
                    </div>
                </div>

                <?php if ($alreadyPending): ?>
                    <div class="alert-warning">
                        ⚠ Cette référence est déjà dans les <strong>commandes en attente de livraison</strong>.
                        Vous ne pouvez pas créer une deuxième commande pour la même référence.
                    </div>
                    <div class="form-actions" style="justify-content:center;margin-top:14px;">
                        <a class="btn primary" href="livraison.php">→ Voir les commandes en attente</a>
                    </div>
                <?php else: ?>
                    <div class="form-actions" style="justify-content:center;">
                        <a class="btn green" href="livraison_commande.php?ref=<?= urlencode($result['ref']) ?>">
                            ➜ Créer une commande
                        </a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>

</div>

</body>
</html>
