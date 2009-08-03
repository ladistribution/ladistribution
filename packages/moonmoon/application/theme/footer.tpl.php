<div id="footer">
    <p>Powered by <a href="http://moonmoon.org/">moonmoon</a>
        via <a href="http://ladistribution.net/">La Distribution</a>
    <?php if ($application->getUserRole() == 'administrator') : ?>
        | <a href="./admin/">Administration</a>
    <?php endif ?>
    </p>
</div>

<?php
$conf = $application->getInstaller()->getConfiguration();
if (isset($conf['superbar']) && $conf['superbar'] == 'never') {
    // nothing
} elseif (isset($conf['superbar']) && $conf['superbar'] == 'connected' && Ld_Auth::isAuthenticated()) {
    Ld_Ui::super_bar(array('jquery' => true));
} else {
    Ld_Ui::super_bar(array('jquery' => true));
}
?>
