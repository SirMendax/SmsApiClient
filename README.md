# SmsApiClient
Laravel package for sms-api (smsc.ru)

## Install
1. composer require sirmendax/sms-api-client
2. Add SmsApiClientServiceProvider and SmsApiClientFacde to config/app.php
3. php artisan config:cache
4. php artisan vendor:publish
5. php artisan ide-helper:generate

## Use
_Create SmsApiClient through public static method getSmsClient()_

`$api_client = SmsApiClient::getSmsClient();`

_Send sms-message_

`$api_client->sendSms('79991119911', 'test message', 1);`
