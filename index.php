<?php session_start() ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Tuita</title>
  </head>
  <body><?php
    require 'comunes/auxiliar.php';

    $con = conectar();

    $nick = "";
    $usuario = devolver_usuario();
    $tuits = devuelve_tuits();

    function devolver_usuario(){
      global $con;
      global $nick;

      if(!isset($_SESSION['usuario'])):
        redireccion_login();
      else:
        $res = pg_query_params($con, "select *
                                        from usuarios
                                       where id::text = $1", 
                                            [$_SESSION['usuario']]);

        if(pg_num_rows($res) != 1)
          redireccion_login();
        else{
          $nick = pg_fetch_assoc($res)['nick'];
          return $_SESSION['usuario'];
        }

      endif;
    }

    function devuelve_tuits(){
      global $con;
      global $usuario;
      
      $res = pg_query_params($con, "select * 
                                      from tuits
                                      where usuarios_id = $1", [$usuario]);


      return pg_fetch_all($res);
    }
  ?>

  <p>Usuario: <?= $nick ?></p>  <?php

  foreach ($tuits as $tuit): ?>

    <article>
      <p>Mensaje:</p>
      <section>
        <?= $tuit['mensaje'] ?>
      </section>
      <section>
        Fecha: <?= $tuit['fecha'] ?>
      </section>
    </article> <?php

  endforeach; ?>
  </body>
</html>