<?php

namespace App\Http\Controllers;

use RequestLog;

abstract class Controller
{
    public function __construct()
    {
        RequestLog::log();
    }
}
