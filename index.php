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

    $pagina_actual = 0;
    $paginas_totales = 0;
    $filas_por_pag = 5;
    $primer_tuit = 0;
    $total_tuits = 0;

    //Da valor a $nick y devuelve la id del usuario en base a $_SESSION
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

    //Devuelve un array con todos los tuits generados por el usuario y los tuits
    //                                                  donde ha sido mencionado
    function devolver_tuits($id_usuario){
      global $con;
      global $filas_por_pag;     
      global $pagina_actual;
      global $paginas_totales;
      global $primer_tuit;
      global $total_tuits;

      if(empty($id_usuario))
        return array();

      paginar_usuario($id_usuario);

      $res = pg_query_params($con, "select mensaje, fecha
                                      from tuits left join relacionados on
                                            (tuits.id = relacionados.tuits_id)
                                     where usuarios_id = $1 
                                            or id_usuarios_mencionados = $1 
                                     group by tuits.id
                                     order by fecha ".$_SESSION['orden'] ."
                                     limit " . $filas_por_pag . "
                                    offset " . $primer_tuit,
                                                        [$id_usuario]);

      return pg_fetch_all($res);
    }

    //-----------------------------------------------------------BLOQUE HASHTAGS
    //Devuelve un array con los nombres de #hashtags que son mencionados en el
    //                                                                  $mensaje
    function devolver_nombres_hash($mensaje){
      $expr = "/(#\w{1,24})\b/";
      $division = preg_split($expr, $mensaje, null, PREG_SPLIT_DELIM_CAPTURE);

      return preg_grep($expr, $division);
    }

    //Devuelve un array con las ids de los #hashtags que han sido mencionados en
    //                                                              el $mensaje
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

    //Crea los #hashtags que han sido mencionados en el $mensaje y que no están
    //                                               creados en la base de datos
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

    //Crea enlaces en los #hashtags del mensaje
    function enlazar_hashtags($mensaje){
      $hashs = devolver_nombres_hash($mensaje);

      foreach ($hashs as $hash) {
        $index = strpos($mensaje, $hash, 0);

        if($index !== FALSE):
          $mensaje = substr_replace($mensaje,
                        "<a href='index.php?hashtag=" . 
                                    substr($hash,1) . "'>$hash</a>", 
                                    $index, strlen($hash));
        endif;
      }

      return $mensaje;
    }

    //Pinta todos los tuits relacionados con el $hashtag
    function pintar_tuits_hashtag($hashtag){
      global $con;
      global $errores;
      global $filas_por_pag;
      global $primer_tuit;

      $hashtag = devolver_ids_hashtags('#'.$hashtag);

      if(empty($hashtag) && isset($_GET['hashtag'])):
        $errores[] = "el hashtag #".$_GET['hashtag']." no existe";
      else:
        $hashtag = $hashtag[0];

        paginar_hashtag($hashtag);

        $res = pg_query_params($con, "select *
                                        from tuits left join hashtags_en_tuits on 
                                        (tuits.id = hashtags_en_tuits.tuits_id)
                                       where hashtags_en_tuits.hashtags_id = $1
                                       order by fecha ".$_SESSION['orden']."
                                     limit " . $filas_por_pag . "
                                    offset " . $primer_tuit,
                                              [$hashtag]);

        if(pg_num_rows($res) != 0):
          $tuits = pg_fetch_all($res);

          pintar_boton_orden();

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
        endif;

        menu_paginacion();
      endif;
    }
    //-------------------------------------------------------FIN BLOQUE HASHTAGS

    //-----------------------------------------------------------BLOQUE USUARIOS
    //Devuelve un array con los nicks de los @usuarios mencionados en $mensaje
    function devolver_nicks_usuarios_mencionados($mensaje){
      $expr = "/(@\w{1,15})\b/";
      $division = preg_split($expr, $mensaje, null, PREG_SPLIT_DELIM_CAPTURE);

      return preg_grep($expr, $division);
    }

    //Devuelve un array con los ids de los @usuarios mencionados en $mensaje
    function devolver_ids_usuarios_mencionados($mensaje){
      global $con;
      global $errores;

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

      if(empty($ids) && isset($_GET['nick']))
        $errores[] = "el usuario @".trim($_GET['nick'])." no existe";

      return $ids;
    }

    //Crea enlaces con los usuarios mencionados en el $mensaje aunque no existan
    function enlazar_usuarios($mensaje){
      $nicks = devolver_nicks_usuarios_mencionados($mensaje);

      foreach ($nicks as $nick) {
        $index = strpos($mensaje, $nick, 0);

        if($index !== FALSE):
          $mensaje = substr_replace($mensaje, 
                          "<a href='index.php?nick=" . 
                                                substr($nick,1) . "'>$nick</a>", 
                                                $index,strlen($nick));
        endif;
      }
      
      return $mensaje;
    }
    //-------------------------------------------------------FIN BLOQUE USUARIOS

    //Con el nuevo tuit insertado, se inserta en la tabla hashtags_en_tuits la
    //                                        relacion del tuit con el hashtag
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

    //Con el nuevo tuit insertado, se inserta en la tabla relacionados la 
    //                                      relación de los usuarios con el tuit
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

      if(empty($nuevomensaje)):
        $errores[] = "El mensaje no puede estar vacío";
      else:
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

          if(pg_affected_rows($res) != 1):
            $errores[] = "No se pudo guardar el tuit";
            return FALSE;
          else:
            return TRUE;
          endif;

        endif;
      endif;
    }

    function pintar_tuits_usuario($usuario){
      $tuits = devolver_tuits($usuario);

      if(empty($tuits) || empty($usuario))
        return;

      pintar_boton_orden();

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

      menu_paginacion();
    }

    function consulta_boton_volver(){
      global $nick;

      if((isset($_GET['nick']) && trim($_GET['nick'] != $nick))){
        return TRUE;
      }

      if(!isset($_POST['nuevomensaje']) && !isset($_GET['hashtag'])){
          return FALSE;
      }

      if(isset($_POST['nuevomensaje']) 
        && strlen(trim($_POST['nuevomensaje'])) < 140 
        && strlen(trim($_POST['nuevomensaje'])) > 0 )
        return FALSE;

      return TRUE;
    }

    function pintar_boton_orden(){ ?>
        <form action="index.php" method="GET"> <?php
          if(isset($_GET['nick'])){ ?>
            <input type="hidden" name="nick" value=<?= trim($_GET['nick']) ?>> <?php
          } 
          if(isset($_GET['hashtag'])){ ?>
            <input type="hidden" name="hashtag" value=<?= trim($_GET['hashtag'])?> > <?php
          }
          if($_SESSION['orden'] == "asc"){ ?>
            <input type="hidden" name="orden" value="desc"> 
            <input type="submit" value="Descendente"><?php 
          }else{?>
            <input type="hidden" name="orden" value="asc"> 
            <input type="submit" value="Ascendente"> <?php
          } ?>
        </form> <?php
    }

    function pintar_formulario_tuits(){
      global $nick;
      global $mensaje_erroneo; ?>
      <p>Usuario: <?= $nick ?></p>  
      <a href="login/logout.php"><button>Logout</button></a>
      <article>
        Nuevo Mensaje:
        <form action="index.php" method="post">
          <textarea name="nuevomensaje" cols="100" rows="10"><?= 
            (isset($mensaje_erroneo)) ? trim($mensaje_erroneo) : ""?></textarea>
          <br>
          <input type="submit" value="Enviar">
        </form> <?php 

          if(consulta_boton_volver()): ?>
            <a href="index.php"><button>Volver</button></a> <?php
          endif; ?>

      </article> <?php
    }

    //---------------------------------------------------------BLOQUE PAGINACIÓN
    function paginar_usuario($id_usuario){
      global $con;
      global $filas_por_pag;     
      global $pagina_actual;
      global $paginas_totales;
      global $primer_tuit;
      global $total_tuits;
      
      if(empty($id_usuario))
        return array();

      $res = pg_query_params($con, "select mensaje, fecha
                                      from tuits left join relacionados on
                                            (tuits.id = relacionados.tuits_id)
                                     where usuarios_id = $1 
                                            or id_usuarios_mencionados = $1 
                                     group by tuits.id", [$id_usuario]);
      
      $total_tuits = count(pg_fetch_all($res));
      $paginas_totales = ceil($total_tuits / $filas_por_pag);

      if(isset($_GET['pag']))
        $pagina_actual = (int)$_GET['pag'];

      if($pagina_actual < 1)
        $pagina_actual = 1;
      else if($pagina_actual > $paginas_totales)
        $pagina_actual = $paginas_totales;


      $primer_tuit = ($pagina_actual - 1) * $filas_por_pag;
    }

    function paginar_hashtag($hashtag){      
      global $con;
      global $filas_por_pag;     
      global $pagina_actual;
      global $paginas_totales;
      global $primer_tuit;
      global $total_tuits;

      $res = pg_query_params($con, "select *
                                      from tuits left join hashtags_en_tuits on 
                                        (tuits.id = hashtags_en_tuits.tuits_id)
                                     where hashtags_en_tuits.hashtags_id = $1", 
                                                        [$hashtag]);
      
      $total_tuits = count(pg_fetch_all($res));
      $paginas_totales = ceil($total_tuits / $filas_por_pag);

      if(isset($_GET['pag']))
        $pagina_actual = (int)$_GET['pag'];

      if($pagina_actual < 1)
        $pagina_actual = 1;
      else if($pagina_actual > $paginas_totales)
        $pagina_actual = $paginas_totales;


      $primer_tuit = ($pagina_actual - 1) * $filas_por_pag;
    }

    function menu_paginacion(){
      global $con;
      global $filas_por_pag;     
      global $pagina_actual;
      global $paginas_totales;
      global $primer_tuit;
      global $total_tuits;

      for ($i=1; $i <= $paginas_totales; $i++) {
        if($i == $pagina_actual){ ?>
          <span> <?= $i ?> </span> <?php ;
        }
        else if($i == 1 || $i == $paginas_totales 
                || ($i >= $pagina_actual - 2 && $i <= $pagina_actual + 2)){ 

        $href = "?pag=$i";
        $href .= (isset($GET['orden'])) ? "&orden=".$_GET['orden'] : "";
        $href .= (isset($GET['nick'])) ? "&nick=".$_GET['nick'] : "";
        $href .= (isset($GET['hashtag'])) ? "&hashtag=".$_GET['hashtag'] : ""; ?>
        <a href="<?= $href ?>"> <?= $i ?> </a> <?php ;
        }
      }

    }
    //-----------------------------------------------------FIN BLOQUE PAGINACIÓN

    function comprobar_errores(){
      global $errores;

      if(!empty($errores))
        throw new Exception();    
    }

    try{
      if(!isset($_SESSION['orden']))
        $_SESSION['orden'] = "asc";

      if(isset($_GET['orden']))
        $_SESSION['orden'] = trim($_GET['orden']);

      if(isset($_POST['nuevomensaje'])){
        if(!insertar_tuit()) 
          pintar_formulario_tuits();

        comprobar_errores();

        crear_hashtags(trim($_POST['nuevomensaje']));
        relacionar_hashtags(trim($_POST['nuevomensaje']));
        relacionar_usuarios(trim($_POST['nuevomensaje']));
      } 

      if(isset($_GET['hashtag'])){
        pintar_formulario_tuits(); ?>

        <h2> #<?=trim($_GET['hashtag'])?> </h2> <?php

        pintar_tuits_hashtag(trim($_GET['hashtag']));
        comprobar_errores();
      }else if(isset($_GET['nick'])){
        pintar_formulario_tuits();

        if(trim($_GET['nick']) != $nick): ?>
          <h2> @<?=trim($_GET['nick'])?> </h2> <?php
        endif;

        $id_usuario = devolver_ids_usuarios_mencionados('@'.trim($_GET['nick']));
        comprobar_errores();

        if(empty($id_usuario)):?>
          <h3>El usuario <?= '@'.trim($_GET['nick']) ?> no existe</h3> <?php
        else:
          pintar_tuits_usuario($id_usuario[0]);
        endif;

      }else{
        pintar_formulario_tuits();
        pintar_tuits_usuario($usuario);
      }

      comprobar_errores();
    }catch(Exception $e){
      foreach ($errores as $v) { ?>
        <h3><?= "Error: " . $v ?></h3> <?php
      } 
    }finally{
      pg_query($con, "commit");
      pg_close($con);
    } ?>
  </body>
</html>