<?php

require_once 'config/database.php';

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = mysqli_prepare(
    $conn,
    "UPDATE users SET password = ? WHERE username = 'admin'"
);

mysqli_stmt_bind_param($stmt, "s", $hash);

if (mysqli_stmt_execute($stmt)) {

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo "<h2>Password admin berhasil diubah!</h2>";
        echo "Username: <b>admin</b><br>";
        echo "Password: <b>admin123</b><br><br>";
        echo "Hash tersimpan:<br>";
        echo "<textarea style='width:600px;height:100px;'>$hash</textarea>";
    } else {
        echo "UPDATE berhasil, tetapi data admin tidak berubah.";
        echo "<br>Cek apakah username admin benar-benar ada.";
    }

} else {

    echo "Gagal UPDATE: " . mysqli_error($conn);
}