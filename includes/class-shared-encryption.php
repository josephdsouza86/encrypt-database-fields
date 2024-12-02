<?php

namespace EncryptDatabaseFields;

class SharedEncryption
{
    protected $cipher = 'AES-256-CBC';
    protected $key;

    public function __construct($secret_key = null)
    {
        if (empty($secret_key) && defined('ENCRYPT_DB_FIELDS_SECRET_KEY')) {
            $secret_key = ENCRYPT_DB_FIELDS_SECRET_KEY;
        } else {
            // Unsafe fallback
            $secret_key = 'keystring';
        }

        $this->key = hash('sha256', $secret_key);
    }

    public function encrypt($str)
    {
        if (empty($str)) {
            return $str;
        }

        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($str, $this->cipher, $this->key, 0, $iv);
        return ENCRYPT_DB_FIELDS_PREFIX . base64_encode($iv . $encrypted);
    }

    public function decrypt($enc_str)
    {
        if (strpos($enc_str, ENCRYPT_DB_FIELDS_PREFIX) !== 0) {
            return $enc_str;
        }

        $enc_str = substr($enc_str, strlen(ENCRYPT_DB_FIELDS_PREFIX));
        $decoded = base64_decode($enc_str);
        $iv_len = openssl_cipher_iv_length($this->cipher);
        $iv = substr($decoded, 0, $iv_len);
        $encrypted = substr($decoded, $iv_len);

        return openssl_decrypt($encrypted, $this->cipher, $this->key, 0, $iv);
    }

    protected function get_user_roles()
    {
        global $wp_roles;
        $roles = $wp_roles->roles;
        $choices = [];
        foreach ($roles as $role => $details) {
            $choices[$role] = $details['name'];
        }
        return $choices;
    }

    public function get_setting_name ($field)
    {
        return ENCRYPT_DB_FIELDS_PREFIX . $field;
    }

    public function get_setting_value ($field, $setting)
    {
        $setting_name = $this->get_setting_name($setting);
        
        if (isset($field[$setting_name])) {
            return $field[$setting_name];
        }
    
        // Defaults
        switch ($setting) {
            case 'is_encrypted':
                return false;
            case 'hide_value':
                return false;
            case 'visible_roles':
                return ['administrator'];
            default:
                return null; 
        }
    }
}
