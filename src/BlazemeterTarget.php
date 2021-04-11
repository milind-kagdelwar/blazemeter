<?php

namespace Drutiny\BlazeMeter;

use Drutiny\Container;
use Drutiny\Target\DrushTarget;
use Drutiny\Target\InvalidTargetException;
use Drutiny\Driver\DrushRouter;

/**
 * @Drutiny\Annotation\Target(
 *  name = "blazemeter"
 * )
 */
class BlazemeterTarget extends DrushTarget {

  protected $master;

  public function validate()
  {
    $drush = DrushRouter::createFromTarget($this);
    $drush->setOptions([
      'format' => 'int',
    ]);
    $status = $drush->status();

    if (!isset($status['files'])) {
      throw new InvalidTargetException("Drush status indicates target is not valid: " . $this->uri());
    }
    return parent::validate();
  }

}


 ?>
