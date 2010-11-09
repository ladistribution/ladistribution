<?php
/*
Plugin Name: LD feed
Plugin URI: http://h6e.net/wordpress/plugins/ld-feed
Description: Enhance Atom feed with La Distribution specifics values
Version: 0.5.1
Author: h6e.net
Author URI: http://h6e.net/
*/

function ld_atom_entry()
{
	$username = get_the_author_meta('login');
	echo '<ld:username>' . $username . '</ld:username>' . "\n";
}

add_action('atom_entry', 'ld_atom_entry');

function ld_atom_ns()
{
    echo 'xmlns:ld="http://ladistribution.net/#ns"';
}

add_action('atom_ns', 'ld_atom_ns');
