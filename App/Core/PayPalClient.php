<?php

namespace App\Core;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;

/**
 * Extending PayPalHttpClient to allow loading local CA certificate for curl when testing on a development machine
 */
class PayPalHttpClientEx extends PayPalHttpClient{
    /**
     * @return string The path to a custom CA certificate
     */
    protected function getCACertFilePath(){
        return LOCAL_CA_PATH;
    }
}

class PayPalClient
{
    /**
     * @return PayPalHttpClientEx PayPal HTTP client instance with environment that has access
     * credentials context. Use this instance to invoke PayPal APIs, provided the
     * credentials have access.
     */
    public static function client()
    {
        return new PayPalHttpClientEx(self::environment());
    }

    /**
     * Set up and return PayPal PHP SDK environment with PayPal access credentials.
     * This sample uses SandboxEnvironment. In production, use ProductionEnvironment.
     * @return SandboxEnvironment|ProductionEnvironment
     */
    public static function environment()
    {
        // Get PayPal client ID/Secret from defined constant and fallback to defined environment
        $clientId = CLIENT_ID?: getenv("CLIENT_ID");
        $clientSecret = CLIENT_SECRET?: getenv("CLIENT_SECRET");

        if(PAYPAL_SANDBOX_MODE){
            return new SandboxEnvironment($clientId, $clientSecret);
        }else{
            return new ProductionEnvironment($clientId, $clientSecret);
        }
    }
}