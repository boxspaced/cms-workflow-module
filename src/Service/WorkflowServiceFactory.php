<?php
namespace Boxspaced\CmsWorkflowModule\Service;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Zend\Log\Logger;
use Zend\Authentication\AuthenticationService;
use Boxspaced\EntityManager\EntityManager;
use Boxspaced\CmsWorkflowModule\Model;
use Boxspaced\CmsAccountModule\Model\UserRepository;
use Boxspaced\CmsCoreModule\Model\EntityFactory;

class WorkflowServiceFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new WorkflowService(
            $container->get(Logger::class),
            $container->get(AuthenticationService::class),
            $container->get(EntityManager::class),
            $container->get(UserRepository::class),
            $container->get(Model\WorkflowService::class),
            $container->get(EntityFactory::class)
        );
    }

}
