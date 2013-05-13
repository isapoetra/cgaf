<?php
if (!defined('CGAF')) {
  include "cgafinit.php";
}
if (!CGAF::isInstalled()) {
  include "install.php";
} else {
  CGAF::Run();
}
?>