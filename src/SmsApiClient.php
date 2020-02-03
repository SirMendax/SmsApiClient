<?php


namespace SirMendax\SmsApiClient;

use SirMendax\SmsApiClient\Interfaces\SmsApiClientInterface;
use Exception;

/**
 * Class SmsApiClient
 * @package SirMendax\SmppApiClient
 */
class SmsApiClient implements SmsApiClientInterface
{
  /**
   * @return SmsSmsc
   * @throws Exception
   */
  public function getSmsClient()
  {
    $config = config('sms-api-client');
    return new SmsSmsc($config);
  }
}
