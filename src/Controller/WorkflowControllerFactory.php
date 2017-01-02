<?php
namespace Workflow\Controller;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Workflow\Controller\WorkflowController;
use Workflow\Service\WorkflowService;
use Zend\Log\Logger;
use Core\Controller\AbstractControllerFactory;

class WorkflowControllerFactory extends AbstractControllerFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $controller = new WorkflowController(
            $container->get(WorkflowService::class),
            $container->get(Logger::class),
            $container->get('config')
        );

        return $this->forceHttps($controller, $container);
    }

}
