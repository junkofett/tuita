<?php
  function conectar(){
    return pg_connect("host=localhost user=tuita password=tuita
                       dbname=tuita");
  }

  function redireccion_login(){
      header("Location: login/login.php");
  }

  function limpiar_datos_post(){
      foreach ($_POST as $key => $value)
        $_POST["$key"] = trim($value);
    }
