<?php

namespace Core\Form;

use Zend\Form\Form;
use Zend\Form\Element;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

abstract class AbstractForm extends Form implements InputFilterAwareInterface
{
  protected $inputFilter;

  public function isValid($data = array())
  {
    $this->setInputFilter($this->getInputFilter());
    return $this->setData($data);
  }

  public function setData($data)
  {
    parent::setData($data);
    return parent::isValid();
  }

  public function setInputFilter(InputFilterInterface $inputFilter)
  {
    parent::setInputFilter($inputFilter);
  }

  public function getInputFilter()
  {
    parent::getInputFilter();

    return $this->inputFilter;
  }

  public function clearData()
  {
    $data = $this->getData(\Zend\Form\FormInterface::VALUES_AS_ARRAY);
    $keys = array_keys($data);
    $data = array_combine($keys, array_fill(0, count($keys), null));
    parent::setData($data);
  }
  
  /**
   * Return All Elements Values
   * @return array
   */
  public function getValues(){
      $elements = $this->getElements();
      $values = array();
      foreach ($elements as $element){
          if($element->getAttribute('type') != 'submit'){
            $values[$element->getName()] = $element->getValue();
          }
      }
      
      return $values;
  }
}