<?php

namespace Jungi\Orm\Mapping\Loader;

use Jungi\Orm\Mapping\ClassDefinition;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
interface LoaderInterface
{
    public function load(string $className): ClassDefinition;
}
