<?php

$projectRoot = dirname(__DIR__);
require_once($projectRoot . '/vendor/autoload.php');

// Enable AspectMock. This allows us to stub/double out static methods.
$kernel = \AspectMock\Kernel::getInstance();
$kernel->init([
    'debug' => true,
    // Any class that we want to stub/mock needs to be in included paths
    'includePaths' => [
        $projectRoot . '/vendor/simplesamlphp/simplesamlphp/',
        $projectRoot . '/lib',
    ]
]);
// AspectMock seems to have trouble with SSP's custom class loader. Explicitly load class names like 'ssp_modulename_*'.
$kernel->loadFile($projectRoot . '/lib/Auth/Source/Instagram.php');

// Symlink module into ssp vendor lib so that templates and urls can resolve correctly
$linkPath = $projectRoot . '/vendor/simplesamlphp/simplesamlphp/modules/authinstagram';
if (file_exists($linkPath) === false) {
    print "Linking '$linkPath' to '$projectRoot'\n";
    symlink($projectRoot, $linkPath);
}
