# Encrypt Database Fields

Adds an option to encrypt ACF text field values and usermeta fields upon save. Useful for storing sensitive data, such as API keys or private personel information.

## Requirements

- PHP 7+ with OpenSSL enabled
- [Advanced Custom Fields](https://www.advancedcustomfields.com/) plugin (If encrypting ACF fields)

## Installation

Download and unzip plugin direcrtory inside of `wp-content/plugins` and activate plugin

Define the `ENCRYPT_DB_FIELDS_SECRET_KEY` constant inside of `wp-config.php`

```
/** ACF Encrypt Field Option Key */
define('ENCRYPT_DB_FIELDS_SECRET_KEY', 'your key here');
```

For usermeta field encryption declare the meta keys you want to be encrypted as follows

```
define('ENCRYPT_DB_FIELDS_META_KEYS', [
    'sensitive_data',
    'private_notes',
    'confidential_info',
]);
```

## Screen Shots

### ACF Field Options

![Field Options Settings](/field-options.png?raw=true "Field Options Settings")