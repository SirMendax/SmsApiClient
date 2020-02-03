<?php

namespace SirMendax\SmsApiClient;

use SirMendax\SmsApiClient\Interfaces\SmsApiClientInterface;
use Illuminate\Support\Facades\Facade;

/**
 * Class SmsApiFacade
 * @package Arven\SmppApi
 */
class SmsApiClientFacade extends Facade
{
  public static function getFacadeAccessor()
  {
    return SmsApiClientInterface::class;
  }
}
