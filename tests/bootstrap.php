<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 10/28/16
 * Time: 3:45 PM
 */

require __DIR__ . '/../vendor/autoload.php';

class HasToString
{
    public function __toString()
    {
        return 'bar';
    }
}
