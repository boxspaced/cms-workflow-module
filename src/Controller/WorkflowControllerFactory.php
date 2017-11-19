<?php
namespace Boxspaced\CmsWorkflowModule\Controller;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Boxspaced\CmsWorkflowModule\Controller\WorkflowController;
use Boxspaced\CmsWorkflowModule\Service\WorkflowService;
use Zend\Log\Logger;

class WorkflowControllerFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new WorkflowController(
            $container->get(WorkflowService::class),
            $container->get(Logger::class),
            $container->get('config')
        );
    }

}
