<?php

namespace App\Providers;

use App\Models\Folder;
use App\Policies\FolderPolicy;
use App\Repositories\AuthRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Folder::class => FolderPolicy::class,
    ];
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->register();
    }
}
