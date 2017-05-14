<?php
/**
 * Created by PhpStorm.
 * User: akazakbaev
 * Date: 3/31/17
 * Time: 5:28 PM
 */

namespace Core\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\MvcEvent;
use Zend\EventManager\EventManagerInterface;

class ActionController extends AbstractActionController
{
    protected $view;

    protected $routeMatch = null;

    protected function attachDefaultListeners()
    {
        parent::attachDefaultListeners();
        $events = $this->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'preDispatch'), 100);
        $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'postDispatch'), 2);
    }

    public function onDispatch(MvcEvent $e)
    {
        return parent::onDispatch($e);
    }


    public function preDispatch(MvcEvent $e)
    {

    }

    public function postDispatch(MvcEvent $e)
    {

    }

    public function init()
    {

    }

    public function setTemplate($layoutTemplate = 'layout/layout')
    {
        $this->layout()->setTemplate($layoutTemplate);
    }
}