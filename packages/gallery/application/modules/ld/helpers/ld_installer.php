<?php defined("SYSPATH") or die("No direct script access.");

class ld_installer {
  static function install() {
    module::set_version("ld", 1);
  }

  static function activate() {
    // ld::check_config();
  }

  static function deactivate() {
    site_status::clear("ld_config");
  }
}