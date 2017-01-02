<?php
namespace Workflow;

use Zend\Router\Http\Segment;
use Core\Model\ServiceFactory;

return [
    'router' => [
        'routes' => [
            // LIFO
            'workflow' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/workflow[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9-]*',
                    ],
                    'defaults' => [
                        'controller' => Controller\WorkflowController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            // LIFO
        ],
    ],
    'service_manager' => [
        'factories' => [
            Service\WorkflowService::class => Service\WorkflowServiceFactory::class,
            Model\WorkflowService::class => ServiceFactory::class,
        ]
    ],
    'controllers' => [
        'factories' => [
            Controller\WorkflowController::class => Controller\WorkflowControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
