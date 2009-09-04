<?php

$core->addBehavior('publicFooterContent', array('ldPublicBehaviors','publicFooterContent'));

class ldPublicBehaviors
{

  public static function publicFooterContent(&$core)
  {
      Ld_Ui::top_bar();
      Ld_Ui::super_bar(array('style' => true));
  }

}
