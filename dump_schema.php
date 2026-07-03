<?php
$pdo = new PDO('mysql:host=localhost;dbname=seafood_batam', 'root', '');
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "TABLES:\n";
print_r($tables);

foreach ($tables as $table) {
    echo "\nSCHEMA FOR $table:\n";
    $stmt = $pdo->query("DESCRIBE `$table`");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
