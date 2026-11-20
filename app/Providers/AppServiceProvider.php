<?php

namespace App\Providers;

use App\Livewire\Synthesizers\NumberSynth;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\TransferOrder;
use App\Policies\TransferOrderPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Product;
use App\Observers\ProductObserver;
use Livewire\Livewire;



class AppServiceProvider extends ServiceProvider
{
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


        Gate::policy(TransferOrder::class, TransferOrderPolicy::class);
        Relation::enforceMorphMap([
            'user' => \App\Models\User::class
            //'transfer_order' => \App\Models\TransferOrder::class,
            //'purchase_order' => \App\Models\PurchaseOrder::class,
        ]);
        Product::observe(ProductObserver::class);
        Livewire::propertySynthesizer(NumberSynth::class);

        if ($this->app->environment('local')) {
            // `artisan serve` ignores PHP_CLI_SERVER_WORKERS unless --no-reload is set,
            // and one worker queues all of Filament's asset requests behind each other.
            if (env('PHP_CLI_SERVER_WORKERS') === null) {
                putenv('PHP_CLI_SERVER_WORKERS=4');
            }

            DevCommands::artisan('serve --no-reload', 'server');
        }
    }
}
