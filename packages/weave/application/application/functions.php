<?php

function weave_get_css_url($file, $package)
{
    $site = Zend_Registry::get('site');
    $infos = $site->getLibraryInfos("css-$package");
    $url = $site->getUrl('css') . $file . '?v=' . $infos['version'];
    return $url;
}
