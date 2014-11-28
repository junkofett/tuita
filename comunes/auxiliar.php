<?php
  function conectar(){
    return pg_connect("host=localhost user=tuita password=tuita
                       dbname=tuita");
  }