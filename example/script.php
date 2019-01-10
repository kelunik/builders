<?php

use Example\User;
use function Example\user;

require __DIR__ . '/../vendor/autoload.php';

$user = a(user()->withName('Niklas Keller')->verified());

var_dump($user instanceof User);
var_dump($user);