<?php
session_start();
require "conexion.php";

// Inicializar intentos si no existen
if (!isset($_SESSION['intentos'])) {
    $_SESSION['intentos'] = 3;
}

// Verificar bloqueo si existe
if (isset($_SESSION['bloqueo_hasta'])) {
    $restante = $_SESSION['bloqueo_hasta'] - time();

    if ($restante > 0) {
        $_SESSION['mensaje_login'] = "Bloqueado. Espera $restante segundos.";
        $_SESSION['contador'] = $restante;
        header("Location: indexLogin.php");
        exit();
    } else {
        // Se acabó el bloqueo
        unset($_SESSION['bloqueo_hasta']);
        $_SESSION['intentos'] = 3;
    }
}

if (!empty($_POST['usuario']) && !empty($_POST['password'])) {

    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    // Buscar usuario
    $sql = "SELECT * FROM usuarios WHERE nombreDeUsuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows !== 1) {
        // Usuario NO encontrado → cuenta como intento fallido
        $_SESSION['intentos']--;

        if ($_SESSION['intentos'] <= 0) {
            $_SESSION['bloqueo_hasta'] = time() + 30;
            $_SESSION['mensaje_login'] = "Bloqueado durante 30 segundos.";
            $_SESSION['contador'] = 30;
        } else {
            $_SESSION['mensaje_login'] = "Usuario o contraseña incorrectos. Intentos restantes: " . $_SESSION['intentos'];
        }

        header("Location: indexLogin.php");
        exit();
    }

    // Usuario SÍ existe → validar contraseña
    $userData = $resultado->fetch_assoc();

    if ($password === $userData['password']) {

        // Login correcto → limpiar intentos
        $_SESSION['usuario'] = $userData['nombreDeUsuario'];
        $_SESSION['rol']     = $userData['rolUsuario'];

        $_SESSION['intentos'] = 3;
        unset($_SESSION['bloqueo_hasta']);

        header("Location: indexhome.php");
        exit();

    } else {
        // Contraseña incorrecta
        $_SESSION['intentos']--;

        if ($_SESSION['intentos'] <= 0) {
            $_SESSION['bloqueo_hasta'] = time() + 30;
            $_SESSION['mensaje_login'] = "Bloqueado durante 30 segundos.";
            $_SESSION['contador'] = 30;
        } else {
            $_SESSION['mensaje_login'] = "Usuario o contraseña incorrectos. Intentos restantes: " . $_SESSION['intentos'];
        }

        header("Location: indexLogin.php");
        exit();
    }

} else {
    $_SESSION['mensaje_login'] = "Completa todos los campos.";
    header("Location: indexLogin.php");
    exit();
}
?>
