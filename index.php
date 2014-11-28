<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Tuita</title>
  </head>
  <body><?php
    require 'comunes/auxiliar.php';

    $con = conectar();

    function mostrar_tuits(){
      global $con;

      $res = pg_query_params($con, "select *
                                      from usuarios
                                    where id = $1", [$_SESSION['usuario']]);
      $fila = pg_fetch_assoc($res);

      echo $fila['nick'];
    }

    mostrar_tuits();
  ?>
  </body>
</html>