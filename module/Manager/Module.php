<?php
namespace Manager;

use Zend\Mvc\MvcEvent;

use Manager\Entity\Document;
use Manager\Entity\Job;
use Manager\Entity\Metadata;
use Manager\Form\UploadForm;
use Manager\Form\UploadFormInputFilter;
use Manager\Model\DAO\DocumentDAO;
use Manager\Model\DAO\JobDAO;
use Manager\Model\DAO\MetadataDAO;
use Manager\Model\Queue\Manager;

class Module
{
    /**
     * Get config
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get autoloader config
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php'
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    /**
     * Get service config
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Manager\Entity\Document' => function($sm)
                {
                    return new Document;
                },
                'Manager\Entity\Job' => function($sm)
                {
                    return new Job;
                },
                'Manager\Entity\Metadata' => function($sm)
                {
                    $citationStyles = $sm->get('CitationstyleConversion\Model\Citationstyles');
                    return new Metadata($citationStyles);
                },
                'Manager\Form\UploadForm' => function($sm)
                {
                    $citationStyles = $sm->get('CitationstyleConversion\Model\Citationstyles');
                    $translator = $sm->get('translator');
                    return new UploadForm($translator, $citationStyles);
                },
                'Manager\Form\UploadFormInputFilter' => function($sm)
                {
                    return new UploadFormInputFilter();
                },
                'Manager\Model\DAO\DocumentDAO' => function($sm)
                {
                    $em = $sm->get('doctrine.entitymanager.orm_default');
                    return new DocumentDAO($em);
                },
                'Manager\Model\DAO\JobDAO' => function($sm)
                {
                    $em = $sm->get('doctrine.entitymanager.orm_default');
                    return new JobDAO($em);
                },
                'Manager\Model\DAO\MetadataDAO' => function($sm)
                {
                    $em = $sm->get('doctrine.entitymanager.orm_default');
                    return new MetadataDAO($em);
                },
                'Manager\Model\Queue\Manager' => function($sm)
                {
                    $logger = $sm->get('Logger');
                    $translator = $sm->get('translator');
                    $queueManager = $sm->get('SlmQueue\Queue\QueuePluginManager');
                    $jobManager = $sm->get('SlmQueue\Job\JobPluginManager');
                    $jobDAO = $sm->get('Manager\Model\DAO\JobDAO');
                    return new Manager(
                        $logger,
                        $translator,
                        $queueManager,
                        $jobManager,
                        $jobDAO
                    );
                },
            ),
            'shared' => array(
                'Manager\Entity\Job' => false,
                'Manager\Entity\Document' => false,
            )
        );
    }

    /**
     * Get controller config
     *
     * @return array
     */
    public function getControllerConfig()
    {
        return array(
            'factories' => array(
                'Manager\Controller\Manager' => function($cm)
                {
                    $sm = $cm->getServiceLocator();
                    $logger = $sm->get('Logger');
                    $translator = $sm->get('Translator');
                    $queueManager = $sm->get('Manager\Model\Queue\Manager');
                    $uploadForm = $sm->get('Manager\Form\UploadForm');
                    $uploadFormInputFilter = $sm->get('Manager\Form\UploadFormInputFilter');
                    $documentDAO = $sm->get('Manager\Model\DAO\DocumentDAO');
                    $jobDAO = $sm->get('Manager\Model\DAO\JobDAO');
                    $metadataDAO = $sm->get('Manager\Model\DAO\MetadataDAO');

                    return new Controller\ManagerController(
                        $logger,
                        $translator,
                        $queueManager,
                        $uploadForm,
                        $uploadFormInputFilter,
                        $documentDAO,
                        $jobDAO,
                        $metadataDAO
                    );
                }
            )
        );
    }
}
