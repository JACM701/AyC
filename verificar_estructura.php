<?php
$conn = new mysqli('localhost', 'root', '', 'inventory_management_system2');
$result = $conn->query('DESCRIBE cotizaciones');
echo "ESTRUCTURA DE LA TABLA cotizaciones:\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Default'] . "\n";
}
?>
