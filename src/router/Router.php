<?php

use Klein\Klein;
use Klein\Request;
use Klein\Response;
use Klein\ServiceProvider;

class Router implements IRouter
{

    /** @var */
    public $controller;

    /**
     * @param Klein $klein
     */
    public function create (Klein $klein)
    {
        $klein->respond(function ($request, $response, $service, $app) use ($klein) {
            $app->register('call', function () use ($request, $response, $service, $app) {
                return new Call($request, $response, $service, $app);
            });

            $app->register('util', function () use ($request, $response, $service, $app) {
                return new Util($request, $response, $service, $app);
            });

            $klein->onHttpError(function ($code) use ($app) {
                if ($code >= 400 && $code < 500) {
                    require ERROR_404_PAGE;
                } else {
                    require ERROR_500_PAGE;
                }
            });
        });
    }
}