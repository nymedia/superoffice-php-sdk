<?php

namespace nymedia\SuperOffice\resources;

use nymedia\SuperOffice\RestBase;

class Contact extends RestBase
{

  protected $resourcePath = 'Contact';

  public function getPersonsForContact($id)
  {
      return $this->get(sprintf('%s/Persons', $id));
  }

  public function getDuplicates($name)
  {
      return $this->get(sprintf('Duplicates/%s', $name));
  }

}
