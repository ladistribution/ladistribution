<?php

class Ld_Installer_Roundcube extends Ld_Installer
{

    public function install($preferences = array())
    {
        parent::install($preferences);

        $cfg = "<?php\n";
        $cfg .= "require(dirname(__FILE__) . '/../dist/main.php');\n";
        $cfg .= '$rcmail_config["des_key"]' . " = '" . Ld_Auth::generatePhrase(24) . "';\n";
        
        Ld_Files::put($this->getAbsolutePath() . "/config/main.inc.php", $cfg);

        $cfg = "<?php\n";
        $cfg .= "require(dirname(__FILE__) . '/../dist/db.php');\n";
        Ld_Files::put($this->getAbsolutePath() . "/config/db.inc.php", $cfg);
    }

    public function postInstall($preferences = array())
    {
        parent::postInstall($preferences);

        $dbConnection = $this->getInstance()->getDbConnection('php');

        $sql = $this->getSQL('initial');

        $result = $dbConnection->multi_query($sql);

        if (empty($result) && constant('LD_DEBUG')) {
            echo '<b>SQL Error:</b>:' . $dbConnection->error . '<br>';
        }
    }

    public function postUpdate()
    {
        parent::postUpdate();

        $dbConnection = $this->getInstance()->getDbConnection('php');

        $sql = $this->getSQL('update');

        $result = $dbConnection->multi_query($sql);

        if (empty($result) && constant('LD_DEBUG')) {
           echo '<b>SQL Error:</b>:' . $dbConnection->error . '<br>';
        }
    }

    protected function getSQL($type = 'initial')
    {
        $dbPrefix = $this->getInstance()->getDbPrefix();

        $sql = Ld_Files::get($this->getAbsolutePath() . "/SQL/mysql.$type.sql");

        // Remove simple line breaks
        $sql = str_replace("\n\n", "\r", $sql);
        $sql = str_replace("\n", " ", $sql);
        $sql = str_replace("\r", "\n", $sql);

        $sql = str_replace("TABLE `", "TABLE `$dbPrefix", $sql);
        
        return $sql;
    }

}
