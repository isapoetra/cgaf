<?php
define('CGAF_DEBUG',true);
if (!defined('CGAF')) {
  include "cgafinit.php";
}
if (CGAF::isInstalled()) {
  MailHelper::send("isapoetra@gmail.com", "test", "testing aja");
}