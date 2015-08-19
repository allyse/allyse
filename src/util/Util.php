<?php

class Util extends Call
{

    /** @var */
    public $utils;

    function __construct ()
    {
        $this->utils = $this;
    }

    /**
     * @param string $name
     * @param array  $arguments
     * @return object
     */
    function __call ($name, $arguments)
    {
        $class = ucfirst($name) . 'Util';
        $_class = 'Nette\Utils\\' . ucfirst($name);
        $method = $name;
        $_method = array_slice($arguments, 0, 1)[0];
        $_arguments = array_slice($arguments, 1);

        // $this->utils->var
        if (method_exists($class, $method) === TRUE) {
            return call_user_func_array([(new ReflectionClass($class))->newInstance(), $method], $arguments);
        }

        // $this->utils->arrays('trim', $array)
        elseif (method_exists($class, $_method) === TRUE) {
            return call_user_func_array([(new ReflectionClass($class))->newInstance(), $_method], $_arguments);
        }

        // $this->utils->arrays('get', $arr, $key, $default)
        elseif (method_exists($_class, $_method) === TRUE) {
            return call_user_func_array($_class . '::' . $_method, $_arguments);
        }

        // $this->utils->arrays()->trim($array)
        else {
            return new $class();
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    function __get ($name)
    {
        return $this::__call($name, []);
    }

}