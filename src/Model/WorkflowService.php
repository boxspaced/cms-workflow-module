<?php
namespace Boxspaced\CmsWorkflowModule\Model;

use Boxspaced\CmsWorkflowModule\Exception;
use Boxspaced\CmsVersioningModule\Model\VersionableInterface;

class WorkflowService
{

    /**
     * @param WorkflowableInterface $content
     * @return void
     */
    public function moveToAuthoring(WorkflowableInterface $content)
    {
        $content->setWorkflowStage($content::WORKFLOW_STAGE_AUTHORING);
    }

    /**
     * @param WorkflowableInterface $content
     * @return void
     */
    public function sendBackToAuthor(WorkflowableInterface $content)
    {
        if (!in_array($content->getStatus(), array(
            VersionableInterface::STATUS_DRAFT,
            VersionableInterface::STATUS_REVISION,
        ))) {
            throw new Exception\UnexpectedValueException('You can only move a draft or revision');
        }

        if ($content->getWorkflowStage() !== $content::WORKFLOW_STAGE_PUBLISHING) {
            throw new Exception\UnexpectedValueException('Content not in publishing');
        }

        $content->setWorkflowStage($content::WORKFLOW_STAGE_REJECTED);
    }

    /**
     * @param WorkflowableInterface $content
     * @return void
     */
    public function moveToPublishing(WorkflowableInterface $content)
    {
        if (!in_array($content->getStatus(), array(
            VersionableInterface::STATUS_DRAFT,
            VersionableInterface::STATUS_REVISION,
        ))) {
            throw new Exception\UnexpectedValueException('You can only move a draft or revision');
        }

        if (!in_array($content->getWorkflowStage(), array(
            $content::WORKFLOW_STAGE_AUTHORING,
            $content::WORKFLOW_STAGE_REJECTED,
        ))) {
            throw new Exception\UnexpectedValueException('Content not in authoring');
        }

        $content->setWorkflowStage($content::WORKFLOW_STAGE_PUBLISHING);
    }

    /**
     * @param WorkflowableInterface $content
     * @return void
     */
    public function removeFromWorkflow(WorkflowableInterface $content)
    {
        $content->setWorkflowStage(null);
    }

}
