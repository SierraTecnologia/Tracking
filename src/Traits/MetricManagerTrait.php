<?php
namespace Tracking\Traits;

use Tracking\Abstracts\MetricManager;

use Finder\Models\Digital\Midia\File;
use Finder\Models\Digital\Internet\ComputerFile;

/**
 * Outputs events information to the console.
 * @see TriggerableInterface
 */
trait MetricManagerTrait
{

    public $metrics = [];

    /**
     * Lógica
     */
    protected function run()
    {
        return true;
    }

    /**
     * Type pode ser: Extensions, Identificadores, Groups
     */
    public function registerMetricCount($type, $group, $sum = 1)
    {
        if (!isset($this->metrics[$type])) {
            $this->metrics[$type] = [];
        }

        if (!isset($this->metrics[$type][$group])) {
            $this->metrics[$type][$group] = 0;
        }

        return $this->metrics[$type][$group] += $sum;
    }
    public function mergeWith($mergeWith)
    {
        $this->metrics = $this->arrayMergeRecursive($this->metrics, $mergeWith);
    }

    public function saveAndReturnArray()
    {
        $this->save();
        return $this->returnMetrics();
    }

    public function returnMetrics()
    {
        return $this->metrics;
    }

    protected function save()
    {
        return $this->metrics;
    }


    protected function arrayMergeRecursive($array1, $array2)
    {
        if (empty($array2)) {
            return $array1;
        }
        if (empty($array1)) {
            return $array2;
        }

        foreach ($array2 as $indice => $valor) {
            if (!isset($array1[$indice])) {
                $array1[$indice] = $array2[$indice];
            } else {
                foreach ($array2[$indice] as $indice2 => $valor2) {
                    if (!isset($array1[$indice][$indice2])) {
                        $array1[$indice][$indice2] = $array2[$indice][$indice2];
                    } else{
                        $array1[$indice][$indice2] += $array2[$indice][$indice2];
                    }
                }
            }
        }

        return $array1;
    }
}
