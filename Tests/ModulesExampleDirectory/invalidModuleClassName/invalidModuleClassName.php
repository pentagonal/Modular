<?php
namespace Pentagonal\Modular\Test\ModuleExampleDirectory;


/**
 * Class invalidModuleClassName
 * @package Pentagonal\Modular\Test\ModuleExampleDirectory
 */
class invalidModuleClassName extends \stdClass
{
    /**
     * @var array
     */
    protected $recordArgs = [];

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

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setArg(string $name, $value)
    {
        $this->recordArgs[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getArg(string $name)
    {
        return isset($this->recordArgs[$name])
            ? $this->recordArgs[$name]
            : null;
    }
}
