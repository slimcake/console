<?php

namespace Slimcake\Console;

/**
 * Class Command
 * @package Slimcake\Console
 */
abstract class Command
{
    /** @var string $name */
    protected $name;

    /** @var string $description */
    protected $description;

    /** @var array $options */
    protected $options = array();

    /**
     * Command constructor.
     * @param string $name
     * @param array $options
     */
    public function __construct($name, array $options)
    {
        $this->name = $name;
        foreach ($options as $k => $v) {
            if (array_key_exists($k, $this->options)) {
                $this->options[$k] = $v;
            }
        }
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    abstract public function execute($args = array());
}
