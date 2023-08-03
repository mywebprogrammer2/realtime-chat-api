<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Chatify\Traits\UUID;
use Illuminate\Support\Facades\DB;

class ChMessage extends Model
{
    use UUID;


    public function ScopeUnseen($q){
        return $q->where('seen',0)->select(DB::raw('count(seen) as unseen'));
    }
}
