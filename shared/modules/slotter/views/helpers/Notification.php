<?php

class View_Helper_Notification extends Zend_View_Helper_Abstract
{

    public function notification($notification = null)
    {
        if (empty($notification)) {
            if (isset($this->view->notification)) {
                $notification = $this->view->notification;
            } else {
                $messages = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->getMessages();
                if (count($messages) > 0) {
                    $notification = $messages[0];
                } else {
                    return;
                }
            }
        }
        ?>
        <div id="ld-notification" class="ld-notice"><?php echo $notification ?></div>
        <script type="text/javascript">
        $("#ld-notification").delay(1000).fadeOut('slow');
        </script>
        <?php
    }

}
