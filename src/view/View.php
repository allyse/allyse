<?php

use Tracy\Debugger;

class View extends Call
{

    /** @var */
    public $smarty;

    /**
     * Init smarty templates
     */
    function init ()
    {
        $smarty = new Smarty();
        $smarty->setTemplateDir(VIEW_DIR);
        $smarty->setCompileDir(COMPILE_DIR);
        $smarty->setPluginsDir([
                                   BOOTSTRAP_DIR . '/view/assets/Plugins',
                                   APP_DIR . '/view/assets/Plugins'
                               ]);
        $smarty->assign([
                            'basePath' => BASE_PATH,
                            'isDebug'  => DEBUG
                        ]);
        $smarty->autoload_filters = ['output' => ['striphtml']];
        $smarty->debugging = DEBUG;

        $this->smarty = $smarty;
    }

    /**
     * Fetch template
     *
     * @param string           $file
     * @param array            $data  (optional)
     * @param int|string|array $cache (optional)
     * @return string
     */
    public function fetch ($file, $data = [], $cache = NULL)
    {
        // Remap cache
        if (is_int($data) === TRUE) {
            $cache = $data;
            $data = [];
        }

        // Cache params (used only for select)
        list($cacheId, $cacheLifetime) = array_values($this->cacheDefaults());
        if (is_array($cache)) {
            list($cacheId, $cacheLifetime) = array_merge([
                                                             'id'       => $cacheId,
                                                             'lifetime' => $cacheLifetime
                                                         ], $cache);
        } elseif (is_int($cache) || $cache instanceof \DateTime) {
            $cacheLifetime = $cache;
        } elseif (is_string($cache)) {
            $cacheId = $cache;
        }
        $cache = (bool) $cacheLifetime === TRUE ? TRUE : NULL;

        // Get item from cache
        if ($cache !== NULL) {
            $cacheDb = $this->call->cache();
            $cacheObj = $cacheDb->pool('/view/' . $cacheId);
            $template = $cacheDb->get($cacheObj);
        }

        // Check if cache is enabled and item is cached
        if ($cache === NULL || $cacheDb->isCached($cacheObj) === FALSE) {
            // Lock item in cache during processing data
            if ($cache !== NULL) {
                $cacheDb->lock($cacheObj);
            }

            // Assign smarty templates
            if (count($data) !== 0) {
                $this->smarty->assign($data);
            }

            $template = $this->smarty->fetch($file);

            // Save to cache
            if ($cache !== NULL) {
                $cacheDb->set($cacheObj, $template, $cacheLifetime);
            }
        }

        // Tracy panel
        if (TRACY === TRUE && Debugger::$productionMode === FALSE) {
            Debugger::getBar()->addPanel($panel = new SmartyBarPanel($this->smarty));
        }

        return $template;
    }

    /**
     * Display template
     *
     * @param string $template
     * @param array  $data  (optional)
     * @param array  $cache (optional)
     * @return void
     */
    public function display ()
    {
        echo call_user_func_array([$this, 'fetch'], func_get_args());
    }

    /**
     * Get cache default params
     *
     * @param array $cachableRequestParams
     * @return array
     */
    protected function cacheDefaults ($cachableRequestParams = [])
    {
        // Url without params
        $cacheId = explode('?', $_SERVER['REQUEST_URI'], 2);
        if (is_array($cacheId)) {
            $cacheId = reset($cacheId);
        }

        // Params
        if (count($cachableRequestParams) > 0) {
            parse_str($_SERVER['QUERY_STRING'], $requestParams);

            if (count($requestParams) > 0) {
                $cacheId .= '/' . http_build_query(array_intersect_assoc($cachableRequestParams, $requestParams), '', '/');
            }
        }

        return [
            'id'       => $cacheId,
            'lifetime' => CACHE
        ];
    }

}