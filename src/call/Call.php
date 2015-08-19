<?php

class Call
{

    /** @var Klein\App */
    private $app;

    /** @var */
    protected $call;

    /** @var */
    private $data;

    /** @var Klein\Request */
    private $request;

    /** @var Klein\Response */
    private $response;

    /** @var Klein\ServiceProvider */
    private $service;

    /** @var */
    protected $utils;

    /**
     * @param                       $app
     * @param Klein\Request         $request
     * @param Klein\Response        $response
     * @param Klein\ServiceProvider $service
     * @param Klein\App             $app
     * @param mixed                 $args,...
     */
    function __construct ()
    {
        $args = func_get_args();

        // Construct arguments from Klein
        list($request, $response, $service, $app) = array_slice($args, -4, 4);
        $this->app = $app;
        $this->request = $request;
        $this->response = $response;
        $this->service = $service;

        // Shortcuts
        $this->call = $this;
        $this->utils = $this->app->util();

        // Custom 2nd construct (init)
        $_constructArgs = array_slice($args, 0, -4);
        if (method_exists($this, 'init') === TRUE) {
            call_user_func_array([$this, 'init'], $_constructArgs);
        } elseif (count($_constructArgs) > 0) {
            foreach ($_constructArgs as $vars) {
                foreach ($vars as $varName => $value) {
                    $this->$varName = $value;
                }
            }
        }
    }

    /**
     * @param string $name
     * @param array  $arguments
     * @return object
     */
    function __call ($name, $arguments)
    {
        if (method_exists($name, '__construct') === TRUE) {
            array_push($arguments, $this->request, $this->response, $this->service, $this->app);

            return (new ReflectionClass($name))->newInstanceArgs($arguments);
        } else {
            return (new ReflectionClass($name))->newInstanceWithoutConstructor();
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    function __get ($name)
    {
        return $this->data[$name];
    }

    /**
     * @param $varName
     * @param $value
     */
    function __set ($varName, $value)
    {
        $this->data[$varName] = $value;
    }

}