<?php
namespace Workflow;

class Module
{

    const VERSION = '1.0.0';

    /**
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

}
