<?php
namespace Finder\Spider\Abstracts;

use Tracking\Traits\MetricManagerTrait;
use Finder\Helps\DebugHelper;

/**
 * Outputs events information to the console.
 * @see TriggerableInterface
 */
abstract class MetricManager
{
    use MetricManagerTrait;

    public function __construct($file)
    {
        // $this->setFile($file);
        // DebugHelper::debug('File Manager'.$this->getFile());
        $this->run();
    }
}
