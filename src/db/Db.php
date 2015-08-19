<?php

class Db extends Call
{

    /** @var */
    private $connection;

    /** @var */
    private $connectionId;

    /** @var */
    private $credentials;

    /** @var */
    private $commands = [
        'select' => [
            'fetch',
            'cache'
        ]
    ];

    /**
     * @param array $credentials
     */
    function init ($credentials = [])
    {
        $this->credentials = array_merge([
                                             'driver'   => DB_DRIVER,
                                             'host'     => DB_HOST,
                                             'username' => DB_USERNAME,
                                             'password' => DB_PASSWORD,
                                             'database' => DB_DATABASE
                                         ], $credentials);
    }

    /**
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    function __call ($name, $arguments)
    {
        // Add name to arguments array
        $name = strtolower($name);
        array_unshift($arguments, $name);

        return $this->query($arguments);
    }

    /**
     * Connect to DB, inherit credentials from constructor
     */
    private function connect ()
    {
        $this->connectionId = 'DB_CONNECTION_' . md5(implode(',', $this->credentials));

        if (defined($this->connectionId) === FALSE) {
            $connection = dibi::connect($this->credentials, $this->connectionId);

            // Tracy panel
            if (TRACY === TRUE) {
                $panel = new Dibi\Bridges\Tracy\Panel;
                $panel->register($connection);
            }

            define($this->connectionId, TRUE);
        }

        $this->connection = dibi::getConnection($this->connectionId);
    }

    /**
     * Call SQL query
     *
     * @param array $params
     * @return mixed
     */
    private function query ($params)
    {
        @list($command, $sql, $fetch, $cache) = $params;
        $settings = $this->utils->arrays('get', $this->commands, $command, []);

        // SQL query
        if (is_string($sql) === TRUE) {
            $sql = [trim($sql)];
        }

        // Fetch (used only for select)
        if (in_array('fetch', $settings)) {
            if (is_string($fetch) === TRUE) {
                $fetch = array_map('trim', explode(',', $fetch));
            }
        } else {
            $fetch = NULL;
        }

        // Cache params (used only for select)
        if (in_array('cache', $settings)) {
            list($cacheId, $cacheLifetime) = array_values($this->cacheDefaults($sql, $fetch));

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
        } else {
            $cache = NULL;
        }

        // Get item from cache
        if ($cache !== NULL) {
            $cacheDb = $this->call->cache();
            $cacheObj = $cacheDb->pool('/db/' . $cacheId);
            $result = $cacheDb->get($cacheObj);
        }

        // Check if cache is enabled and item is cached
        if ($cache === NULL || $cacheDb->isCached($cacheObj) === FALSE) {
            $result = ['success' => TRUE, 'count' => 0];
            $extended = DB_EXTENDED && strpos(strtolower(DB_DRIVER), 'mysql') !== FALSE;

            // Lock item in cache during processing data
            if ($cache !== NULL) {
                $cacheDb->lock($cacheObj);
            }

            // Connect do DB
            if ($this->connection === NULL) {
                $this->connect();
            }

            // Extended query
            $sqlToSelect = NULL;
            if ($extended === TRUE && in_array($command, ['delete', 'update', 'insert'])) {
                $sqlToSelect = $this->utils->arrays('get', $this->parseQuery($sql), 'select');
            }

            // Select rows before query
            if ($sqlToSelect !== NULL && in_array($command, ['delete', 'update'])) {
                $rowsBefore = $result['rowsBefore'] = $this->sql($sqlToSelect, ['fetchAll']);
            }

            // Query rows
            $rows = $this->sql($sql, $fetch);
            if ($command === 'select') {
                $result['rows'] = $rows;
            }

            // Query count
            $count = $result['count'] = is_int($rows) ? $rows : count($rows);

            // Success
            $success = $result['success'] = (bool) $count;

            // Select rows after query
            if ($sqlToSelect !== NULL) {
                $rowsAfter = $result['rowsAfter'] = $success === TRUE ? $this->sql($sqlToSelect, ['fetchAll']) : $rowsBefore;
            }

            // Unsuccess
            if ($success === FALSE) {
                unset($result['rowsBefore']);
                unset($result['count']);
                unset($result['rows']);
                unset($result['rowsAfter']);
            }

            // Save to cache
            if ($cache !== NULL) {
                $cacheDb->set($cacheObj, $result, $cacheLifetime);
            }
        }

        return $result;
    }

    /**
     * Do SQL query
     *
     * @param array|string $sql
     * @param array|string $fetch
     * @return mixed
     */
    private function sql ($sql, $fetch = NULL)
    {
        if ($this->connection === NULL) {
            $this->connect();
        }

        if (is_array($sql) === FALSE) {
            $sql = [$sql];
        }

        $result = $this->connection->query($sql);

        if (is_string($fetch) === TRUE) {
            $fetch = array_map('trim', explode(',', $fetch));
        }

        if (is_array($fetch) === TRUE && count($fetch) > 0) {
            $result = call_user_func_array([$result, $fetch[0]], array_slice($fetch, 1));
        }

        return $result;
    }

    /**
     * Parse sql query
     *
     * @param array|string $sql
     * @return array
     */
    private function parseQuery ($sql)
    {
        $result = [];

        // SQL to string
        if (is_array($sql) === TRUE) {
            $sql = implode(' ', $sql);
        }
        $sql = trim($sql);

        // Parsed query
        try {
            $parsed = (array) new \PHPSQL\Parser($sql);
            $parsed = $parsed['parsed'];
        } catch (Exception $e) {
            $parsed = NULL;
        }
        $result['parsed'] = $parsed;

        // SQL command => parsed first word
        try {
            $command = key($parsed);
        } catch (Exception $e) {
            $command = NULL;
        }
        $result['command'] = strtolower($command);

        // Select query
        try {
            $select = [];
            $select[] = 'SELECT * FROM';
            // Table
            $tableRowKey = array_search('table', array_column($parsed[$command], 'expr_type'));
            if (is_int($tableRowKey)) {
                $select[] = $parsed[$command][$tableRowKey]['table'];
            } else {
                $tableRowKey = array_search('table', array_column($parsed['FROM'], 'expr_type'));

                if (is_int($tableRowKey)) {
                    $select[] = $parsed['FROM'][$tableRowKey]['table'];
                }
            }
            // Conditions
            if (array_key_exists('1', $select)) {
                if (array_key_exists('WHERE', $parsed)) {
                    $select[] = 'WHERE ' . implode(' ', array_map(function ($condition) {
                            return $condition['base_expr'];
                        }, $parsed['WHERE']));
                }
            }
            // Merge query
            $select = implode(' ', $select);
        } catch (Exception $e) {
            $select = NULL;
        }
        $result['select'] = $select;

        return $result;
    }

    /**
     * Get cache default params
     *
     * @param array|string $sql
     * @param array|string $fetch
     * @return array
     */
    protected function cacheDefaults ($sql, $fetch = NULL)
    {
        // SQL to string
        if (is_array($sql) === TRUE) {
            $sql = implode('', $sql);
        }

        // Fetch to string
        if (is_array($fetch) === TRUE) {
            $fetch = implode('', $fetch);
        } elseif ($fetch === NULL) {
            $fetch = '';
        }

        return [
            'id'       => md5($sql . $fetch),
            'lifetime' => CACHE
        ];
    }

}