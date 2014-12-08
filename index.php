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
    $errores = [];
    $mensaje_erroneo;
    $mensaje_final = "";
    $usuario = devolver_usuario();

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

        if(pg_num_rows($res) != 1):
          redireccion_login();
        else:
          $nick = pg_fetch_assoc($res)['nick'];
          return $_SESSION['usuario'];
        endif;

      endif;
    }

    function devolver_tuits(){
      global $con;
      global $usuario;
      
      $res = pg_query_params($con, "select mensaje, fecha
                                      from tuits left join relacionados on
                                            (tuits.id = relacionados.tuits_id)
                                     where usuarios_id = $1 
                                            or id_usuarios_mencionados = $1 
                                     group by tuits.id
                                     order by fecha", [$usuario]);

      return pg_fetch_all($res);
    }

    function devolver_nombres_hash($mensaje){
      $expr = "/(#\w{1,24})\b/";
      $division = preg_split($expr, $mensaje, null, PREG_SPLIT_DELIM_CAPTURE);

      return preg_grep($expr, $division);
    }

    function devolver_ids_hashtags($mensaje){
      global $con;

      $hashs = devolver_nombres_hash($mensaje);
      $ids = [];

      foreach ($hashs as $hash){
        $hash = substr($hash, 1);
        $res = pg_query_params($con, "select *
                                        from hashtags
                                        where nombre = $1", [$hash]);
        if(pg_num_rows($res) == 1):
          $fila = pg_fetch_assoc($res);
          $ids[] = $fila['id'];
        endif;
      }

      return $ids;
    }

    function crear_hashtags($mensaje){
      global $con;

      $hashs = devolver_nombres_hash($mensaje);

      foreach ($hashs as $hash){
        $hash = substr($hash, 1);
        $res = pg_query_params($con, "select *
                                        from hashtags
                                       where nombre = $1", [$hash]);
        if(pg_num_rows($res) == 0):
          $res = pg_query_params($con, "insert into hashtags (nombre)
                                              values ($1)", [$hash]);
        endif;
      }
    }

    function enlazar_usuarios($mensaje){
      $nicks = devolver_nicks_usuarios_mencionados($mensaje);

      foreach ($nicks as $nick) {
        $index = strpos($mensaje, $nick, 0);

        if($index !== FALSE):
          $mensaje = substr_replace($mensaje, 
                          "<a href='tuita/index.php?nick=" . 
                                                substr($nick,1) . "'>$nick</a>", 
                                                $index,strlen($nick));
        endif;
      }
      
      return $mensaje;
    }

    function enlazar_hashtags($mensaje){
      $hashs = devolver_nombres_hash($mensaje);

      foreach ($hashs as $hash) {
        $index = strpos($mensaje, $hash, 0);

        if($index !== FALSE):
          $mensaje = substr_replace($mensaje,
                        "<a href='tuita/index.php?hashtag=" . 
                                    substr($hash,1) . "'>$hash</a>", 
                                    $index, strlen($hash));
        endif;
      }

      return $mensaje;
    }

    function devolver_nicks_usuarios_mencionados($mensaje){
      $expr = "/(@\w{1,15})\b/";
      $division = preg_split($expr, $mensaje, null, PREG_SPLIT_DELIM_CAPTURE);

      return preg_grep($expr, $division);
    }

    function devolver_ids_usuarios_mencionados($mensaje){
      global $con;

      $nicks = devolver_nicks_usuarios_mencionados($mensaje);
      $ids = [];

      foreach ($nicks as $nick) {
        $nick = substr($nick, 1);
        $res = pg_query_params($con, "select *
                                        from usuarios
                                       where nick = $1", [$nick]);

        if (pg_num_rows($res) == 1):
          $fila = pg_fetch_assoc($res);
          $ids[] = $fila['id'];
        endif;
      }

      return $ids;
    }

    function relacionar_hashtags($mensaje){
      global $con;

      $hashs = devolver_ids_hashtags($mensaje);
      $tuits_id = pg_fetch_assoc(pg_query($con, "select max(id) as id
                                                   from tuits"))['id'];

      foreach ($hashs as $hash) {
        $res = pg_query_params($con, "insert into hashtags_en_tuits 
                                                  (hashtags_id, tuits_id)
                                      values ($1, $2)", [$hash, $tuits_id]);
      }
    }

    function relacionar_usuarios($mensaje){
      global $con;

      $usuarios_id = devolver_ids_usuarios_mencionados($mensaje);
      $tuits_id = pg_fetch_assoc(pg_query($con, "select max(id) as id
                                                   from tuits"))['id'];

      foreach ($usuarios_id as $id) {
        $res = pg_query_params($con, "insert into relacionados
                                          (id_usuarios_mencionados, tuits_id)
                                        values ($1, $2)", 
                                                    [$id, $tuits_id]); 
      }
    }

    function insertar_tuit(){
      global $con;
      global $usuario;
      global $errores;
      global $mensaje_erroneo;

      $nuevomensaje = trim($_POST['nuevomensaje']);

      if (strlen($nuevomensaje) > 140):
        $mensaje_erroneo = $nuevomensaje;
        $errores[] = "El mensaje debe ser igual o inferior a 140 caracteres";
      else:
        $res = pg_query($con, "begin");
        $res = pg_query($con, "lock table tuits, hashtags, relacionados 
                                                            in share mode");
        $res = pg_query_params($con, "insert into tuits (mensaje, usuarios_id)
                                                              values ($1,$2)",
                                                    [$nuevomensaje, $usuario]);

        if(pg_affected_rows($res) != 1)
          $errores[] = "No se pudo guardar el tuit";

      endif;
    }


    function pintar_tuits(){
      $tuits = devolver_tuits();

      foreach ($tuits as $tuit):
        $mensaje = enlazar_usuarios($tuit['mensaje']); 
        $mensaje = enlazar_hashtags($mensaje); ?>
        <article>
          Mensaje:
          <section>
            <?= $mensaje ?>
          </section>
          <section>
            Fecha: <?= $tuit['fecha'] ?>
          </section>
        </article> <?php
      endforeach; 
    }

    function comprobar_errores(){
      global $errores;

      if(!empty($errores))
        throw new Exception();    
    }

    try{ 
      if(isset($_POST['nuevomensaje'])){
        insertar_tuit();
        comprobar_errores();
        crear_hashtags(trim($_POST['nuevomensaje']));
        relacionar_hashtags(trim($_POST['nuevomensaje']));
        relacionar_usuarios(trim($_POST['nuevomensaje']));
      } ?>

      <p>Usuario: <?= $nick ?></p>  

      <article>
        Nuevo Mensaje:
        <form action="index.php" method="post">
          <textarea name="nuevomensaje" cols="100" rows="10"><?= 
            (isset($mensaje_erroneo)) ? trim($mensaje_erroneo) : ""?></textarea>
          <br>
          <input type="submit" value="Enviar">
        </form>
      </article> <?php

      pintar_tuits();
      comprobar_errores();
    }catch(Exception $e){
      foreach ($errores as $v) { ?>
        <p><?= "Error: " . $v ?></p> <?php
      } 
    }finally{
      pg_query($con, "commit");
      pg_close($con);
    } ?>
  </body>
</html>