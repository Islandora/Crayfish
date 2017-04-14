<?php

return [
    'db.options' => [
        'driver' => 'pdo_mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'gemini',
        'user' => 'changeme',
        'password' => 'changeme',
    ],

    // Valid log levels:
    //  - DEBUG
    //  - INFO
    //  - NOTICE
    //  - WARNING
    //  - ERROR
    //  - CRITICAL
    //  - ALERT
    //  - EMERGENCY
    //  - NONE
    // if none is used the log file won't be opened.
    'loglevel' => 'NONE',
    'logfile' => '../houdini.log',

    // Toggles JWT security for the service.
    'security enabled' => true,
    // Path to the syn config file for authentication.
    // Example can be found here:
    // https://github.com/Islandora-CLAW/Syn/blob/master/conf/syn-settings.example.xml
    'security config' => '../syn-settings.xml',
];
