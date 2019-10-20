<?php

namespace Jungi\Orm\Mapping\Loader;

use Jungi\Orm\Mapping\Builder\FluentMappingBuilderContext;
use Jungi\Orm\Mapping\ClassDefinition;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class PhpFileLoader implements LoaderInterface
{
    private $dirs;
    private $fallbackDir;

    public function __construct(array $dirs, string $fallbackDir = null)
    {
        foreach ($dirs as $namespacePrefix => $dir) {
            if (!is_dir($dir)) {
                throw new \InvalidArgumentException(sprintf('Invalid directory "%s".', $dir));
            }
            if ('\\' !== $namespacePrefix[strlen($dir) - 1]) {
                throw new \InvalidArgumentException(sprintf('Invalid namespace prefix "%s", it should end with slash "\".', $namespacePrefix));
            }
        }
        if ($fallbackDir && !is_dir($fallbackDir)) {
            throw new \InvalidArgumentException(sprintf('Invalid directory "%s".', $fallbackDir));
        }

        $this->dirs = $dirs;
        $this->fallbackDir = $fallbackDir;
    }

    public function load(string $className): ClassDefinition
    {
        $matched = false;
        $dir = null;
        $namespacePrefix = '';

        foreach ($this->dirs as $namespacePrefix => $dir) {
            if (0 === strpos($className, $namespacePrefix)) {
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            if (!$this->fallbackDir) {
                throw new \RuntimeException(sprintf('Cannot find mapping for the class "%s".', $className));
            }

            $dir = $this->fallbackDir;
            $namespacePrefix = '';
        }

        $file = $dir.'/'.str_replace('\\', '.', substr($className, strlen($namespacePrefix))).'.php';

        return $this->loadFile($file);
    }

    private function loadFile(string $file): ClassDefinition
    {
        if (!is_file($file)) {
            throw new \RuntimeException(sprintf('Could not find mapping file "%s".', $file));
        }

        $context = new FluentMappingBuilderContext(); // required
        $classDefinition = include $file;

        if (!$classDefinition instanceof ClassDefinition) {
            throw new \UnexpectedValueException(sprintf(
                'Expected to get "%s" from the file "%s".',
                ClassDefinition::class,
                $file
            ));
        }

        return $classDefinition;
    }
}
