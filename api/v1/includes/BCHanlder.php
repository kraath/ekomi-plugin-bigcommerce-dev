<?php

namespace Ekomi;

use Bigcommerce\Api\Client as Bigcommerce;

/**
 * Calls the BigCommerce APIs
 * 
 * This is the class which contains the queries to eKomi Systems.
 * 
 * @since 1.0.0
 */
class BCHanlder {

    private $storeConfig;
    private $prcConfig;

    function __construct($storeConfig, $prcConfig) {
        $this->storeConfig = $storeConfig;
        $this->prcConfig = $prcConfig;

        Bigcommerce::useJson();
        configureBCApi($storeConfig['storeHash'], $storeConfig['accessToken']);
        Bigcommerce::verifyPeer(false);
    }

    public function getOrderStatusesList() {
        $orderStatuses = Bigcommerce::getOrderStatuses();
        $statuses = array();

        foreach ($orderStatuses as $key => $status) {
            $statuses [$status->id] = $status->name;
        }
        return $statuses;
    }

    public function getVariantIDs($bcProduct) {
        $productId = '';
        if ($bcProduct) {
            foreach ($bcProduct->variants as $key => $variant) {
                $productId .= ',' . "'$variant->id'";
            }
        }
        return $productId;
    }

    public function createWebHooks($appUrl) {
        try {
            Bigcommerce::createWebhook([
                "scope" => "store/order/statusUpdated",
                "destination" => $appUrl . "orderStatusUpdated",
                "is_active" => true
            ]);
        } catch (Error $error) {
            echo $error->getCode();
            echo $error->getMessage();
        }
    }

}