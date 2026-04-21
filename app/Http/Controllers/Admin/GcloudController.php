<?php

namespace App\Http\Controllers\Admin;

// This controller has been deprecated.
// All GCP admin functionality has been consolidated into VpsController.
// This file is kept to avoid class-not-found errors from composer autoload cache.
// Safe to delete after running: php artisan optimize:clear

use App\Http\Controllers\Controller;

class GcloudController extends Controller
{
    //
}
