<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class ReusableFacades extends Facade {
   protected static function getFacadeAccessor() { return 'Reusable'; }
}
