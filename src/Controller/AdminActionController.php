<?php
/**
 * Core
 *
 */

namespace Core\Controller;

use Core\Api\Api;
use Zend\Mvc\MvcEvent;
use Zend\Session\Container;

class AdminActionController extends ActionController
{
    public function preDispatch(MvcEvent $e)
    {
        $session = new Container('Infocom_Admin_Auth');
        if (time() > 1400 + $session->start) {
            unset($session->identity);
            unset($session->start);

            Api::_()->getAuth()->clearIdentity();
            Api::_()->setViewer();
            return $this->redirect()->toRoute('admin_login');
        }
        else
        {
            $session->start = time();

            if(Api::_()->getViewer()->getIdentity() && Api::_()->getViewer()->level_id != 1){
                return $this->redirect()->toRoute('home');
            }
            elseif(!Api::_()->getViewer()->getIdentity())
            {
                return $this->redirect()->toRoute('home');
            }
        }
    }
    
    public function postDispatch(MvcEvent $e)
    {
            $this->setTemplate('layout/admin');
    }
}
