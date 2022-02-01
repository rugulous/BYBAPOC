<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../_lib/modules.php');

$module = get_module('membership-list');
if($module == NULL){
    echo "Oh no - no module found :(";
} else {
    var_dump($module->getMembershipList(2022));
}
