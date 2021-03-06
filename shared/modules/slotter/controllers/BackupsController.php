<?php

require_once 'BaseController.php';

class Slotter_BackupsController extends Slotter_BaseController
{

    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->userCan('admin')) {
            $this->disallow();
        }

        $this->appendTitle( $this->translate('Backups') );
    }

    public function indexAction()
    {
        // Do backup
        if ($this->getRequest()->isPost() && $this->_hasParam('dobackup')) {
            $this->site->doBackup();
            $this->view->notification = $this->translate("Backup generated");
        }

        // Download
        if ($this->_hasParam('download')) {
            foreach ($this->_getBackups() as $backup) {
                if ($backup['filename'] == $this->_getParam('download')) {
                    return $this->_sendBackup($backup['absoluteFilename'], $backup['filename']);
                }
            }
            throw new Exception('Non existing backup.');
        }

        // Delete
        if ($this->getRequest()->isPost() && $this->_hasParam('delete')) {
            foreach ($this->_getBackups() as $backup) {
                if ($backup['filename'] == $this->_getParam('delete')) {
                    Ld_Files::rm($backup['absoluteFilename']);
                }
            }
            $this->view->notification = $this->translate("Backup deleted");
        }

        // Restore
        if ($this->getRequest()->isPost() && $this->_hasParam('restore')) {
            foreach ($this->_getBackups() as $backup) {
                if ($backup['filename'] == $this->_getParam('restore')) {
                    $this->_restoreBackup( $backup['absoluteFilename'] );
                }
            }
            $this->view->notification = $this->translate("Backup restored");
        }

        $this->view->backups = $this->_getBackups();
    }

    public function downloadAction()
    {
        $freshness = $this->_getParam('freshness', 24 * 60 * 60);

        $backupsPath = $this->site->getDirectory('dist') . '/backups';

        foreach ($this->_getBackups() as $backup) {
            if (time() - filemtime($backup['absoluteFilename']) < $freshness) {
                return $this->_sendBackup($backup['absoluteFilename'], $backup['filename']);
            }
        }

        $backup = $this->site->doBackup();
        return $this->_sendBackup($backup['absoluteFilename'], $backup['filename']);
    }

    protected function _getBackups()
    {
        $backupsPath = $this->site->getDirectory('dist') . '/backups';

        $backups = array();
        foreach (Ld_Files::getFiles($backupsPath) as $filename) {
            $absoluteFilename = $backupsPath . '/' . $filename;
            $size = round( filesize($absoluteFilename) / 1024 ) . ' ko';
            $time = filemtime($absoluteFilename);
            $backups[$filename] = compact('filename', 'absoluteFilename', 'size', 'time');
        }
        ksort($backups);
        return $backups;
    }

    protected function _restoreBackup($backupFilename)
    {
        $site = $this->getSite();
        $siteDistDir = $site->getDirectory('dist');

        $tmpDir = $site->getDirectory('tmp') . '/site-restore-' . date("d-m-Y-H-i-s");
        $tmpDistDir = $tmpDir . '/dist';

        Ld_Zip::extract($backupFilename, $tmpDir);

        // Configuration
        $config = $originalConfig = Ld_Files::getJson("$tmpDistDir/config.json");
        $config['host'] = $site->getConfig('host');
        $config['path'] = $site->getConfig('path');
        $site->setConfig($config);

        // Dist files
        foreach (array('colors.json', 'users.json', 'locales.json') as $file) {
            Ld_Files::copy("$tmpDistDir/$file", "$siteDistDir/$file");
            Ld_Files::copy("$tmpDistDir/$file.php", "$siteDistDir/$file.php");
        }
        Ld_Files::copy("$tmpDistDir/custom.css", "$siteDistDir/custom.css");

        // Instances
        $instances = Ld_Files::getJson("$tmpDistDir/instances.json");
        foreach ($instances as $id => $infos) {
            if ($infos['type'] == 'application') {
                if ($instance = $site->getInstance($infos['path'])) {
                    $site->deleteInstance($instance);
                }
                try {
                    $instance = $site->cloneInstance("$tmpDir/$id.zip", $infos);
                } catch (Exception $e) {
                    Ld_Files::log('cloneInstance', "exception with instance $id: " . $e->getMessage());
                }
            }
        }

        Ld_Files::rm($tmpDir);
    }

    protected function _sendBackup($absoluteFileName, $filename)
    {
        ob_end_clean();
        header('Content-Type: application/zip', true);
        header('Content-Disposition: attachment; filename="' . $filename . '"', true);
        header("Content-Length: ". filesize($absoluteFileName), true);
        $handle = fopen($absoluteFileName, "rb");
        while ( ($buffer = fread($handle, 8192)) != '' ) {
            echo $buffer;
        }
        fclose($handle);
        exit;
    }

}
