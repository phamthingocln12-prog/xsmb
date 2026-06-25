<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=XSMB_P', 'root', '');
$r = $pdo->query('SELECT draw_date, gdb_full, gdb_first2, gdb_last2, g1_full, g1_first2, g1_last2 FROM analysis_extractions ORDER BY draw_date DESC LIMIT 3');
foreach ($r as $row) {
    echo $row['draw_date'] . " GDB:" . $row['gdb_full'] . " => first2=" . $row['gdb_first2'] . ", last2=" . $row['gdb_last2']
        . " | G1:" . $row['g1_full'] . " => first2=" . $row['g1_first2'] . ", last2=" . $row['g1_last2'] . PHP_EOL;
}
