<?php
/**
 * Order Control Manager
 * Handles enabling/disabling WooCommerce orders and timeframe settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kazeem_Payment_Order_Controls_Order_Control {
    
    private $optionName = 'kpoc_order_control_settings';
    
    public function __construct() {
        add_action('woocommerce_after_checkout_validation', array($this, 'validateOrderSubmission'), 10, 2);
        add_action('woocommerce_checkout_process', array($this, 'blockCheckoutIfDisabled'));
        
        // Hide add to cart buttons when orders are disabled
        add_filter('woocommerce_is_purchasable', array($this, 'disablePurchasable'), 10, 2);
        add_action('woocommerce_single_product_summary', array($this, 'showDisabledMessage'), 31);
        
        // Handle redirects
        add_action('template_redirect', array($this, 'handleCheckoutRedirect'));
    }
    
    /**
     * Check if a specific product can be ordered
     */
    public function canOrderProduct($productId) {
        $settings = $this->getSettings();
        
        // Check if orders are globally enabled
        if (!isset($settings['enable_orders']) || !$settings['enable_orders']) {
            return false;
        }
        
        // Check restriction type
        $restrictionType = isset($settings['restriction_type']) ? $settings['restriction_type'] : 'all';
        $isAffected = false;
        
        if ($restrictionType === 'all') {
            $isAffected = true;
        } elseif ($restrictionType === 'categories') {
            $isAffected = $this->isProductInRestrictedCategories($productId, $settings);
        } elseif ($restrictionType === 'products') {
            $restrictedProducts = isset($settings['restricted_products']) ? $settings['restricted_products'] : array();
            $isAffected = in_array($productId, $restrictedProducts);
        }
        
        return $isAffected ? $this->isWithinAllowedPeriod($settings) : true;
    }
    
    /**
     * Check if product is in restricted categories
     */
    private function isProductInRestrictedCategories($productId, $settings) {
        $restrictedCategories = isset($settings['restricted_categories']) ? $settings['restricted_categories'] : array();
        if (empty($restrictedCategories)) {
            return false;
        }
        
        $productCategories = wp_get_post_terms($productId, 'product_cat', array('fields' => 'ids'));
        return !empty(array_intersect($productCategories, $restrictedCategories));
    }
    
    /**
     * Check if current time/date is within allowed period
     */
    private function isWithinAllowedPeriod($settings) {
        $allowed = true;
        
        // Check date range if enabled
        if (isset($settings['enable_date_range']) && $settings['enable_date_range']) {
            $currentDatetime = current_time('timestamp');
            
            if (!empty($settings['start_datetime'])) {
                $startDatetime = strtotime($settings['start_datetime']);
                if ($currentDatetime < $startDatetime) {
                    $allowed = false;
                }
            }
            
            if ($allowed && !empty($settings['end_datetime'])) {
                $endDatetime = strtotime($settings['end_datetime']);
                if ($currentDatetime > $endDatetime) {
                    $allowed = false;
                }
            }
        }
        
        // Check time range if enabled
        if ($allowed && isset($settings['enable_timeframe']) && $settings['enable_timeframe']) {
            $allowed = $this->isWithinTimeframe($settings);
        }
        
        return $allowed;
    }
    
    /**
     * Check if orders are currently enabled (backward compatibility)
     */
    public function areOrdersEnabled() {
        $settings = $this->getSettings();
        
        if (!isset($settings['enable_orders']) || !$settings['enable_orders']) {
            return false;
        }
        
        return $this->isWithinAllowedPeriod($settings);
    }
    
    /**
     * Check if current time is within allowed timeframe
     */
    private function isWithinTimeframe($settings) {
        if (empty($settings['start_time']) || empty($settings['end_time'])) {
            return true;
        }
        
        $currentTime = current_time('H:i');
        $start = $settings['start_time'];
        $end = $settings['end_time'];
        
        // Handle overnight timeframes (e.g., 22:00 to 06:00)
        if ($start <= $end) {
            return $currentTime >= $start && $currentTime <= $end;
        } else {
            return $currentTime >= $start || $currentTime <= $end;
        }
    }
    
    /**
     * Block checkout if orders are disabled
     */
    public function blockCheckoutIfDisabled() {
        if (!$this->areOrdersEnabled()) {
            $settings = $this->getSettings();
            $message = isset($settings['disabled_message']) ? $settings['disabled_message'] :
                       __('Orders are currently disabled. Please try again later.', 'kazeem-payment-order-controls-for-woocommerce');
            
            wc_add_notice(esc_html($message), 'error');
        }
    }
    
    /**
     * Validate order submission
     */
    public function validateOrderSubmission($data, $errors) {
        // Parameter $data is part of the hook signature but unused here.
        unset($data);
        
        if (!$this->areOrdersEnabled()) {
            $settings = $this->getSettings();
            $message = isset($settings['disabled_message']) ? $settings['disabled_message'] :
                       __('Orders are currently disabled. Please try again later.', 'kazeem-payment-order-controls-for-woocommerce');
            
            $errors->add('orders_disabled', esc_html($message));
        }
    }
    
    /**
     * Disable purchasable status for products when orders are disabled
     */
    public function disablePurchasable($isPurchasable, $product) {
        if (!$this->canOrderProduct($product->get_id())) {
            return false;
        }
        return $isPurchasable;
    }
    
    /**
     * Show custom message on product page when orders are disabled
     */
    public function showDisabledMessage() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        if (!$this->canOrderProduct($product->get_id())) {
            $settings = $this->getSettings();
            $message = isset($settings['disabled_message']) ? $settings['disabled_message'] :
                       __('Orders are currently disabled. Please try again later.', 'kazeem-payment-order-controls-for-woocommerce');
            
            echo '<div class="woocommerce-info" style="margin: 20px 0;">' . esc_html($message) . '</div>';
        }
    }
    
    /**
     * Handle checkout page redirect when orders are disabled
     */
    public function handleCheckoutRedirect() {
        if (is_checkout() && !$this->areOrdersEnabled()) {
            $settings = $this->getSettings();
            $redirectUrl = isset($settings['redirect_url']) ? $settings['redirect_url'] : home_url();
            
            if (!empty($redirectUrl)) {
                wp_safe_redirect($redirectUrl);
                exit;
            }
        }
    }
    
    /**
     * Get settings
     */
    public function getSettings() {
        return get_option($this->optionName, array(
            'enable_orders' => true,
            'enable_timeframe' => false,
            'enable_date_range' => false,
            'start_time' => '00:00',
            'end_time' => '23:59',
            'start_datetime' => '',
            'end_datetime' => '',
            'restriction_type' => 'all',
            'restricted_categories' => array(),
            'restricted_products' => array(),
            'redirect_url' => '',
            'disabled_message' => __('Orders are currently disabled. Please try again later.', 'kazeem-payment-order-controls-for-woocommerce')
        ));
    }
    
    /**
     * Update settings
     */
    public function updateSettings($settings) {
        return update_option($this->optionName, $settings);
    }
    
    /**
     * Get order statistics
     */
    public function getStatistics() {
        $settings = $this->getSettings();
        
        return array(
            'orders_enabled' => $settings['enable_orders'],
            'timeframe_enabled' => isset($settings['enable_timeframe']) ? $settings['enable_timeframe'] : false,
            'current_status' => $this->areOrdersEnabled() ? 'active' : 'disabled',
            'start_time' => isset($settings['start_time']) ? $settings['start_time'] : '',
            'end_time' => isset($settings['end_time']) ? $settings['end_time'] : ''
        );
    }
}