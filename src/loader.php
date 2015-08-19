<?php

spl_autoload_register(function ($class) {
    // Dirs to scan
    $dirs = [BOOTSTRAP_DIR, APP_DIR, BOOTSTRAP_DIR . '/tracy'];

    // Camel case split and reverse array
    $splitted = array_values(array_filter(preg_split('/(?=[A-Z])/', trim($class))));

    // UtilsModel
    // => ['Model', 'Utils']
    // => Looking for Utils/UtilsModel.php then UtilsModel/UtilsModel.php in $dirs...
    for ($i = 0; $i <= count($splitted); $i++) {
        if ($i === 0) {
            $file = $class . '.php';
        } else {
            $file = join('', array_slice($splitted, -($i))) . '/' . $class . '.php';
        }

        foreach ($dirs as $dir) {
            if (file_exists($dir . '/' . $file)) {
                include $dir . '/' . $file;

                break;
            }
        }
    }
});