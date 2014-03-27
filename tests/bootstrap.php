<?php
/**
 * bootstrap.php
 * data-api
 * @author: Matt License, B023339
 * @date:   2014/03
 */

if(!is_file(__DIR__ . '/../vendor/autoload.php')) {
    throw new \Exception("Run 'composer install --dev' in order to generate autoload.php");
}

require(__DIR__ . '/../vendor/autoload.php');
