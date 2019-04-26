<?php

namespace Islandora\Crayfish\Commons\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Yaml\Yaml;
use InvalidArgumentException;

class YamlConfigServiceProvider implements ServiceProviderInterface
{
    protected $config;
    protected $basename;

    public function __construct($config, $basename = 'crayfish')
    {
        $this->config = $config;
        $this->basename = $basename;
    }

    protected function getName($name, $key)
    {
        return "$name.$key";
    }

    protected function isAssocArray($array)
    {
        if (!is_array($array)) {
            return false;
        }

        if (array() === $array) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    protected function parse($container, $array, $name)
    {
        foreach ($array as $key => $value) {
            if ($this->isAssocArray($value)) {
                $this->parse($container, $value, $this->getName($name, $key));
            } else {
                $container[$this->getName($name, $key)] = $value;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        if (!file_exists($this->config)) {
            throw new InvalidArgumentException("File does not exist!");
        }
        $data = Yaml::parse(file_get_contents($this->config));
        $this->parse($container, $data, $this->basename);
    }
}
