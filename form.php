<?php
require __DIR__ . "/cnx.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $sql = "INSERT INTO appro (ref, description, format, client, stock_pf, stock_fb, stock, arrivage, cde_italie, couverture)
        VALUES (:ref, :description, :format, :client, :stock_pf, :stock_fb, :stock, :arrivage, :cde_italie, :couverture)
        ON DUPLICATE KEY UPDATE
          description=VALUES(description),
          format=VALUES(format),
          client=VALUES(client),
          stock_pf=VALUES(stock_pf),
          stock_fb=VALUES(stock_fb),
          stock=VALUES(stock),
          arrivage=VALUES(arrivage),
          cde_italie=VALUES(cde_italie),
          couverture=VALUES(couverture)";

    $ref_val         = trim($_POST["ref"] ?? "");
    $description_val = trim($_POST["description"] ?? "");
    $format_val      = trim($_POST["format"] ?? "");
    $client_val      = trim($_POST["client"] ?? "");
    $stock_pf_val    = (int)($_POST["stock_pf"] ?? 0);
    $stock_fb_val    = (int)($_POST["stock_fb"] ?? 0);
    $stock_val       = (int)($_POST["stock"] ?? 0);
    $arrivage_val    = (int)($_POST["arrivage"] ?? 0);
    $cde_italie_val  = (int)($_POST["cde_italie"] ?? 0);
    $couverture_val  = (int)($_POST["couverture"] ?? 0);

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":ref"         => $ref_val,
        ":description" => $description_val,
        ":format"      => $format_val,
        ":client"      => $client_val,
        ":stock_pf"    => $stock_pf_val,
        ":stock_fb"    => $stock_fb_val,
        ":stock"       => $stock_val,
        ":arrivage"    => $arrivage_val,
        ":cde_italie"  => $cde_italie_val,
        ":couverture"  => $couverture_val,
    ]);

    // Also insert into appro_historique so the article appears in Historique
    $hist = $conn->prepare("
        INSERT INTO appro_historique
            (ref, description, format, client, stock_pf, stock_fb, stock, arrivage, cde_italie, couverture, date_import)
        VALUES
            (:ref, :description, :format, :client, :stock_pf, :stock_fb, :stock, :arrivage, :cde_italie, :couverture, CURDATE())
    ");
    $hist->execute([
        ":ref"         => $ref_val,
        ":description" => $description_val,
        ":format"      => $format_val,
        ":client"      => $client_val,
        ":stock_pf"    => $stock_pf_val,
        ":stock_fb"    => $stock_fb_val,
        ":stock"       => $stock_val,
        ":arrivage"    => $arrivage_val,
        ":cde_italie"  => $cde_italie_val,
        ":couverture"  => $couverture_val,
    ]);

    //  par Redirection format
    $format = strtoupper(trim($_POST["format"] ?? ""));

    if (strpos($format, "D109") === 0) {
        header("Location: table_d109.php");
    } elseif (strpos($format, "D180") === 0) {
        header("Location: table_d180.php");
    } elseif (strpos($format, "D305") === 0) {
        header("Location: table_d305.php");
    } else {
        header("Location: table_autres.php");
    }
    exit;
}
?>

<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Ajouter / Modifier </title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container wide">

        <nav>
            <a href="form.php" class="active">+ Ajouter</a>
            <a href="table_d109.php">D109</a>
            <a href="table_d180.php">D180</a>
            <a href="table_d305.php">D305</a>
            <a href="table_autres.php">Autres</a>
            <a href="import_excel.php" class="btn primary">📥 Importer Excel</a>
            <a href="table_last.php" class="btn primary">Dernier Excel</a>
            <a href="livraison_search.php" class="btn primary">Livraison</a>
            <a href="historique.php" class="btn primary">Historique</a>
        </nav>

        <div class="card">
            <h2>Ajouter / Modifier un produit</h2>

            <form method="post" id="approForm">
                <div class="form-grid">

                    <div>
                        <label>Référence (REF)</label>
                        <input name="ref" required placeholder="Ex: D109-007">
                    </div>

                    <div>
                        <label>Client</label>
                        <input name="client" placeholder="Nom du client">
                    </div>

                    <div>
                        <label>Description</label>
                        <input name="description" placeholder="Description du produit">
                    </div>

                    <div>
                        <label>Format</label>
                        <input name="format" id="format" placeholder="Ex: D305x335 VI/IMP">
                    </div>

                    <div>
                        <label>Stock PF</label>
                        <input type="number" name="stock_pf" id="stock_pf" value="0">
                    </div>

                    <div>
                        <label>Stock FB</label>
                        <input type="number" name="stock_fb" id="stock_fb" value="0">
                    </div>

                    <div>
                        <label>Stock</label>
                        <input type="number" name="stock" value="0">
                    </div>

                    <div>
                        <label>Arrivage</label>
                        <input type="number" name="arrivage" id="arrivage" value="0">
                    </div>

                    <div>
                        <label>Cde Italie</label>
                        <input type="number" name="cde_italie" id="cde_italie" value="0">
                    </div>

                    <div>
                        <label>Couverture</label>
                        <input type="number" name="couverture" value="0">
                    </div>


                </div>

                <hr>

                <div class="form-actions">
                    <a class="btn primary" href="table_autres.php">Retour</a>
                    <button class="btn green" type="submit">Enregistrer</button>
                </div>
            </form>

        </div>
    </div>

    <script>
        function n(v) {
            v = parseInt(v || "0", 10);
            return isNaN(v) ? 0 : v;
        }

    
    </script>

</body>

</html>