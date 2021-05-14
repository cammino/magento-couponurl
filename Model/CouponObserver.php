<?php
class Cammino_Couponurl_Model_CouponObserver extends Varien_Object
{
    public function applyCoupon(Varien_Event_Observer $observer) {
        $session = Mage::getSingleton('core/session');
        $couponCode = (string)$session->getCustomCouponCode();

        if (!$couponCode or !strlen($couponCode)) {
            return;
        }

        $session = Mage::getSingleton('checkout/session');
        $cart = Mage::getSingleton('checkout/cart')->getQuote();
        $cart->getShippingAddress()->setCollectShippingRates(true);
        $cart->setCouponCode(strlen($couponCode) ? $couponCode : '')->collectTotals()->save();
    }

    public function setCoupon(Varien_Event_Observer $observer) {
        $coupon = Mage::app()->getRequest()->getParam('coupon_code');
        $session = Mage::getSingleton('core/session');
        if (strlen($coupon) > 0) {
            $session->setCustomCouponCode($coupon);
        }      
    }

    public function setProductPriceBasedOnCupon(Varien_Event_Observer $observer) {
        $totalDiscount = 0;
        $product = $observer->getEvent()->getProduct();
        $couponCode = (string)Mage::getSingleton('core/session')->getCustomCouponCode();
        if ($couponCode) {

            $coupon = Mage::getModel('salesrule/rule');
            $couponCollection = $coupon->getCollection()->addFieldToFilter('code', $couponCode);
            foreach($couponCollection as $c){
                $totalDiscount = $c->getDiscountAmount();
            }

            $router = Mage::app()->getRequest()->getRouteName();

            if($router != 'checkout' && $router != 'onestepcheckout') {
                Mage::app()->getCacheInstance()->cleanType('block_html');
                Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => 'block_html'));
                $product->setFinalPrice($product->getFinalPrice() -  ($product->getFinalPrice() * $totalDiscount / 100));
            }
        }
    }
}