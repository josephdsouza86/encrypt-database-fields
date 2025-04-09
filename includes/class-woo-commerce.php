<?php

namespace EncryptDatabaseFields;

if (!defined('ABSPATH')) exit;

class WooCommerce
{
    protected $encryption;

    public function __construct($encryption_instance)
    {
        $this->encryption = $encryption_instance;

        // Register dynamic filters based on meta keys
        $this->hook_customer_meta_decryption();
    }

    protected function hook_customer_meta_decryption()
    {
        if (!defined('ENCRYPT_DB_FIELDS_META_KEYS') || !is_array(ENCRYPT_DB_FIELDS_META_KEYS)) {
            return;
        }

        foreach (ENCRYPT_DB_FIELDS_META_KEYS as $meta_key) {
            $filter = "woocommerce_customer_get_{$meta_key}";

            add_filter($filter, function ($value) use ($meta_key) {
                if (is_string($value) && strpos($value, ENCRYPT_DB_FIELDS_PREFIX) === 0) {
                    return $this->encryption->decrypt($value);
                }
                return $value;
            }, 10, 1);
        }
    }
}
