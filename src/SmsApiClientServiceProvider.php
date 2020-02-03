<?php
namespace SirMendax\SmsApiClient;

use SirMendax\SmsApiClient\Interfaces\SmsApiClientInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Class ServiceProvider
 * @package SirMendax\SmsApiClient
 */
class SmsApiClientServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap any application services.
   * @return void
   */
  public function boot() :void
  {
      $this->publishes([
          __DIR__.'/../config/sms-api-client.php' =>  config_path('sms-api-client.php'),
      ], 'config');
  }

  /**
   * Register any application services.
   * @return void
   */
  public function register() :void
  {
    $this->mergeConfigFrom(
      __DIR__.'/../config/sms-api-client.php', 'sms-api-client'
    );
    $this->app->singleton(SmsApiClientInterface::class, function () {
      return new SmsApiClient();
    });
  }
}
