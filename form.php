<?php
require __DIR__ . "/cnx.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $sql = "INSERT INTO appro (ref, description, format, client, stock_pf, stock_fb, arrivage, cde_italie)
            VALUES (:ref, :description, :format, :client, :stock_pf, :stock_fb, :arrivage, :cde_italie)
            ON DUPLICATE KEY UPDATE
              description=VALUES(description),
              format=VALUES(format),
              client=VALUES(client),
              stock_pf=VALUES(stock_pf),
              stock_fb=VALUES(stock_fb),
              arrivage=VALUES(arrivage),
              cde_italie=VALUES(cde_italie)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":ref" => trim($_POST["ref"] ?? ""),
        ":description" => trim($_POST["description"] ?? ""),
        ":format" => trim($_POST["format"] ?? ""),
        ":client" => trim($_POST["client"] ?? ""),
        ":stock_pf" => (int)($_POST["stock_pf"] ?? 0),
        ":stock_fb" => (int)($_POST["stock_fb"] ?? 0),
        ":arrivage" => (int)($_POST["arrivage"] ?? 0),
        ":cde_italie" => (int)($_POST["cde_italie"] ?? 0),
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
<title>Formulaire Appro</title>
<link rel="stylesheet" href="style.css">
</head>

<body>
<div class="container">

<div class="topbar">
    <div class="brand">Stock Manager</div>
    <a class="btn primary" href="table_autres.php">Voir le tableau</a>
</div>

<div class="card" style="padding:20px;margin-top:20px;">
<h2>Ajouter / Modifier Article</h2>

<form method="post">

<div class="form-grid">

<div>
<label>REF</label>
<input name="ref" required>
</div>

<div>
<label>Description</label>
<input name="description">
</div>

<div>
<label>Format</label>
<input name="format" id="format">
</div>

<div>
<label>Client</label>
<input name="client">
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
<label>Arrivage</label>
<input type="number" name="arrivage" value="0">
</div>

<div>
<label>Cde Italie</label>
<input type="number" name="cde_italie" id="cde_italie" value="0">
</div>

</div>

<div class="form-actions">
<button class="btn green" type="submit">Enregistrer</button>
</div>

<hr>

<p><b>Stock:</b> <span id="stock_calc">0</span></p>
<p><b>Couverture:</b> <span id="couv_calc">0</span></p>

</form>
</div>

</div>

<script>
function n(v){ v = parseInt(v || "0", 10); return isNaN(v) ? 0 : v; }

function recalc(){
  const pf = n(document.getElementById('stock_pf').value);
  const fb = n(document.getElementById('stock_fb').value);
  const cde = n(document.getElementById('cde_italie').value);

  const stock = pf + fb;
  const couv = stock + cde;

  document.getElementById('stock_calc').textContent = stock;
  document.getElementById('couv_calc').textContent = couv;
}

['stock_pf','stock_fb','cde_italie'].forEach(id=>{
  document.getElementById(id).addEventListener('input', recalc);
});
recalc();
</script>

</body>
</html>