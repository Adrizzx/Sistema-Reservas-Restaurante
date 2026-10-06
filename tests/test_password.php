<?php
// Verificar contraseña del admin
$stored_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$password_to_test = 'password';

echo "Probando contraseña 'password': ";
if (password_verify($password_to_test, $stored_hash)) {
    echo "✅ CORRECTA!\n";
} else {
    echo "❌ Incorrecta\n";
}

// También probemos con admin
$password_to_test = 'admin';
echo "Probando contraseña 'admin': ";
if (password_verify($password_to_test, $stored_hash)) {
    echo "✅ CORRECTA!\n";
} else {
    echo "❌ Incorrecta\n";
}

// Y probemos con 123456
$password_to_test = '123456';
echo "Probando contraseña '123456': ";
if (password_verify($password_to_test, $stored_hash)) {
    echo "✅ CORRECTA!\n";
} else {
    echo "❌ Incorrecta\n";
}
?>