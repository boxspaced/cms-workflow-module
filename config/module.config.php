<?php
namespace Workflow;

use Zend\Router\Http\Segment;
use Core\Model\ServiceFactory;
use Zend\Permissions\Acl\Acl;

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
    'acl' => [
        'resources' => [
            [
                'id' => Controller\WorkflowController::class,
            ],
        ],
        'rules' => [
            [
                'type' => Acl::TYPE_ALLOW,
                'roles' => 'author',
                'resources' => Controller\WorkflowController::class,
                'privileges' => [
                    'authoring',
                    'authoring-delete',
                ],
            ],
            [
                'type' => Acl::TYPE_ALLOW,
                'roles' => 'publisher',
                'resources' => Controller\WorkflowController::class,
                'privileges' => [
                    'publishing',
                    'send-back',
                    'publishing-delete',
                ],
            ],
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
