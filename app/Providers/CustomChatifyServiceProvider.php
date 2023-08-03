<?php

namespace App\Providers;

use App\Repositories\ChatifyCustom;
use Chatify\ChatifyServiceProvider;

class CustomChatifyServiceProvider extends ChatifyServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        app()->bind('ChatifyMessenger', function () {
            return new ChatifyCustom;
        });
    }


}
