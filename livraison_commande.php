<?php
require __DIR__ . "/cnx.php";

/* ── 1. Resolve REF (GET or POST) ─────────────────────────── */
$ref = trim($_GET['ref'] ?? $_POST['ref'] ?? '');

if ($ref === '') {
    header("Location: livraison_search.php");
    exit;
}

/* ── 2. Fetch latest stock for this REF ───────────────────── */
$stmt = $conn->prepare("
    SELECT *
    FROM appro_historique
    WHERE ref = :ref
    ORDER BY date_import DESC
    LIMIT 1
");
$stmt->execute([':ref' => $ref]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: livraison_search.php?err=notfound");
    exit;
}

/* ── 2b. Check if this REF already has a pending commande ─── */
$chk = $conn->prepare("SELECT id FROM commandes WHERE ref = :ref LIMIT 1");
$chk->execute([':ref' => $ref]);
$existingCommande = $chk->fetch(PDO::FETCH_ASSOC);

$error   = '';
$success = false;

/* ── 3. Handle form submission ────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commande       = (int)($_POST['commande'] ?? 0);
    $date_livraison = trim($_POST['date_livraison'] ?? '');

    if ($existingCommande) {
        $error = "⚠ Cette référence est déjà dans les commandes en attente de livraison.";
    } elseif ($commande <= 0) {
        $error = "La quantité commandée doit être supérieure à 0.";
    } elseif ($date_livraison === '') {
        $error = "Veuillez saisir une date de livraison.";
    } else {
        // Get max sort_order to place new commande at end
        $maxOrd = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0) FROM commandes")->fetchColumn();

        $insert = $conn->prepare("
            INSERT INTO commandes (ref, stock_pf, stock, commande, date_livraison, sort_order)
            VALUES (:ref, :stock_pf, :stock, :commande, :date_livraison, :sort_order)
        ");
        $insert->execute([
            ':ref'            => $ref,
            ':stock_pf'       => (int)$product['stock_pf'],
            ':stock'          => (int)$product['stock'],
            ':commande'       => $commande,
            ':date_livraison' => $date_livraison,
            ':sort_order'     => $maxOrd + 1,
        ]);

        header("Location: livraison.php?success=1");
        exit;
    }
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Livraison — Commande</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .commande-card {
            max-width: 560px;
            margin: 32px auto 0;
        }
        .readonly-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 14px;
            margin-bottom: 22px;
        }
        .readonly-box {
            background: var(--gray-50);
            border: 1.5px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 14px 16px;
            text-align: center;
        }
        .readonly-box .ro-label {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .readonly-box .ro-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--brand-red-2);
        }
        .ro-ref .ro-value {
            font-size: 1.1rem;
            letter-spacing: .5px;
        }
        .input-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 22px;
        }
        .alert-error {
            background: #fff5f5;
            border: 1.5px solid #ffa8a8;
            color: #c92a2a;
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            font-size: .9rem;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .alert-warning {
            background: #fff9db;
            border: 1.5px solid #ffe066;
            color: #856404;
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            font-size: .9rem;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .section-sep {
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--text-muted);
            margin-bottom: 12px;
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
        .step.active .step-num  { background: var(--brand-red-2); color: #fff; }
        .step.done .step-num    { background: var(--gray-400); color: #fff; }
        .step.pending .step-num { background: var(--gray-200); color: var(--gray-500); }
        .step-label { font-size: .78rem; font-weight: 600; white-space: nowrap; }
        .step.active .step-label  { color: var(--brand-red-2); }
        .step.done .step-label    { color: var(--gray-600); }
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
        <div class="step done">
            <div class="step-num">✓</div>
            <div class="step-label">Recherche REF</div>
        </div>
        <div class="step-sep done-sep"></div>
        <div class="step active">
            <div class="step-num">2</div>
            <div class="step-label">Créer commande</div>
        </div>
        <div class="step-sep"></div>
        <div class="step pending">
            <div class="step-num">3</div>
            <div class="step-label">Livrer</div>
        </div>
    </div>

    <div class="commande-card">
        <div class="card">
            <h2>Étape 2 — Créer une commande</h2>

            <?php if ($existingCommande): ?>
                <div class="alert-warning">
                    ⚠ Cette référence est déjà dans les <strong>commandes en attente de livraison</strong>.
                    Vous ne pouvez pas créer une deuxième commande pour la même référence.
                    <br><br>
                    <a class="btn primary" href="livraison.php">→ Voir les commandes en attente</a>
                </div>
            <?php else: ?>

            <?php if ($error): ?>
                <div class="alert-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Read-only info -->
            <p class="section-sep">Informations du produit</p>
            <div class="readonly-grid">
                <div class="readonly-box ro-ref" style="grid-column: span 1;">
                    <div class="ro-label">REF</div>
                    <div class="ro-value"><?= htmlspecialchars($product['ref']) ?></div>
                </div>
                <div class="readonly-box">
                    <div class="ro-label">Stock PF</div>
                    <div class="ro-value"><?= number_format((int)$product['stock_pf'], 0, ',', ' ') ?></div>
                </div>
                <div class="readonly-box">
                    <div class="ro-label">Stock Total</div>
                    <div class="ro-value"><?= number_format((int)$product['stock'], 0, ',', ' ') ?></div>
                </div>
            </div>

            <!-- Editable inputs -->
            <p class="section-sep">Détails de la commande</p>
            <form method="post" id="commandeForm">
                <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>">

                <div class="input-grid">
                    <div>
                        <label for="commande">Quantité commandée</label>
                        <input
                            type="number"
                            name="commande"
                            id="commande"
                            min="1"
                            placeholder="Ex: 500"
                            value="<?= (int)($_POST['commande'] ?? 0) ?: '' ?>"
                            required
                            autofocus
                        >
                    </div>
                    <div>
                        <label for="date_livraison">Date de livraison prévue</label>
                        <input
                            type="date"
                            name="date_livraison"
                            id="date_livraison"
                            min="<?= $today ?>"
                            value="<?= htmlspecialchars($_POST['date_livraison'] ?? '') ?>"
                            required
                        >
                    </div>
                </div>

                <hr>

                <div class="form-actions">
                    <a class="btn primary" href="livraison_search.php">← Retour</a>
                    <button class="btn green" type="submit">✔ Enregistrer la commande</button>
                </div>
            </form>

            <?php endif; ?>

        </div>
    </div>

</div>

</body>
</html>
