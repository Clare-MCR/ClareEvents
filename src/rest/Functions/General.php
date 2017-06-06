<?php namespace Rest\Functions;
/**
 * Created by PhpStorm.
 * User: rg12
 * Date: 21/09/2016
 * Time: 12:15
 */

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}