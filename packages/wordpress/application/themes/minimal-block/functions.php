<?php

function minimal_block_class($classes = array())
{
	$classes[] = 'h6e-block';
	return $classes;
}

add_filter('h6e_block_class', 'minimal_block_class');
