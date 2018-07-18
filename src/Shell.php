<?php

namespace Slimcake\Console;

use Slimcake\Core\Exception;
use Slimcake\Core\Inflector;
use Slimcake\Core\Logger;

/**
 * Class Shell
 * @package Slimcake\Console
 */
class Shell
{
    /**
     * @param array $args
     * @return array
     */
    protected static function parse(array $args)
    {
        $options = array();
        $arguments = array();

        foreach ($args as $arg) {
            if (strpos($arg, '--') === 0) {
                $key = substr($arg, 2);
                if (strpos($key, '=') === false) {
                    $options[$key] = true;
                    continue;
                }

                list($k, $v) = explode('=', $key, 2);
                $options[$k] = $v;
            } else {
                $arguments[] = $arg;
            }
        }

        $commandName = array_shift($arguments);
        return array($commandName, $arguments, $options);
    }

    /**
     * @return array
     */
    protected static function getCommands()
    {
        $cmd = array();
        $commands = glob(sprintf('%s/src/Commands/*Command.php', __ROOT__));
        foreach ($commands as $command) {
            $cmdName = pathinfo(basename($command), PATHINFO_FILENAME);
            $cmd[] = Inflector::underscore(substr($cmdName, 0, -7));
        }

        sort($cmd);
        return $cmd;
    }

    /**
     * @param string $commandName
     * @param array $options
     * @return Command
     * @throws Exception
     */
    protected static function createCommand($commandName, $options = array())
    {
        $className = Inflector::camelcase(sprintf('%s_command', $commandName));
        $command = sprintf('App\\Commands\\%s', $className);

        if (class_exists($command) === false) {
            throw new Exception(sprintf('Command "%s" not found', $command));
        }

        return new $command($commandName, $options);
    }

    /**
     * @param string $format
     * @param string $heading
     * @param array $data
     */
    protected static function log($format, $heading, $data = array())
    {
        $fmt = empty($heading) ? '%s  ' : '%s: ';
        $heading = sprintf($fmt, str_pad($heading, 10));

        Logger::info($heading . vsprintf($format, $data));
    }

    /**
     * @param array $arguments
     * @throws Exception
     */
    protected static function execute(array $arguments)
    {
        $script = array_shift($arguments);
        list($commandName, $args, $opts) = self::parse($arguments);

        if (empty($commandName)) {
            self::log('%s [command] [arguments...] [options...]', 'Usage', array($script));

            $prefix = 'Commands';
            $commands = self::getCommands();
            foreach ($commands as $cmd) {
                self::log('%s', $prefix, array($cmd));
                $prefix = null;
            }

            return;
        }

        $command = self::createCommand($commandName, $opts);
        if (array_key_exists('help', $opts)) {
            $description = $command->getDescription();
            $options = array_keys($command->getOptions());
            if (in_array('help', $options) === false) {
                $options[] = 'help';
            }

            ksort($options);
            $prefix = 'Options';

            self::log('%s %s [arguments...] [options...]', 'Usage', array($script, $command->getName()));
            if (empty($description) === false) {
                self::log('%s', 'Desc', $description);
            }

            foreach ($options as $option) {
                self::log('--%s', $prefix, array($option));
                $prefix = null;
            }

            return;
        }

        $command->execute($args);
    }

    /**
     * @param array $arguments
     */
    public static function dispatch(array $arguments)
    {
        try {
            if (defined('__ROOT__') === false) {
                throw new Exception('"__ROOT__" constant is not defined');
            }

            self::execute($arguments);
        } catch (Exception $exception) {
            Logger::error($exception->getMessage());
            exit(1);
        }
    }
}
