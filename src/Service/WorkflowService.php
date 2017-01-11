<?php
namespace Boxspaced\CmsWorkflowModule\Service;

use DateTime;
use Boxspaced\CmsWorkflowModule\Model;
use Boxspaced\EntityManager\EntityManager;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Sql;
use Zend\Log\Logger;
use Boxspaced\CmsWorkflowModule\Exception;
use Boxspaced\CmsAccountModule\Model\UserRepository;
use Boxspaced\CmsCoreModule\Model\EntityFactory;
use Boxspaced\CmsAccountModule\Model\User;
use Boxspaced\CmsItemModule\Model\Item;
use Boxspaced\CmsBlockModule\Model\Block;
use Boxspaced\CmsVersioningModule\Model\VersionableInterface;

class WorkflowService
{

    const WORKFLOW_STATUS_CURRENT = 'Current';
    const WORKFLOW_STATUS_UPDATE = 'Update';
    const WORKFLOW_STATUS_NEW = 'New';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var AuthenticationService
     */
    protected $authService;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var Model\WorkflowService
     */
    protected $workflowService;

    /**
     * @var EntityFactory
     */
    protected $entityFactory;

    /**
     * @param AuthenticationService $authService
     * @param EntityManager $entityManager
     * @param UserRepository $userRepository
     * @param Model\WorkflowService $workflowService
     * @param EntityFactory $entityFactory
     */
    public function __construct(
        Logger $logger,
        AuthenticationService $authService,
        EntityManager $entityManager,
        UserRepository $userRepository,
        Model\WorkflowService $workflowService,
        EntityFactory $entityFactory
    )
    {
        $this->logger = $logger;
        $this->authService = $authService;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->workflowService = $workflowService;
        $this->entityFactory = $entityFactory;

        if ($this->authService->hasIdentity()) {
            $identity = $authService->getIdentity();
            $this->user = $userRepository->getById($identity->id);
        }
    }

    /**
     * @param string $module
     * @param int $id
     * @return string
     */
    public function getStatus($module, $id)
    {
        $content = $this->getContent($module, $id);
        $workflowableContent = $this->getWorkflowableContent($content);

        if ($workflowableContent->getStatus() === VersionableInterface::STATUS_PUBLISHED) {
            return static::WORKFLOW_STATUS_CURRENT;
        }

        if ($workflowableContent->getStatus() === VersionableInterface::STATUS_REVISION) {
            return static::WORKFLOW_STATUS_UPDATE;
        }

        return static::WORKFLOW_STATUS_NEW;
    }

    /**
     * @todo delete via Item or Block service, like you would edit
     * @param string $module
     * @param int $id
     * @return void
     */
    public function delete($module, $id)
    {
        $content = $this->getContent($module, $id);

        if (!in_array($content->getStatus(), array(
            VersionableInterface::STATUS_DRAFT,
            VersionableInterface::STATUS_REVISION,
        ))) {
            throw new Exception\UnexpectedValueException('You can only delete a draft or revision');
        }

        if (!$content->getWorkflowStage()) {
            throw new Exception\UnexpectedValueException('Content is not in workflow');
        }

        if (
            $content->getAuthor() !== $this->user
            && $content->getWorkflowStage() !== Model\WorkflowableInterface::WORKFLOW_STAGE_PUBLISHING
        ) {
            throw new Exception\RuntimeException('Content is not authored by user');
        }

        if (
            !($content instanceof Block)
            && $content->getStatus() === VersionableInterface::STATUS_DRAFT
        ) {

            if ($content->getRoute()) {
                $this->entityManager->delete($content->getRoute());
                $content->setRoute(null);
            }

            if ($content->getProvisionalLocation()) {
                $this->entityManager->delete($content->getProvisionalLocation());
                $content->setProvisionalLocation(null);
            }
        }

        $this->entityManager->delete($content);

        $this->entityManager->flush();
    }

    /**
     * @param string $module
     * @param int $id
     * @return void
     */
    public function moveToPublishing($module, $id)
    {
        $content = $this->getContent($module, $id);
        $workflowableContent = $this->getWorkflowableContent($content);

        $this->workflowService->moveToPublishing($workflowableContent);

        $this->entityManager->flush();
    }

