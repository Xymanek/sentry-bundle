<?php

declare(strict_types=1);

use Cake\Chronos\Chronos;
use Cake\Chronos\Date;
use Cake\Chronos\MutableDate;
use Cake\Chronos\MutableDateTime;

require __DIR__ . '/../vendor/autoload.php';

Chronos::setTestNow(Chronos::now());
MutableDateTime::setTestNow(Chronos::now());

Date::setTestNow(Chronos::now());
MutableDate::setTestNow(Chronos::now());