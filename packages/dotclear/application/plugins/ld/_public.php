<?php

$core->addBehavior('publicFooterContent', array('ldPublicBehaviors','publicFooterContent'));

class ldPublicBehaviors
{

  public static function publicFooterContent(&$core)
  {
      Ld_Ui::topBar();
      Ld_Ui::superBar(array('style' => true));
  }

}
