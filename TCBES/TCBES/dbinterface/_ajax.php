<?php
  require_once('_DBEHSP.php');
  $dbio = new DBEHSP();
  $dbio->set_xsl_directory_path('xsl/');
  $dbio->ajax_handler();
?>