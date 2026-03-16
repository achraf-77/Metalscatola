<?php

ini_set('display_errors',0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    echo json_encode(["success"=>false,"message"=>"Méthode non autorisée"]);
    exit;
}

require __DIR__ . "/cnx.php";

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput,true);

if(!$data || !isset($data["rows"])){
    echo json_encode(["success"=>false,"message"=>"Données invalides"]);
    exit;
}

$rows = $data["rows"];

$sql = "
INSERT INTO appro
(ref, description, format, client, stock_pf, stock_fb, stock, arrivage, cde_italie, couverture)

VALUES
(:ref, :description, :format, :client, :stock_pf, :stock_fb, :stock, :arrivage, :cde_italie, :couverture)

ON DUPLICATE KEY UPDATE
description=VALUES(description),
format=VALUES(format),
client=VALUES(client),
stock_pf=VALUES(stock_pf),
stock_fb=VALUES(stock_fb),
stock=VALUES(stock),
arrivage=VALUES(arrivage),
cde_italie=VALUES(cde_italie),
couverture=VALUES(couverture)
";

$stmt = $conn->prepare($sql);

$imported = 0;
$errors = 0;

foreach($rows as $row){

    $ref = trim($row["ref"] ?? "");

    if($ref == ""){
        $errors++;
        continue;
    }

    $stock_pf = (int)($row["stock_pf"] ?? 0);
    $stock_fb = (int)($row["stock_fb"] ?? 0);
    $arrivage = (int)($row["arrivage"] ?? 0);
    $cde_italie = (int)($row["cde_italie"] ?? 0);

    // الحساب هنا
    $stock = $stock_pf + $stock_fb;
    $couverture = $stock + $arrivage + $cde_italie;

    try{

        $stmt->execute([
            ":ref"=>$ref,
            ":description"=>trim($row["description"] ?? ""),
            ":format"=>trim($row["format"] ?? ""),
            ":client"=>trim($row["client"] ?? ""),
            ":stock_pf"=>$stock_pf,
            ":stock_fb"=>$stock_fb,
            ":stock"=>$stock,
            ":arrivage"=>$arrivage,
            ":cde_italie"=>$cde_italie,
            ":couverture"=>$couverture
        ]);

        $imported++;

    }catch(PDOException $e){
        $errors++;
    }
}

echo json_encode([
    "success"=>true,
    "imported"=>$imported,
    "errors"=>$errors
]);