    /**
     * @param string $module
     * @param int $id
     * @param string $noteText
     * @return void
     */
    public function sendBackToAuthor($module, $id, $noteText)
    {
        $content = $this->getContent($module, $id);
        $workflowableContent = $this->getWorkflowableContent($content);

        $this->workflowService->sendBackToAuthor($workflowableContent);

        if ($noteText) {

            $entityName = sprintf(
                '%s\\Model\\%sNote',
                ucfirst($module),
                ucfirst($module)
            );

            $note = $this->entityFactory->createEntity($entityName);
            $note->setText($noteText);
            $note->setUser($this->user);
            $note->setCreatedTime(new DateTime());

            $content->addNote($note);
        }

        $this->entityManager->flush();
    }

    /**
     * @param string $module
     * @param int $id
     * @return mixed
     */
    protected function getContent($module, $id)
    {
        $type = ucfirst(strtolower($module));

        $entityName = sprintf(
            '%s\\Model\\%s',
            $type,
            $type
        );

        $content = $this->entityManager->find($entityName, $id);

        if (null === $content) {
            throw new Exception\UnexpectedValueException('Unable to find content');
        }

        return $content;
    }

    /**
     * @param mixed $content
     * @return Model\WorkflowableInterface
     */
    protected function getWorkflowableContent($content)
    {
        $parts = explode('\\', get_class($content));
        $type = array_pop($parts);

        $adapter = $type . '\\Model\\Workflowable' . $type;

        return new $adapter($content);
    }

    /**
     * @param int $offset
     * @param int $showPerPage
     * @return WorkflowContent[]
     */
    public function getContentInAuthoring($offset = null, $showPerPage = null)
    {
        $select = $this->createAuthoringSelect();
        $select->order(['authored_time' => 'DESC']);

        if (null !== $offset && null !== $showPerPage) {
            $select->limit($showPerPage)->offset($offset);
        }

        $sql = new Sql\Sql($this->entityManager->getDb());
        $stmt = $sql->prepareStatementForSqlObject($select);

        $contentInAuthoring = [];

        foreach ($stmt->execute()->getResource()->fetchAll() as $row) {

            $routable = true;

            if ('Block' === $row['type']) {
                $type = Block::class;
                $routable = false;
            } else {
                $type = Item::class;
            }

            $content = $this->entityManager->find($type, $row['id']);
            $contentInAuthoring[] = $this->createContent($content, $routable);
        }

        return $contentInAuthoring;
    }

    /**
     * @param mixed $content
     * @return WorkflowContent
     */
    protected function createContent($content, $routable = true)
    {
        $workflowContent = new WorkflowContent();

        if ($routable) {

            if ($content->getVersionOf()) {
                $workflowContent->name = $content->getVersionOf()->getRoute()->getSlug();
            } else {
                $workflowContent->name = $content->getRoute()->getSlug();
            }

        } else {

            if ($content->getVersionOf()) {
                $workflowContent->name = $content->getVersionOf()->getName();
            } else {
                $workflowContent->name = $content->getName();
            }
        }

        if ($routable) {

            if ($content->getVersionOf()) {
                $workflowContent->moduleName = $content->getVersionOf()->getRoute()->getModule()->getRouteController();
                $workflowContent->actionName = $content->getVersionOf()->getRoute()->getModule()->getRouteAction();
            } else {
                $workflowContent->moduleName = $content->getRoute()->getModule()->getRouteController();
                $workflowContent->actionName = $content->getRoute()->getModule()->getRouteAction();
            }

        } else {
            $workflowContent->moduleName = 'block';
        }

        $workflowContent->id = $content->getId();
        $workflowContent->workflowStage = $content->getWorkflowStage();
        $workflowContent->authoredTime = $content->getAuthoredTime();
        $workflowContent->authorUsername = $content->getAuthor()->getUsername();

        $type = $content->getType();

        $workflowContent->typeName = $type->getName();
        $workflowContent->typeIcon = $type->getIcon();

        foreach ($type->getTemplates() as $templateEntity) {

            $template = new WorkflowContentTemplate();
            $template->id = (int) $templateEntity->getId();
            $template->name = $templateEntity->getName();

            $workflowContent->availableTemplates[] = $template;
        }

        if ($content->getStatus() === VersionableInterface::STATUS_REVISION) {
            $workflowContent->workflowStatus = static::WORKFLOW_STATUS_UPDATE;
        } else {
            $workflowContent->workflowStatus = static::WORKFLOW_STATUS_NEW;
        }

        foreach ($content->getNotes() as $noteEntity) {

            $note = new WorkflowNote();
            $note->username = $noteEntity->getUser()->getUsername();
            $note->text = $noteEntity->getText();
            $note->time = $noteEntity->getCreatedTime();

            $workflowContent->notes[] = $note;
        }

        return $workflowContent;
    }

