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

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":ref" => trim($_POST["ref"] ?? ""),
        ":description" => trim($_POST["description"] ?? ""),
        ":format" => trim($_POST["format"] ?? ""),
        ":client" => trim($_POST["client"] ?? ""),
        ":stock_pf" => (int)($_POST["stock_pf"] ?? 0),
        ":stock_fb" => (int)($_POST["stock_fb"] ?? 0),
        ":stock" => (int)($_POST["stock"] ?? 0),
        ":arrivage" => (int)($_POST["arrivage"] ?? 0),
        ":cde_italie" => (int)($_POST["cde_italie"] ?? 0),
        ":couverture" => (int)($_POST["couverture"] ?? 0),
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

        <div class="topbar">
            <div class="brand">
                <img src="assets/logo.png" alt="Metalscatola Afrique" class="logo-img">
            </div>
            <div class="actions">
                <a class="btn primary" href="table_autres.php">Voir le tableau</a>
                <a class="btn primary" href="livraison.php">livraison</a>
                <a class="btn primary" href="import_excel.php">Importer Excel</a>
                <a class="btn primary" href="table_last.php">Dernier Excel</a>
                <a class="btn primary" href="historique.php">Historique</a>
                <button class="btn green" type="submit" form="approForm">Enregistrer</button>
            </div>

        </div>

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