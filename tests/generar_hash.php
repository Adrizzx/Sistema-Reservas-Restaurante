<?php
// Generar hash para password 'admin'
$password = 'admin';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Generar Hash de Password</h2>";
echo "<p><strong>Password original:</strong> admin</p>";
echo "<p><strong>Hash generado:</strong></p>";
echo "<textarea style='width:100%; height:100px; font-family:monospace;'>$hash</textarea>";
echo "<br><br>";
echo "<p><strong>SQL para actualizar:</strong></p>";
echo "<textarea style='width:100%; height:150px; font-family:monospace;'>";
echo "UPDATE administradores SET password = '$hash' WHERE usuario = 'admin';";
echo "</textarea>";
echo "<br><br>";
echo "<p><a href='test_admin_login.php'>Probar login después de actualizar</a></p>";
echo "<p><a href='index.html'>Volver al index</a></p>";
?>
