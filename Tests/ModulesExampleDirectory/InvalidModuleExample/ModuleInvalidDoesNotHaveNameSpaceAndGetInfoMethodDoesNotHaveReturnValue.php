<?php
namespace {

    use Pentagonal\Modular\Module;

    /**
     * Class ModuleInvalidDoesNotHaveNameSpaceAndGetInfoMethodDoesNotHaveReturnValue
     */
    class ModuleInvalidDoesNotHaveNameSpaceAndGetInfoMethodDoesNotHaveReturnValue extends Module
    {
        public function getInfo(): array
        {
            // no return value
        }

        public function initialize()
        {
            // TODO: Implement initialize() method.
        }
    }
}
