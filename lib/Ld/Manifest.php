<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Manifest
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Manifest
{

    public function __construct($xml, $rawXml)
    {
        $this->xml = $xml;

        $this->rawXml = $rawXml;
    }

    public static function loadFromDirectory($dir)
    {
        $filename = $dir . '/dist/manifest.xml';
        if (!file_exists($filename)) {
            $filename = $dir . '/manifest.xml'; // alternate name
        }
        if (file_exists($filename)) {
            return self::parse($filename);
        } else {
            throw new Exception("manifest.xml doesn't exists or is unreadable in $dir");
        }
    }

    public static function loadFromZip($zip)
    {
        $tmpFolder = LD_TMP_DIR . '/package-' . date("d-m-Y-H-i-s");
        $uz = new fileUnzip($zip);
        $uz->unzipAll($tmpFolder);

        $manifest = self::loadFromDirectory($tmpFolder);

        Ld_Files::unlink($tmpFolder);

        return $manifest;
    }

    public static function parse($filename)
    {
        $rawXml = Ld_Files::get($filename);

        try {
            $xml = new SimpleXMLElement($rawXml);
        } catch (Exception $e) {
            throw new Exception("Can't parse $filename as XML.");
        }

        return new Ld_Manifest($xml, $rawXml);
    }

    public function getXml()
    {
        return $this->xml;
    }

    public function getRawXml()
    {
        return $this->rawXml;
    }

    public function getInfos()
    {
        $infos = array();

        $keys = array('id', 'name', 'type', 'version', 'extend', 'url');
        foreach ($keys as $key) {
            if (isset($this->xml->$key)) {
                $infos[$key] = (string)$this->xml->$key;
            }
        }

        return $infos;
    }

    public function getId()
    {
        if (isset($this->xml->id)) {
            return (string)$this->xml->id;
        }
        throw new Exception("id is undefined.");
    }

    public function getType()
    {
        if (isset($this->xml->type)) {
            return (string)$this->xml->type;
        }
        throw new Exception("type is undefined.");
    }

    public function getPreferences($type = 'configuration')
    {
        $preferences = array();

        if (isset($this->xml->$type)) {
            foreach ($this->xml->$type->preference as $xmlPref) {
                $attr = $xmlPref->attributes();
                $pref = new Ld_Preference((string) $attr['type']);
                $pref->setName((string) $attr['name']);
                $pref->setLabel((string) $attr['label']);
                if (isset($attr['defaultValue'])) {
                    $pref->setDefaultValue((string) $attr['defaultValue']);
                }
                foreach ($xmlPref->option as $option) {
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

    public function getDependencies()
    {
        $dependencies = array();
        foreach ($this->xml->need as $need) {
            $dependencies[] = (string)$need;
        }
        return $dependencies;
    }

    public function getDb()
    {
        if (isset($this->xml->db)) {
            return (string) $this->xml->db;
        }
        return false;
    }

    public function getDirectory()
    {
        if (isset($this->xml->directory)) {
            return (string)$this->xml->directory;
        }
        return null;
    }

    public function getLinks()
    {
        $links = array();
        foreach ($this->xml->link as $link) {
            $title = (string)$link['title'];
            $rel   = (string)$link['rel'];
            $type  = (string)$link['type'];
            $href  = (string)$link['href'];
            $links[] = compact('title', 'rel', 'href', 'type');
        }
        return $links;
    }

    public function getDeploymentRules()
    {
        $type = $this->getType();

        $rules = array();

        switch ($type) {
            case 'application':
            case 'theme':
            case 'plugin':
            case 'locale':
                $rules[$type] = array('origin' => $type, 'path' => 'public', 'destination' => '');
                $rules['dist'] = array('origin' => 'dist', 'path' => 'public', 'destination' => 'dist');
                break;
            case 'lib':
            case 'css':
            case 'js':
            case 'shared':
                $rules[$type] = array('origin' => $type, 'path' => $type, 'destination' => '');
                break;
            default:
        }

        foreach ($this->xml->deploy as $deploy) {
            $id = (string)$deploy->origin;
            $rules[$id] = array(
                'origin' => (string)$deploy->origin,
                'path' => (string)$deploy->destination['path'],
                'destination' => (string)$deploy->destination
            );
        }

        return $rules;
    }

    public function getClassName()
    {
        if (isset($this->xml->installer)) {
            return (string)$this->xml->installer['name'];
        }
        return 'Ld_Installer_' . ucfirst($this->getId());
    }

}
