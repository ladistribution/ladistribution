<?php

require_once 'Ld/Files.php';
require_once 'Ld/Preference.php';

class Ld_Installer
{
    
    public function __construct($params = array())
    {
        $this->dir = $params['dir'];
        
        $this->id = isset($params['id']) ? $params['id'] : null;
        $this->instance = isset($params['instance']) ? $params['instance'] : null;
        
        if (isset($this->instance)) {
            $this->setPath($this->instance->getPath());
            $this->setAbsolutePath($this->instance->getAbsolutePath());
        }
        
        if (empty($params['dbPrefix'])) {
            $this->dbPrefix = str_replace('-', '_', $this->id) . '_' . uniqid() . '_';
        } else {
            $this->dbPrefix = $params['dbPrefix'];
        }
        
        $filename = $this->dir . '/dist/manifest.xml';
        if (!file_exists($filename)) {
            $filename = $this->dir . '/manifest.xml'; // alternate name
        }
        if (file_exists($filename)) {
            $manifestXml = file_get_contents($filename);
        } else {
            throw new Exception("manifest.xml doesn't exists or is unreadable in $this->dir");
        }
        
        $this->manifest = new SimpleXMLElement($manifestXml);
    }
    
    public function getManifest()
    {
        return $this->manifest;
    }
    
    public function getDependencies()
    {
        $dependencies = array();
        foreach ($this->manifest->need as $need) {
            $dependencies[] = (string)$need;
        }
        return $dependencies;
    }
    
    public function getDeployments()
    {
        // Default Rules
        $type = (string)$this->manifest->type;
        $rules = array();
        
        // $rules[$type] = array('origin' => $type, 'path' => 'public', 'destination' => '');
        // $rules['dist'] = array('origin' => 'dist', 'path' => 'public', 'destination' => 'dist');
        
        switch ($type) {
            case 'application':
            case 'theme':
            case 'plugin':
            case 'locale':
                $rules[$type] = array('origin' => $type, 'path' => 'public', 'destination' => '');
                $rules['dist'] = array('origin' => 'dist', 'path' => 'public', 'destination' => 'dist');
            default:
        }
        
        foreach ($this->manifest->deploy as $deploy) {
            $id = (string)$deploy->origin;
            $rules[$id] = array(
                'origin' => (string)$deploy->origin,
                'path' => (string)$deploy->destination['path'],
                'destination' => (string)$deploy->destination
            );
        }
        
        $deployments = array();
        foreach ($rules as $rule) {
            $from = $this->dir . $rule['origin'];
            switch ($rule['path']) {
                case 'lib':
                    $to = LD_LIB_DIR . "/" . $rule['destination'];
                    break;
                case 'css':
                    $to = LD_CSS_DIR . "/" . $rule['destination'];
                    break;
                case 'shared':
                    $to = LD_SHARED_DIR . "/" . $rule['destination'];
                    break;
                case 'public':
                    $to = $this->absolutePath . "/" . $rule['destination'];
                    break;
                default:
                    throw new Exception('Path scheme not known.');
            }
            $deployments[] = compact('from', 'to');
        }
        return $deployments;
    }
    
    public function getExtendedPath()
    {
        if (isset($this->manifest->directory)) {
            return (string)$this->manifest->directory;
        }
        return null;
    }
    
    public function deploy($path = null)
    {
        if (isset($path)) {
            $this->setPath($path);
            if (!file_exists($this->absolutePath)) {
                mkdir($this->absolutePath, 0777, true);
            }
        }
        
        foreach ($this->getDeployments() as $deployment) {
            Ld_Files::copy($deployment['from'], $deployment['to']);
        }
    }
    
    public function install($preferences = array())
    {
        if (empty($preferences['path'])) {
            $preferences['path'] = null;
            // throw new Exception("Path can't be empty.");
        }
        $this->deploy($preferences['path']);
        
        // Config
        // FIXME: Only if type=application ?
        $cfg_ld = "<?php\n";
        $cfg_ld .= "require_once('" . LD_DIST_DIR . "/config.php');\n";
        if (isset($preferences['db'])) {
            $cfg_ld .= "require_once('" . LD_DIST_DIR . "/db/" . $preferences['db'] . ".php');\n";
        }
        if (!empty($preferences['path'])) {
            file_put_contents($this->absolutePath . "/dist/config.php", $cfg_ld);
        }
    }
    
    public function update()
    {
        $this->deploy();
    }
    
