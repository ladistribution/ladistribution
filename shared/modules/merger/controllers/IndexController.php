<?php

/**
 * Index controller
 */
class Merger_IndexController extends Ld_Controller_Action
{

    public function init()
    {
        parent::init();

        $this->view->addHelperPath(dirname(__FILE__) . '/../../slotter/views/helpers/', 'View_Helper');

        if ($this->getSite()->isChild() && $owner = $this->site->getOwner()) {
            $this->view->layoutTitle = $owner['fullname'];
        } else {
            $this->view->layoutTitle = "News Feed";
        }
    }

    public function indexAction()
    {
        $this->_setTitle('News Feed');

        $this->view->baseUrl = $this->getRequest()->getBaseUrl();

        // code should be elsewhere
        $username = Ld_Auth::getUsername();
        $this->view->userRole = $this->userRole = $this->admin->getUserRole($username);

        $mainsite = $this->getSite();
        $sites = array($mainsite);
        foreach ($mainsite->getSites() as $id => $config) {
            $subsite = new Ld_Site_Child($config);
            $subsite->setParentSite($mainsite);
            $sites[] = $subsite;
        }

        $feeds = array();
        foreach ($sites as $site) {
            // echo "Site:" . $site->getUrl() . "<br>\n";
            foreach ($site->getInstances('application') as $id => $infos) {
                $instance = $site->getInstance($id);
                // echo "Application:" . $instance->getUrl() . "<br>\n";
                foreach ($instance->getLinks() as $link) {
                    $type = (string)$link['type'];
                    if ($type == 'application/atom+xml' || $type == 'application/rss+xml') {
                        // echo "URL:" . (string)$link['href'] . "<br>\n";
                        $feeds[] = array(
                            'application' => $instance,
                            'url' => (string)$link['href']
                        );
                    }
                }
            }
        }

        if (Zend_Registry::isRegistered('cache')) {
            $cache = Zend_Registry::get('cache');
            Zend_Feed_Reader::setCache($cache);
            Zend_Feed_Reader::useHttpConditionalGet();
        }

        if (!Zend_Feed_Reader::isRegistered('Ld')) {
            Zend_Feed_Reader::addPrefixPath('Ld_Feed_Reader_Extension', LD_LIB_DIR . '/Ld/Feed/Reader/Extension');
            Zend_Feed_Reader::registerExtension('Ld');
        }

        $entries = array();
        foreach ($feeds as $feed) {
            try {
                $entries = array_merge($entries, $this->_getEntriesAsArray($feed));
            } catch (Exception $e) {
                echo "Error with " . $feed['url'] . ".<br>";
            }
        }

        usort($entries, array($this, '_cmpEntries'));

        $this->view->entries = $entries;
    }

    private function _cmpEntries($a , $b)
    {
        $a_time = $a['date'];
        $b_time = $b['date'];
        if ($a_time == $b_time) {
            return 0;
        }
        return ($a_time > $b_time) ? -1 : 1;
    }

    private function _getEntriesAsArray($feed)
    {
        $zend_feed = Zend_Feed_Reader::import($feed['url']);

        $entries = array();
        foreach ($zend_feed as $entry) {

            $user = null;
            $username = $entry->getUsername();
            if (isset($username)) {
                $user = $this->getSite()->getUser($username);
                $name = $user['fullname'];
            }
            if (empty($user)) {
                $author = $entry->getAuthor();
                $name = $author['name'];
                $user = $this->getSite()->getUser($name);
            }
            if (isset($user)) {
                $name = $user['fullname'];
            }

            $entry = array(
                'application' => $feed['application']->getId(),
                'package' => $feed['application']->getPackageId(),
                'title' => $entry->getTitle(),
                'enclosure' => $entry->getEnclosure(),
                'type' => $entry->getPostType(),
                'name' => $name,
                'user' => $user,
                'link' => $entry->getLink(),
                'date' => $entry->getDateCreated()->getTimestamp(),
                'time' => Ld_Ui::relativeTime($entry->getDateCreated()->getTimestamp()),
                'content' => $entry->getContent()
            );

            $entries[] = $this->_normaliseEntry($entry);

        }
        return $entries;
   }

    private function _normaliseEntry($entry)
    {
        switch ($entry['package']) {
            case 'blogmarks':
                $entry['type'] = 'link';
                $entry['action'] = 'posted a link';
                break;
            case 'statusnet':
                $entry['type'] = 'status';
                break;
            case 'dokuwiki':
                $entry['action'] = 'modified a page';
                break;
            case 'bbpress':
                $entry['title'] = str_replace($entry['name'] . ' on', '', $entry['title']);
                $entry['title'] = trim($entry['title'], " \"");
                $entry['action'] = 'created a topic';
                break;
            case 'wordpress':
                $entry['action'] = 'published a post';
                break;
        }
        return $entry;
    }

}
