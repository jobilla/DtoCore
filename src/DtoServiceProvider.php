<?php

namespace Jobilla\DtoCore;

use Illuminate\Support\ServiceProvider;

class DtoServiceProvider extends ServiceProvider
{
    /**
     * Boot the DTO Service.
     */
    public function boot()
    {
        $this->app->afterResolving(DtoAbstract::class, function ($resolved) {
            $resolved->populateFromArray($this->app['request']->all());
        });
    }
}