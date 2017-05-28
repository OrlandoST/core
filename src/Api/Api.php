<?php
/**
 * Api
 *
 * @copyright (c) 2013, Core.net
 * @author Berdimurat Masaliev <muratmbt@gmail.com>
 */

namespace Core\Api;

use Zend\ServiceManager\ServiceManager;
use Zend\Form\Element\File as FileElement;
use Zend\Authentication\Storage\Session;
use Zend\Session\Container;
use Zend\Authentication\AuthenticationService;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\ResultSet\ResultSet;
use Zend\Authentication\Result;

class Api
{

    /**
     * The singleton Api object
     * @var \Core\Api\Api
     */
    protected static $_instance;

    /**
     * Service Manager
     * @var  \Zend\ServiceManager\ServiceManager
     */

    protected $_serviceManager;

    protected $_viewer = null;

    /**
     * @var $auth AuthenticationService
     */
    protected $auth;

    /**
     * @var $authAdapter \Zend\Authentication\Adapter\AdapterInterface
     */
    protected $authAdapter;


    /**
     * Get or create Api instance
     * @return \Core\Api\Api
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance))
        {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @link self::getInstance() Shorthand for getInstance
     * @return \Core\Api\Api
     */
    public static function _()
    {
        return self::getInstance();
    }


    /**
     *
     * @param \Core\Api\Api $api
     * @return \Core\Api\Api
     */
    public static function setInstance(Api $api)
    {
        return self::$_instance = $api;
    }


    /**
     * Set service manager
     * @param \Zend\ServiceManager\ServiceManager $sm
     * @return \Core\Api\Api
     */
    public function setServiceManager(ServiceManager $sm)
    {
        $this->_serviceManager = $sm;

        return self::getInstance();
    }

    /**
     * get service manager
     *
     * @return \Zend\ServiceManager\ServiceManager
     * @throws Exception
     */
    public function getServiceManager()
    {
        if (is_null($this->_serviceManager)) {
            throw new Exception('Service Manager is not set');
        }

        return $this->_serviceManager;
    }

    /**
     * get service manager
     *
     * @link $this->getServiceManager()
     * @return \Zend\ServiceManager\ServiceManager
     */
    public function sm()
    {
        return $this->getServiceManager();
    }

    /**
     * Get table
     *
     * @param string $tableName
     * @param string $moduleName
     * @return \Core\Model\Table
     */
    public function getDbtable($tableName, $moduleName = 'Application')
    {
        if (!$tableName || !$moduleName) {
            return null;
        }

        $factory = ucfirst(strtolower($moduleName)) . "\\Model\\Table\\" . ucfirst(strtolower($tableName));
        return $this->sm()->get($factory);
    }


    public function getVersion()
    {
        return '1.0.0';
    }

    /**
     * Returns currect user
     * @return \User\Model\User
     */
    public function getViewer()
    {
        if (is_null($this->_viewer)) {
            $identity = $this->getAuth()->getIdentity();
            $this->_viewer = $this->getUser($identity);
        }

        return $this->_viewer;
    }

    /**
     *
     * @param \User\Model\User $viewer
     * @return \Core\Api\Api
     */
    public function setViewer(\User\Model\User $viewer = null)
    {
        $this->_viewer = $viewer;
        return $this;
    }

    // Authentication

    /**
     * authenticate user
     * @param string $identity Email
     * @param string $credential Password
     * @return \Zend\Authentication\Result
     */
    public function authenticate($identity, $credential)
    {
        // Translate email
        $userTable = self::_()->getDbTable('users', 'user');
        $user = $userTable->fetchRow(array('email' => $identity));

        if($user == null)
        {
            return new Result(
                Result::FAILURE_IDENTITY_NOT_FOUND,
                null,
                ['Invalid credentials.']);
        }

        $authAdapter = $this->getAuthAdapter()
            ->setIdentity($user->getIdentity())
            ->setCredential($credential);
        return $this->getAuth()->authenticate($authAdapter);
    }

    /**
     * Get the authentication object
     * @return AuthenticationService
     */
    public function getAuth()
    {
        if (null === $this->auth) {
            $session = new Container('Infocom_Admin_Auth');
            $this->auth = new AuthenticationService(new Session('Infocom_Admin_Auth', 'identity', $session->getManager()));
        }
        return $this->auth;
    }

    /**
     * Set the authentication object
     * @param AuthenticationService $auth
     * @return EngineApi
     */
    public function setAuth(AuthenticationService $auth)
    {
        $this->auth = $auth;
        return $this;
    }

    /**
     * Get the authentication adapter
     * @return \Zend\Authentication\Adapter\AdapterInterface
     */
    public function getAuthAdapter()
    {
        if (null === $this->authAdapter) {
            $this->authAdapter = new \Zend\Authentication\Adapter\DbTable(
                $this->sm()->get('Zend\Db\Adapter\Adapter'), 'user_users', 'user_id', 'password', 'MD5(?)'
            );
        }
        return $this->authAdapter;
    }

    /**
     * Set the authentication adapter object
     * @param \Zend\Authentication\Adapter\AdapterInterface $authAdapter
     * @return AuthenticationService
     */
    public function setAuthAdapter(\Zend\Authentication\Adapter\AdapterInterface $authAdapter)
    {
        $this->authAdapter = $authAdapter;
        return $this;
    }

    protected function getUser($identity)
    {
        if (!$identity) {
            $user = new \User\Model\User();
        } else if ($identity instanceof \User\Model\User) {
            $user = $identity;
        } else if (is_numeric($identity)) {
            $user = self::_()->getDbTable('users', 'user')->fetchRow(array('user_id' => $identity));
        } else {
            $user = self::_()->getDbTable('users', 'user')->fetchRow(array('username' => $identity));
        }

        // Empty user?
        if (null === $user) {
            return new \User\Model\User();
        }

        return $user;
    }


    /**
     * Zend\Form\Element\File да getFileName деген функцияны алып салыптыр
     * Азырынча ушул жерге коюп койдум. Кийин File ды extends кылып жасайбыз если что. Болбосо ушул жерде кала берет
     *
     * @param \Zend\Form\Element\File $file
     * @param mixed $value
     * @param boolean $path
     * @return string|array
     */
    public function getFileName(FileElement $file, $value = null, $path = true)
    {
        $transferAdapter = new Http();

        if (is_null($value)) {
            $file->getValue();
        }

        if (is_null($value)) {
            $value = $file->getName();
        }

        return $transferAdapter->getFileName($value, $path);
    }

    public function paginator(\Zend\Db\Sql\Select $select, $module = 'Application')
    {
        $table = $select->getRawState($select::TABLE);
        $ex = explode('_', $table);
        $class = $module . '\Model\Table\\' . ucfirst($ex[1]);
        $table = new $class($this->sm()->get('Zend\Db\Adapter\Adapter'));

        $rowClass = $table->getRowClass();
        $resultSet = new ResultSet();
        $resultSet->setArrayObjectPrototype(new $rowClass());
        $adapter = new DbSelect($select, $this->sm()->get('Zend\Db\Adapter\Adapter'), $resultSet);
        return new Paginator($adapter);
    }

    public function sendMessage($params)
    {
        $from = $params['from'];
        $to = $params['to'];
        $subject = $params['subject'];
        $template = $params['template'];
        $type = $params['type'];
        $values = $params['values'];

        $method = 'create' . ucfirst($type) . 'Message';

        /**
         * @var $mailService \MailService\Mail\Service\Message
         */
        $mailService = $this->sm()->get('mailServiceMessage');
        $message = $mailService->$method($from, $to, $subject, $template, $values);
        $mailService->send($message);
    }

}

?>
