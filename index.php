<?php
define('LD_ROOT_CONTEXT', true);
if (file_exists('admin/dispatch.php')) require_once('admin/dispatch.php');
else echo 'La Distribution Admin component not installed.';