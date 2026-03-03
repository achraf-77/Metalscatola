<?php
require __DIR__ . "/table_base.php";
render_table_page($conn, "Formats D305", "WHERE UPPER(TRIM(format)) LIKE 'D305%';");
