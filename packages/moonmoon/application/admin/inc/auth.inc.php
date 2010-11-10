<?php

require_once dirname(__FILE__).'/../../dist/prepend.php';

if (Ld_Auth::isAuthenticated()) {
    if ($application->getUserRole() == 'administrator') {
        // ok, cool
        return;
    } else {
        $message = "You're not allowed to do that.";
    }
} else {
    $message = "You're not authenticated.";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/HTML; charset=UTF-8" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Language" content="en" />

    <title>moonmoon administration</title>
    <link rel="stylesheet" media="screen" type="text/css" href="default.css" />
    <?php include("inc/head.inc.php");  ?>
</head>

<body id="admin-feed">
    <?php include("inc/top.inc.php");  ?>
    <div id="page">
        <div id="header">
            <h1>moonmoon</h1>
            <p><a href="../">Back to main page</a></p>
        </div>
        <div id="content">
            <p><?php echo $message ?></p>
        </div>
    </div>
    <?php include("inc/footer.inc.php");  ?>
</body>
</html>
<?php exit ?>