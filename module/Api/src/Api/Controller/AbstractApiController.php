<?php
namespace Api\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\I18n\Translator;
use Zend\View\Model\JsonModel;
use Zend\Authentication\AuthenticationService;
use Xmlps\Logger\Logger;

class AbstractApiController extends AbstractActionController {
    protected $logger;
    protected $translator;
    protected $authService;

    /**
     * Constructor
     *
     * @param Logger $logger
     * @param Translator $translator
     * @param AuthenticationService $authService
     *
     * @return void
     */
    public function __construct(
        Logger $logger,
        Translator $translator,
        AuthenticationService $authService
    )
    {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->authService = $authService;
    }

    /**
     * Execute the request
     *
     * @param  MvcEvent $e
     * @return mixed
     * @throws Exception\DomainException
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();
        if (!$routeMatch) {
            throw new Exception\DomainException('Missing route matches; unsure how to retrieve action');
        }

        $action = $routeMatch->getParam('action', 'not-found');
        $method = static::getMethodFromAction($action);

        if (!method_exists($this, $method)) {
            $method = 'notFoundAction';
        }

        $this->preDispatch();
        $actionResponse = $this->$method();
        $actionResponse = $this->postDispatch($actionResponse);

        $e->setResult($actionResponse);

        return $actionResponse;
    }

    /**
     * Authorize the request
     *
     * @return void
     */
    protected function preDispatch()
    {
        $email = '';
        $password = '';
        if ($this->request->isPost()) {
            $email = $this->params()->fromPost('email');
            $password = $this->params()->fromPost('password');
        }
        else {
            $email = $this->params()->fromQuery('email');
            $password = $this->params()->fromQuery('password');
        }

        if (!empty($email) and !empty($password)) {
            $adapter = $this->authService->getAdapter();
            $adapter->setIdentityValue($email);
            $adapter->setCredentialValue($password);
            $authResult = $this->authService->authenticate();
        }
    }

    /**
     * Convert the response into a JsonModel object
     *
     * @param mixed $actionResponse
     * @return JsonModel response object
     */
    protected function postDispatch($actionResponse)
    {
        if (!is_array($actionResponse)) $actionResponse = array();
        return new JsonModel($actionResponse);
    }
}
