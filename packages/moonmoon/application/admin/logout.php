<?php

require_once(dirname(__FILE__) . '/../dist/prepend.php');

Ld_Auth::logout();

header('Location:' . $application->getUrl());
