<?php
namespace Pentagonal\Modular\Test\ModuleExampleDirectory;

use Pentagonal\Modular\Module;

/**
 * Class ModuleValidContainOverride
 * @package Pentagonal\Modular\Test\ModuleExampleDirectory
 */
class ModuleValid extends Module
{
    /**
     * @inheritDoc
     */
    protected function initialize($args = null)
    {
        $this->description = 'Module description';
        $this->name =  'Module Name';
        $this->finalGetConstructorParser();
        $this->finalGetConstructorArguments();
    }

    /**
     * @inheritDoc
     */
    public function getInfo(): array
    {
        return [];
    }
}
