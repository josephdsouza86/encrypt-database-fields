<?php

namespace EncryptDatabaseFields;

class UserMetaEncryption extends SharedEncryption
{
    public function __construct()
    {
        parent::__construct();

        // Hook into user meta operations
        add_filter('update_user_metadata', [$this, 'encrypt_user_meta'], 10, 4);
        add_filter('get_user_metadata', [$this, 'decrypt_user_meta'], 10, 5);
    }

    /**
     * Encrypt user meta before saving to the database
     *
     * @param null|bool $null
     * @param int $user_id
     * @param string $meta_key
     * @param mixed $meta_value
     * @return string|null
     */
    public function encrypt_user_meta($null, $user_id, $meta_key, $meta_value)
    {
        // Skip if meta key does not require encryption
        if (!$this->should_encrypt_meta($meta_key)) {
            return $null;
        }

        // Encrypt the meta value if not already encrypted
        if (strpos($meta_value, ENCRYPT_DB_FIELDS_PREFIX) !== 0) {
            $meta_value = $this->encrypt($meta_value);
        }

        // Temporarily remove the filter to prevent recursion
        remove_filter('update_user_metadata', [$this, 'encrypt_user_meta'], 10, 4);

        // Update the user meta
        update_metadata('user', $user_id, $meta_key, $meta_value);

        // Re-add the filter
        add_filter('update_user_metadata', [$this, 'encrypt_user_meta'], 10, 4);

        // Return a "false check" to cause the update_metadata to return early
        return false;
    }

    /**
     * Decrypt user meta when retrieving it
     *
     * @param null|mixed $meta_value
     * @param int $user_id
     * @param string $meta_key
     * @param bool $single
     * @return mixed
     */
    public function decrypt_user_meta($meta_value, $object_id, $meta_key, $single, $meta_type)
    {
        // Only target user meta
        if ($meta_type !== 'user') {
            return $meta_value;
        }
        
        // Skip if meta key does not require decryption
        if (!$this->should_encrypt_meta($meta_key)) {
            return $meta_value;
        }

        if (empty($meta_value)) {
            $meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

            if ( ! $meta_cache ) {
                $meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
                if ( isset( $meta_cache[ $object_id ] ) ) {
                    $meta_cache = $meta_cache[ $object_id ];
                } else {
                    $meta_cache = null;
                }
            }

            if ( ! $meta_key ) {
                return $meta_cache;
            }

            if ( isset( $meta_cache[ $meta_key ] ) ) {
                if ( $single ) {
                    $meta_value = maybe_unserialize( $meta_cache[ $meta_key ][0] );
                } else {
                    $meta_value = array_map( 'maybe_unserialize', $meta_cache[ $meta_key ] );
                }
            }
        }

        // Decrypt the meta value if it's encrypted
        if (!empty($meta_value) && is_array($meta_value)) {
            foreach ($meta_value as &$value) {
                if (strpos($value, ENCRYPT_DB_FIELDS_PREFIX) === 0) {
                    $value = $this->decrypt($value);
                }
                if ($single) {
                    break;
                }
            }
        } elseif (strpos($meta_value, ENCRYPT_DB_FIELDS_PREFIX) === 0) {
            $meta_value = $this->decrypt($meta_value);
        }

        return $meta_value;
    }

    /**
     * Determine if a user meta key requires encryption
     *
     * @param string $meta_key
     * @return bool
     */
    private function should_encrypt_meta($meta_key)
    {
        // Fetch the list of meta keys from wp-config.php
        $keys_to_encrypt = defined('ENCRYPT_DB_FIELDS_META_KEYS') ? ENCRYPT_DB_FIELDS_META_KEYS : [];

        return in_array($meta_key, $keys_to_encrypt, true);
    }
}
