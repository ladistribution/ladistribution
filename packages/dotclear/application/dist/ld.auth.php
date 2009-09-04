<?php

class ldDcAuth extends dcAuth
{

    protected $allow_pass_change = false;

    public function checkUser($user_id, $pwd=null, $user_key=null)
    {

        if ($pwd == '') {
            return parent::checkUser($user_id,null,$user_key);
        }

        $this->con->begin();
        $cur = $this->con->openCursor($this->user_table);

        $result = Ld_Auth::authenticate($user_id, $pwd);

        if ($result->isValid()) {
            
            $cur->user_pwd = $pwd;

            if ($this->core->userExists($user_id)) {
                $this->sudo(array($this->core,'updUser'),$user_id,$cur);
                $this->con->commit();
            } else {
                $user = Ld_Auth::getUser();
                $cur->user_id = $user['username'];
                $cur->user_email = $user['email'];
                $this->sudo(array($this->core,'addUser'),$cur);
                $this->sudo(array($this->core,'setUserBlogPermissions'),
                    $user_id,'default',array('usage'=>true));

                $instance = Zend_Registry::get('application');
                $role = $instance->getUserRole();
                if ($role == 'administrator') {
                    // set permissions
                }

                $this->con->commit();
            }

            return parent::checkUser($user_id,$pwd);

        }

    }

}
