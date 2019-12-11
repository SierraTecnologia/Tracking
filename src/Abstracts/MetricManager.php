<?php
namespace Tracking\Abstracts;

use Tracking\Traits\MetricManagerTrait;

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
        $this->run();
    }
}
