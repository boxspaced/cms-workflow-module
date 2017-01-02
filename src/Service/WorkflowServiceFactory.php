<?php
namespace Workflow\Service;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Zend\Log\Logger;
use Zend\Authentication\AuthenticationService;
use Boxspaced\EntityManager\EntityManager;
use Workflow\Model;
use Account\Model\UserRepository;
use Core\Model\EntityFactory;

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