    /**
     * @return int
     */
    public function countContentInAuthoring()
    {
        $select = $this->createAuthoringSelect();

        $select->columns([
            'count' => new Sql\Expression('COUNT(*)'),
        ]);

        $sql = new Sql\Sql($this->entityManager->getDb());
        $stmt = $sql->prepareStatementForSqlObject($select);

        return (int) $stmt->execute()->getResource()->fetchColumn();
    }

    /**
     * @return Sql\Select
     */
    protected function createAuthoringSelect()
    {
        $platform = $this->entityManager->getDb()->getPlatform();

        $selectItems = new Sql\Select();

        $selectItems->columns([
            'type' => new Sql\Literal($platform->quoteValue('Item')),
            'id',
            'authored_time',
        ]);

        $selectItems->from('item');

        $selectItems->where([
            'author_id = ?' => $this->user->getId(),
            'workflow_stage IS NOT NULL',
        ]);

        $selectBlocks = new Sql\Select();

        $selectBlocks->columns([
            'type' => new Sql\Literal($platform->quoteValue('Block')),
            'id',
            'authored_time',
        ]);

        $selectBlocks->from('block');

        $selectBlocks->where([
            'author_id = ?' => $this->user->getId(),
            'workflow_stage IS NOT NULL',
        ]);

        $selectItems->combine($selectBlocks);

        return (new Sql\Select())->from(['all' => $selectItems]);
    }

    /**
     * @param int $offset
     * @param int $showPerPage
     * @return WorkflowContent[]
     */
    public function getContentInPublishing($offset = null, $showPerPage = null)
    {
        $select = $this->createPublishingSelect();
        $select->order(['authored_time' => 'DESC']);

        if (null !== $offset && null !== $showPerPage) {
            $select->limit($showPerPage)->offset($offset);
        }

        $sql = new Sql\Sql($this->entityManager->getDb());
        $stmt = $sql->prepareStatementForSqlObject($select);

        $contentInPublishing = [];

        foreach ($stmt->execute()->getResource()->fetchAll() as $row) {

            $routable = true;

            if ('Block' === $row['type']) {
                $type = Block::class;
                $routable = false;
            } else {
                $type = Item::class;
            }

            $content = $this->entityManager->find($type, $row['id']);
            $contentInPublishing[] = $this->createContent($content, $routable);
        }

        return $contentInPublishing;
    }

    /**
     * @return int
     */
    public function countContentInPublishing()
    {
        $select = $this->createPublishingSelect();

        $select->columns([
            'count' => new Sql\Expression('COUNT(*)'),
        ]);

        $sql = new Sql\Sql($this->entityManager->getDb());
        $stmt = $sql->prepareStatementForSqlObject($select);

        return (int) $stmt->execute()->getResource()->fetchColumn();
    }

    /**
     * @return Sql\Select
     */
    protected function createPublishingSelect()
    {
        $platform = $this->entityManager->getDb()->getPlatform();

        $selectItems = new Sql\Select();

        $selectItems->columns([
            'type' => new Sql\Literal($platform->quoteValue('Item')),
            'id',
            'authored_time',
        ]);

        $selectItems->from('item');

        $selectItems->where([
            'workflow_stage = ?' => Model\WorkflowableInterface::WORKFLOW_STAGE_PUBLISHING,
        ]);

        $selectBlocks = new Sql\Select();

        $selectBlocks->columns([
            'type' => new Sql\Literal($platform->quoteValue('Block')),
            'id',
            'authored_time',
        ]);

        $selectBlocks->from('block');

        $selectBlocks->where([
            'workflow_stage = ?' => Model\WorkflowableInterface::WORKFLOW_STAGE_PUBLISHING,
        ]);

        $selectItems->combine($selectBlocks);

        return (new Sql\Select())->from(['all' => $selectItems]);
    }

}
