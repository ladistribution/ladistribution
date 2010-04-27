<?php

class View_Helper_UserRenderer extends Zend_View_Helper_Abstract
{

    public function userRenderer($user, $params = array())
    {
            $username = $user['username'];
            ?>
            <div id="user_<?php echo $username ?>" class="ld-user">
                <label>
                <?php if (isset($params['checkbox']) && $params['checkbox']) : ?>
                    <?php if (isset($params['disabled']) && $params['disabled']) : ?>
                        <input name="users[<?php echo $username ?>]" type="checkbox" class="checkbox" checked="checked" disabled="disabled"/>
                    <?php else : ?>
                        <input name="users[<?php echo $username ?>]" type="checkbox" class="checkbox"/>
                    <?php endif ?>
                <?php endif ?>
                <?php echo Ld_Ui::getAvatar($user) ?>
                <span class="username"><?php echo $username ?></span>
                <?php if (isset($user['fullname'])) : ?><span class="fullname"><?php echo $user['fullname'] ?></span><?php endif ?>
                </label>
            </div>
            <?php
    }

}
