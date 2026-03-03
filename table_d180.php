<?php
require __DIR__ . "/table_base.php";
render_table_page($conn, "Formats D180", "WHERE UPPER(TRIM(format)) LIKE 'D180%';");