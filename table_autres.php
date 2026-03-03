<?php
require __DIR__ . "/table_base.php";
render_table_page(
  $conn,
  "Autres formats",
  "WHERE UPPER(TRIM(format)) NOT LIKE 'D109%' 
     AND UPPER(TRIM(format)) NOT LIKE 'D180%' 
     AND UPPER(TRIM(format)) NOT LIKE 'D305%';"
);