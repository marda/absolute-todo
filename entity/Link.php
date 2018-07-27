<?php

namespace Absolute\Module\Todo\Classes;


class Link  
{

  private $id;
  private $source;
  private $target;
  private $type;

	public function __construct($id, $source, $target, $type) 
  {
    $this->id = $id;
		$this->source = $source;
    $this->target = $target;
    $this->type = $type;
	}

  public function getId() 
  {
    return $this->id;
  }

  public function getSource() 
  {
    return $this->source;
  }

  public function getTarget() 
  {
    return $this->target;
  }

  public function getType() 
  {
    return $this->type;
  }

  // SETTERS

  // ADDERS

  // OTHER METHODS  

  public function toJson() 
  {
    return array(
      "id" => $this->id,
      "source" => $this->source,
      "target" => $this->target,
      "type" => $this->type,
    );
  }
}

