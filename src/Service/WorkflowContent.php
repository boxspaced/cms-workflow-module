<?php
namespace Boxspaced\CmsWorkflowModule\Service;

use DateTime;

class WorkflowContent
{

    /**
     *
     * @var string
     */
    public $typeIcon;

    /**
     *
     * @var string
     */
    public $typeName;

    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var int
     */
    public $id;

    /**
     *
     * @var string
     */
    public $workflowStatus;

    /**
     *
     * @var string
     */
    public $workflowStage;

    /**
     *
     * @var DateTime
     */
    public $authoredTime;

    /**
     *
     * @var string
     */
    public $authorUsername;

    /**
     *
     * @var string
     */
    public $moduleName;

    /**
     *
     * @var string
     */
    public $actionName;

    /**
     *
     * @var WorkflowContentTemplate[]
     */
    public $availableTemplates = [];

    /**
     *
     * @var WorkflowNote[]
     */
    public $notes = [];

}
