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
        $response = NULL;
        try {
//            $response = Bigcommerce::createWebhook([
//                        "scope" => "store/order/created",
//                        "destination" => $appUrl . "orderUpdated",
//                        "is_active" => true
//            ]);
            $response = Bigcommerce::createWebhook([
                        "scope" => "store/order/updated",
                        "destination" => $appUrl . "orderUpdated",
                        "is_active" => true
            ]);
//            $response = Bigcommerce::createWebhook([
//                        "scope" => "store/order/statusUpdated",
//                        "destination" => $appUrl . "orderUpdated",
//                        "is_active" => true
//            ]);
        } catch (Error $error) {
            $response = $error->getCode();
            $response .= ' | ' . $error->getMessage();
        }
        return $response;
    }

    public function listWebHooks() {
        return Bigcommerce::listWebhooks();
    }

    public function getOrderData($orderId) {
        $orderData = array();

        $order = Bigcommerce::getOrder($orderId);
        var_dump($order->status_id);die;
        if ($order && in_array($order->status_id, explode(',', $this->prcConfig['statuses']))) {
            $store = Bigcommerce::getStore();
            $orderData['store'] = $store;

            $orderData['order'] = $this->getObjectField($order);

            $orderProducts = Bigcommerce::getOrderProducts($orderId);
            $fields = array();
            if (is_array($orderProducts)) {
                foreach ($orderProducts as $key => $value) {
                    $product = Bigcommerce::getProduct($value->product_id);
                    $fields[] = $this->getObjectField($product);
                }
            }
            $orderData['orderedProducts'] = $fields;

            $customerId = $orderData['order']->customer_id;
            $customer = Bigcommerce::getCustomer($customerId);
            $orderData['customer'] = $this->getObjectField($customer);

            $shipingAddresses = Bigcommerce::getOrderShippingAddresses($orderId);
            $orderData['shipingAddresses'] = $this->getObjectFields($shipingAddresses);

            $shipments = Bigcommerce::getShipments($orderId);
            $orderData['shipments'] = $this->getObjectFields($shipments);
        }
        return $orderData;
    }

    private function getObjectFields($array) {
        $fields = array();
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $fields[] = $this->getObjectField($value);
            }
        }
        return $fields;
    }

    private function getObjectField($object) {
        $array = (array) $object;
        return $array[' * fields'];
    }

}
