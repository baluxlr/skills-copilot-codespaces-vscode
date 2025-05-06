<?php
/**
 * DealRoom Utilities Class
 * 
 * Helper functions used throughout the DealRoom plugin.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class DealRoom_Utilities {
    /**
     * Get current user role
     */
    public static function get_user_role() {
        if (!is_user_logged_in()) {
            return 'guest';
        }
        
        $user = wp_get_current_user();
        
        if (in_array('administrator', $user->roles)) {
            return 'admin';
        } elseif (in_array('dealroom_investor', $user->roles)) {
            return 'investor';
        } elseif (in_array('dealroom_entrepreneur', $user->roles)) {
            return 'entrepreneur';
        } else {
            return 'subscriber';
        }
    }

    /**
     * Format currency
     */
    public static function format_currency($amount, $currency = 'USD') {
        if (!$amount) {
            return '—';
        }
        
        if ($currency === 'USD') {
            if ($amount >= 1000000) {
                return '$' . number_format($amount / 1000000, 1) . 'M';
            } elseif ($amount >= 1000) {
                return '$' . number_format($amount / 1000, 1) . 'K';
            } else {
                return '$' . number_format($amount);
            }
        }
        
        return number_format($amount) . ' ' . $currency;
    }
    
    /**
     * Get time ago string
     */
    public static function time_ago($datetime) {
        return human_time_diff(strtotime($datetime), current_time('timestamp')) . ' ago';
    }
    
    /**
     * Truncate text to specified length
     */
    public static function truncate($text, $length = 100, $append = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        
        return $text . $append;
    }
    
    /**
     * Get available sectors
     */
    public static function get_sectors() {
        return array(
            'Energy',
            'FinTech',
            'Health',
            'Agriculture', 
            'Education',
            'ECommerce',
            'RealEstate',
            'Other'
        );
    }
    
    /**
     * Get available funding stages
     */
    public static function get_funding_stages() {
        return array(
            'Pre-Seed',
            'Seed',
            'Series A',
            'Series B',
            'Series C+',
            'Growth'
        );
    }
}
