<?php

return [
    'executable' => 'convert',
    'fedora base url' => 'http://localhost:8080/fcrepo/rest',
    'valid formats' => ['image/jpeg', 'image/png', 'image/tiff', 'image/jp2'],
    'default format' => 'image/jpeg',

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