    public function uninstall()
    {
        if (empty($this->absolutePath)) {
            throw new Exception("Path is undefined");
        }
        
        // Erase files (would be better to delete one by one)
        foreach ($this->getDeployments() as $deployment) {
            Ld_Files::unlink($deployment['to']);
        }
        
        // DROP tables with current prefix
        if ($this->needDb() && isset($this->instance)) {
            $dbName = $this->instance->getDb();
            $dbPrefix = $this->instance->getDbPrefix();
            require LD_DIST_DIR . '/db/' . $dbName . '.php';
            $db = Zend_Db::factory('Mysqli', array(
                'host'     => LD_DB_HOST,
                'username' => LD_DB_USER,
                'password' => LD_DB_PASSWORD,
                'dbname'   => LD_DB_NAME
            ));
            $result = $db->fetchCol('SHOW TABLES');
            foreach ($result as $tablename) {
                if (strpos($tablename, $dbPrefix) !== false) {
                    $db->query("DROP TABLE $tablename");
                }
            }
        }
        
    }
    
    public function backup()
    {
        $timestamp = date("d-m-Y-H-i-s");
        $this->tmpFolder = LD_TMP_DIR . '/backup-' . $timestamp;
        if (!file_exists($this->tmpFolder)) {
            mkdir($this->tmpFolder . '/dist', 0777, true);
        }
        
        if (!file_exists($this->absolutePath . '/backups')) {
            mkdir($this->absolutePath . '/backups', 0777, true);
        }
        
        $directories = array('dist' => $this->absolutePath . '/dist/');
        $directories = array_merge($directories, $this->getBackupDirectories());
        
        $filename = 'backup-' . $timestamp . '.zip';

        $fp = fopen($this->absolutePath . '/backups/' . $filename, 'wb');
        $zip = new fileZip($fp);
        foreach ($directories as $name => $directory) {
            if (file_exists($directory)) {
                $zip->addDirectory($directory, $name, true);
            }
        }
        $zip->write();
        unset($zip);
        
        Ld_Files::unlink($this->tmpFolder);
    }
    
    public function restore($filename, $absolute = false)
    {
        $timestamp = date("d-m-Y-H-i-s");
        $this->tmpFolder = LD_TMP_DIR . '/backup-' . $timestamp;

        if ($absolute == false) {
            $filename = $this->absolutePath . '/backups/' . $filename;
        }

        $uz = new fileUnzip($filename);
        $uz->unzipAll($this->tmpFolder);
    }
    
    public function restrict($state = true)
    {
        $dir = $this->absolutePath . '/dist/prepend';
        $filename = $dir . '/restrict.php';
        if ($state == true) {
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $restrict  = "<?php\n";
            $restrict .= "require_once 'Ld/Auth.php';\n";
            $restrict .= "Ld_Auth::restricted();\n";
            file_put_contents($filename, $restrict);
        } else {
            if (file_exists($filename)) {
                Ld_Files::unlink($filename);
            }
        }
    }

    public function getPreferences($type = 'configuration')
    {
        $preferences = array();
        
        if (isset($this->manifest->$type)) {
            foreach ($this->manifest->$type->preference as $prefXML) {
                $attr = $prefXML->attributes();
                $pref = new Ld_Preference((string) $attr['type']);
                $pref->setName((string) $attr['name']);
                $pref->setLabel((string) $attr['label']);
                if (isset($attr['defaultValue'])) {
                    $pref->setDefaultValue((string) $attr['defaultValue']);
                }
                foreach ($prefXML->option as $option) {
                    if (empty($option['label'])) {
                        $pref->addListOption((string) $option['value']);
                    } else {
                        $pref->addListOption((string) $option['value'], (string) $option['label']);
                    }
                }
                if ($pref->getType() == 'range') {
                    $pref->setRangeOptions((string) $attr['step'], (string) $attr['min'], (string) $attr['max']);
                }
                $preferences[] = $pref;
            }
        }
        return $preferences;
    }

    public function needDb()
    {
        if (isset($this->manifest->db)) {
            return (string) $this->manifest->db;
        }
        return false;
    }
    
    public function setPath($path)
    {
        $this->path = $path;
        $this->absolutePath = LD_ROOT . '/' . $path;
    }
    
    public function setAbsolutePath($path)
    {
        $this->absolutePath = $path;
    }
    
    public function configure() {}
    
    public function getConfiguration() { return array(); }
     
    public function getThemes() { return array(); }
    
    public function getBackupDirectories() { return array(); }

    // Legacy
    
    protected function _copy($from, $to) { return Ld_Files::copy($from, $to); }
    
    protected function _unlink($src) { return Ld_Files::unlink($src); }
    
    protected function _getDirectories($dir) { return Ld_Files::getDirectories($dir); }
    
    
    protected function _generate_phrase($length = 64)
    {
        $chars = "234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $i = 0;
        $password = "";
        while ($i <= $length) {
            $password .= $chars{mt_rand(0,strlen($chars)-1)};
            $i++;
        }
        return $password;
    }

}
