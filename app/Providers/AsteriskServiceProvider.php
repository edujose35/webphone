<?php

namespace App\Providers;

use App\Jobs\AsteriskAdminJob;
use Illuminate\Support\Facades\Queue;
use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class AsteriskServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Queue::push(new AsteriskAdminJob());
    }
}
