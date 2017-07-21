<?php
namespace Boxspaced\CmsWorkflowModule\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Boxspaced\CmsWorkflowModule\Service;
use Zend\Log\Logger;
use Boxspaced\CmsWorkflowModule\Form;
use Zend\Paginator;
use Zend\EventManager\EventManagerInterface;

class WorkflowController extends AbstractActionController
{

    /**
     * @var Service\WorkflowService
     */
    protected $workflowService;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var ViewModel
     */
    protected $view;

    /**
     * @param Service\WorkflowService $workflowService
     * @param Logger $logger
     * @param array $config
     */
    public function __construct(
        Service\WorkflowService $workflowService,
        Logger $logger,
        array $config
    )
    {
        $this->workflowService = $workflowService;
        $this->logger = $logger;
        $this->config = $config;

        $this->view = new ViewModel();
    }

    /**
     * @param EventManagerInterface $events
     * @return void
     */
    public function setEventManager(EventManagerInterface $events)
    {
        parent::setEventManager($events);
        $controller = $this;
        $events->attach('dispatch', function ($e) use ($controller) {
            $controller->layout('layout/admin');
        }, 100);
    }

    /**
     * @return void
     */
    public function indexAction()
    {
        return $this->view;
    }

    /**
     * @return void
     */
    public function authoringAction()
    {
        $adminNavigation = $this->adminNavigationWidget();
        if (null !== $adminNavigation) {
            $this->layout()->addChild($adminNavigation, 'adminNavigation');
        }

        $adapter = new Paginator\Adapter\Callback(
            function ($offset, $itemCountPerPage) {
                return $this->workflowService->getContentInAuthoring($offset, $itemCountPerPage);
            },
            function () {
                return $this->workflowService->countContentInAuthoring();
            }
        );

        $paginator = new Paginator\Paginator($adapter);
        $paginator->setCurrentPageNumber($this->params()->fromQuery('page', 1));
        $paginator->setItemCountPerPage($this->config['core']['admin_show_per_page']);
        $this->view->paginator = $paginator;

        $authoringItems = $this->createPartialValues($paginator);
        $this->view->authoringItems = $authoringItems;

        return $this->view;
    }

    /**
     * @return void
     */
    public function authoringDeleteAction()
    {
        $this->layout('layout/dialog');
        $this->view->setTemplate('boxspaced/cms-workflow-module/workflow/confirm.phtml');
        return $this->handleDelete('authoring');
    }

    /**
     * @return void
     */
    public function publishingDeleteAction()
    {
        $this->layout('layout/dialog');
        $this->view->setTemplate('boxspaced/cms-workflow-module/workflow/confirm.phtml');
        return $this->handleDelete('publishing');
    }

    /**
     * @param string $stage
     * @return void
     */
    protected function handleDelete($stage)
    {
        $form = new Form\ConfirmForm();
        $form->get('moduleName')->setValue($this->params()->fromQuery('moduleName'));
        $form->get('id')->setValue($this->params()->fromQuery('id'));
        $form->get('confirm')->setValue('Confirm delete');

        $this->view->form = $form;

        if (!$this->getRequest()->isPost()) {
            return $this->view;
        }

        $form->setData($this->getRequest()->getPost());

        if (!$form->isValid()) {

            $this->flashMessenger()->addErrorMessage('Validation failed.');
            return $this->view;
        }

        $values = $form->getData();

        $this->workflowService->delete($values['moduleName'], $values['id']);

        $this->flashMessenger()->addSuccessMessage('Delete successful.');

        return $this->redirect()->toRoute('workflow', [
            'action' => $stage,
        ]);
    }

    /**
     * @return void
     */
    public function publishingAction()
    {
        $adminNavigation = $this->adminNavigationWidget();
        if (null !== $adminNavigation) {
            $this->layout()->addChild($adminNavigation, 'adminNavigation');
        }

        $adapter = new Paginator\Adapter\Callback(
            function ($offset, $itemCountPerPage) {
                return $this->workflowService->getContentInPublishing($offset, $itemCountPerPage);
            },
            function () {
                return $this->workflowService->countContentInPublishing();
            }
        );

        $paginator = new Paginator\Paginator($adapter);
        $paginator->setCurrentPageNumber($this->params()->fromQuery('page', 1));
        $paginator->setItemCountPerPage($this->config['core']['admin_show_per_page']);
        $this->view->paginator = $paginator;

        $publishingItems = $this->createPartialValues($paginator);
        $this->view->publishingItems = $publishingItems;

        return $this->view;
    }

    /**
     * @return void
     */
    public function sendBackAction()
    {
        $moduleName = $this->params()->fromPost('moduleName');
        $id = $this->params()->fromPost('id');
        $notes = $this->params()->fromPost('notes');

        $this->workflowService->sendBackToAuthor($moduleName, $id, $notes);

        $this->flashMessenger()->addSuccessMessage('Content sent back to author successfully.');

        return $this->redirect()->toRoute('workflow', [
            'action' => 'publishing',
        ]);
    }

    /**
     *
     * @param Paginator\Paginator $paginator
     * @return array
     */
    protected function createPartialValues(Paginator\Paginator $paginator)
    {
        $items = [];

        foreach ($paginator as $item) {

            $items[] = array(
                'typeIcon' => $item->typeIcon,
                'typeName' => $item->typeName,
                'name' => $item->name,
                'id' => $item->id,
                'workflowStatus' => $item->workflowStatus,
                'workflowStage' => $item->workflowStage,
                'authoredTime' => $item->authoredTime,
                'authorUsername' => $item->authorUsername,
                'moduleName' => $item->moduleName,
                'actionName' => $item->actionName,
                'notes' => $item->notes,
                'availableTemplates' => $item->availableTemplates,
            );
        }

        return $items;
    }

}
