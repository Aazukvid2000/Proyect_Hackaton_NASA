<?php
require_once __DIR__ . '/backend/config.php';

// Generar un nuevo hash
$nueva_password = 'admin123';
$nuevo_hash = password_hash($nueva_password, PASSWORD_BCRYPT);

echo "Nuevo hash generado: " . $nuevo_hash . "<br><br>";

// Actualizar en la base de datos
$stmt = $conn->prepare("UPDATE usuarios SET password_hash = ? WHERE email = 'admin@nasa.com'");
$stmt->bind_param("s", $nuevo_hash);
$stmt->execute();

echo "Hash actualizado en la base de datos<br><br>";

// Verificar
$stmt2 = $conn->prepare("SELECT password_hash FROM usuarios WHERE email = 'admin@nasa.com'");
$stmt2->execute();
$result = $stmt2->get_result();
$user = $result->fetch_assoc();

echo "Hash guardado: " . $user['password_hash'] . "<br>";
echo "Verificación: " . (password_verify($nueva_password, $user['password_hash']) ? "CORRECTO ✓" : "INCORRECTO ✗");
?>