<?php

if (!function_exists('_')):
function _($s) { return $s; }
endif;

if (!function_exists('bindtextdomain')):
function bindtextdomain() { return false; }
endif;

if (!function_exists('bind_textdomain_codeset')):
function bind_textdomain_codeset() { return false; }
endif;

if (!function_exists('textdomain')):
function textdomain() { return false; }
endif;
