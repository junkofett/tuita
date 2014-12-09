<?php session_start(); ?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf8"/>
    <title>Logout Tuita </title>
  </head>
  <body><?php
    // Destruir todas las variables de sesión.
    $_SESSION = array();

    // Si se desea destruir la sesión completamente, borre también
    // la cookie de sesión. Nota: ¡Esto destruirá la sesión, y no
    // la información de la sesión!
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', 1,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]);
    }

    // Finalmente, destruir la sesión.
    session_destroy();

    header("Location: login.php"); ?>
    <p>Cerrando sesión...</p>
  </body>
</html>
