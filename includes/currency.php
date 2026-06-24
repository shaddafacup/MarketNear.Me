<?php
// includes/currency.php

class Currency {
    private static $currencies = null;
    private static $default_currency = null;
    
    /**
     * Get all active currencies
     */
    public static function getAllCurrencies() {
        if (self::$currencies === null) {
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT * FROM currencies 
                WHERE status = 'active' 
                ORDER BY is_default DESC, name ASC
            ");
            $stmt->execute();
            self::$currencies = $stmt->fetchAll();
        }
        
        return self::$currencies;
    }
    
    /**
     * Get default currency
     */
    public static function getDefaultCurrency() {
        if (self::$default_currency === null) {
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT * FROM currencies WHERE is_default = 1 LIMIT 1");
            $stmt->execute();
            self::$default_currency = $stmt->fetch();
            
            // Fallback to first active currency
            if (!self::$default_currency) {
                $stmt = $conn->prepare("SELECT * FROM currencies WHERE status = 'active' LIMIT 1");
                $stmt->execute();
                self::$default_currency = $stmt->fetch();
            }
        }
        
        return self::$default_currency;
    }
    
    /**
     * Get currency by ID
     */
    public static function getCurrencyById($id) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM currencies WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Get currency by code
     */
    public static function getCurrencyByCode($code) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM currencies WHERE code = :code");
        $stmt->execute([':code' => strtoupper($code)]);
        return $stmt->fetch();
    }
    
    /**
     * Format price according to currency settings
     */
    public static function formatPrice($amount, $currency_id = null) {
        if ($currency_id) {
            $currency = self::getCurrencyById($currency_id);
        } else {
            $currency = self::getDefaultCurrency();
        }
        
        if (!$currency) {
            return number_format($amount, 2);
        }
        
        $formatted_amount = number_format(
            $amount,
            $currency['decimal_places'],
            $currency['decimal_separator'],
            $currency['thousands_separator']
        );
        
        if ($currency['symbol_position'] === 'before') {
            return $currency['symbol'] . ' ' . $formatted_amount;
        } else {
            return $formatted_amount . ' ' . $currency['symbol'];
        }
    }
    
    /**
     * Get user's preferred currency
     */
    public static function getUserCurrency($user_id = null) {
        if (!$user_id && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }
        
        if ($user_id) {
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT c.* FROM currencies c 
                JOIN users u ON u.preferred_currency_id = c.id 
                WHERE u.id = :user_id
            ");
            $stmt->execute([':user_id' => $user_id]);
            $currency = $stmt->fetch();
            
            if ($currency) {
                return $currency;
            }
        }
        
        return self::getDefaultCurrency();
    }
    
    /**
     * Set user's preferred currency
     */
    public static function setUserCurrency($user_id, $currency_id) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            UPDATE users SET preferred_currency_id = :currency_id 
            WHERE id = :user_id
        ");
        
        return $stmt->execute([
            ':currency_id' => $currency_id,
            ':user_id' => $user_id
        ]);
    }
    
    /**
     * Get currency options for select dropdown
     */
    public static function getCurrencyOptions($selected_id = null) {
        $currencies = self::getAllCurrencies();
        $options = '';
        
        foreach ($currencies as $currency) {
            $selected = ($selected_id == $currency['id']) ? 'selected' : '';
            $options .= sprintf(
                '<option value="%d" %s>%s (%s) - %s</option>',
                $currency['id'],
                $selected,
                htmlspecialchars($currency['name']),
                $currency['code'],
                htmlspecialchars($currency['symbol'])
            );
        }
        
        return $options;
    }
}
?>