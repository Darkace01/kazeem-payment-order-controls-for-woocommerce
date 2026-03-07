<?php
/**
 * Payment Gateway Control
 * Manages which payment gateways appear based on currency
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kazeem_Payment_Order_Controls_Payment_Gateway_Control {
    
    private $optionName = 'kpoc_payment_gateway_settings';
    
    public function __construct() {
        add_filter('woocommerce_available_payment_gateways', array($this, 'filterGatewaysByCurrency'), 999);
    }
    
    /**
     * Filter payment gateways based on currency settings
     */
    public function filterGatewaysByCurrency($availableGateways) {
        if (is_admin()) {
            return $availableGateways;
        }
        
        $settings = $this->getSettings();
        $currentCurrency = Kazeem_Payment_Order_Controls_Currency_Control::instance()->get_current_currency();
        
        if (empty($settings['rules']) || !is_array($settings['rules'])) {
            return $availableGateways;
        }
        
        $allowedGateways = array();
        
        // Check which gateways are allowed for current currency
        foreach ($settings['rules'] as $rule) {
            // Skip disabled rules
            if (isset($rule['enabled']) && !$rule['enabled']) {
                continue;
            }
            
            $ruleCurrencies = isset($rule['currencies']) ? $rule['currencies'] : (isset($rule['currency']) ? array($rule['currency']) : array());
            
            if (in_array($currentCurrency, $ruleCurrencies)) {
                if (isset($rule['gateways']) && is_array($rule['gateways'])) {
                    $allowedGateways = array_merge($allowedGateways, $rule['gateways']);
                }
            }
        }
        
        // If no specific rules for this currency, return all gateways
        if (empty($allowedGateways)) {
            return $availableGateways;
        }
        
        // Filter gateways
        foreach ($availableGateways as $gatewayId => $gateway) {
            if (!in_array($gatewayId, $allowedGateways)) {
                unset($availableGateways[$gatewayId]);
            }
        }
        
        return $availableGateways;
    }
    
    /**
     * Get all available payment gateways
     */
    public function getAvailableGateways() {
        $gateways = WC()->payment_gateways->payment_gateways();
        $result = array();
        
        foreach ($gateways as $gateway) {
            $result[$gateway->id] = $gateway->get_title();
        }
        
        return $result;
    }
    
    /**
     * Get active currencies
     */
    public function getActiveCurrencies() {
        $all_currencies = get_woocommerce_currencies();
        $active_currencies_codes = Kazeem_Payment_Order_Controls_Currency_Control::instance()->get_available_currencies();
        
        $active = array();
        foreach ($active_currencies_codes as $code) {
            if (isset($all_currencies[$code])) {
                $active[$code] = $all_currencies[$code];
            }
        }
        
        return $active;
    }
    
    public function getSettings() {
        return get_option($this->optionName, array('rules' => array()));
    }
    
    /**
     * Update settings
     */
    public function updateSettings($settings) {
        return update_option($this->optionName, $settings);
    }
    
    /**
     * Get statistics for dashboard
     */
    public function getStatistics() {
        $settings = $this->getSettings();
        $rulesCount = isset($settings['rules']) ? count($settings['rules']) : 0;
        
        return array(
            'total_rules' => $rulesCount,
            'active_currencies' => count($this->getActiveCurrencies()),
            'available_gateways' => count($this->getAvailableGateways())
        );
    }
}