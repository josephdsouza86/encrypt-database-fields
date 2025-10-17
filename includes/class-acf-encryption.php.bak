<?php

namespace EncryptDatabaseFields;

class ACFEncryption extends SharedEncryption
{
    public function __construct()
    {
        parent::__construct();

        add_filter('acf/field_group/additional_field_settings_tabs', [$this, 'add_field_group_settings_tab']);
        add_action('acf/field_group/render_field_settings_tab/encryption/type=text', [$this, 'render_field_settings'], 10, 3);
        add_action('acf/field_group/render_field_settings_tab/encryption/type=textarea', [$this, 'render_field_settings'], 10, 3);
        add_action('acf/update_value', [$this, 'update_value'], 10, 3);
        add_action('acf/load_value', [$this, 'load_value'], 10, 3);
        add_filter('acf/prepare_field', [$this, 'prepare_field'], 10, 3);
    }

    public function add_field_group_settings_tab($tabs)
    {
        $tabs['encryption'] = __('Encryption');
        return $tabs;
    }

    public function render_field_settings($field)
    {
        // Add "Encrypt Field?" setting
        acf_render_field_setting($field, [
            'label'        => __('Encrypt Field?'),
            'instructions' => 'Encrypts the value in the database',
            'name'         => $this->get_setting_name('is_encrypted'),
            'type'         => 'true_false',
            'ui'           => 1,
        ], true);

        // Add "Hide Value?" setting
        acf_render_field_setting($field, [
            'label'        => __('Hide Value?'),
            'instructions' => "Don't show the value on load. Requires user interaction to reveal.",
            'name'         => $this->get_setting_name('hide_value'),
            'type'         => 'true_false',
            'ui'           => 1,
        ], true);

        // Add "Visible For" setting
        acf_render_field_setting($field, [
            'label'        => __('Visible For'),
            'instructions' => __('Select roles that are allowed to view the value'),
            'name'         => $this->get_setting_name('visible_roles'),
            'type'         => 'checkbox',
            'choices'      => $this->get_user_roles(),
            'default_value' => ['administrator'],
        ], true);
    }

    public function update_value($value, $post_id, $field)
    {
        // Check if encryption is enabled for this field
        $is_encrypted = $this->get_setting_value($field, 'is_encrypted');
        if (!$is_encrypted) {
            // Using decrypt will ensure that the plain text value is returned 
            return $this->decrypt($value);
        }
    
        // Check if the current context is a backend save
        if (is_admin() && isset($_POST['acf'])) {
            // Check user roles against "visible for" settings
            $visible_roles = $this->get_setting_value($field, 'visible_roles');
            $current_user_roles = wp_get_current_user()->roles;
            $is_visible = !empty(array_intersect($current_user_roles, $visible_roles));
    
            if (!$is_visible) {
                // User does not have permission; retrieve original value to avoid data loss
                $original_value = get_field($field['key'], $post_id);
                return $this->encrypt($original_value);
            }
        }
    
        // Encrypt the new value
        return $this->encrypt($value);
    }

    public function load_value($value, $post_id, $field)
    {
        if (strpos($value, ENCRYPT_DB_FIELDS_PREFIX) === 0) {
            return $this->decrypt($value);
        }
        return $value;
    }

    public function prepare_field($field)
    {
        // Only do this for encrypted fields
        $is_encrypted = $this->get_setting_value($field, 'is_encrypted');
        if (!$is_encrypted) return $field;

        // Load encryption field settings
        $hide_value = $this->get_setting_value($field, 'hide_value');
        $visible_roles = $this->get_setting_value($field, 'visible_roles');
    
        // Build CSS for this encrypted fields configuration
        $field_selector = '.acf-field-' . substr($field['key'], 6);
        $input_selector = $field['id'];
        $css = "{$field_selector} label:after {
            content: ' (encrypted)';
            font-size: 80%;
            font-weight: normal;
            color: #CCC;
        }";

        // Check if the current user has permission to view this field
        $current_user_roles = wp_get_current_user()->roles;
        $is_visible = !empty(array_intersect($current_user_roles, $visible_roles));

        if (!$is_visible) { 
            // Don't show the field if the user doesn't have permission. Post a blank hidden field instead
            $field['type'] = 'message';
            $field['message'] = __('You do not have permission to view this field.');
            $field['value'] = '';
        } 
        
        if ($is_visible && $hide_value) {
            // Hide the value and show a button to reveal it
            $css .= "#{$input_selector} {
                display: none;
            }";
            ?>
            <script>
                (function () {
                    document.addEventListener('DOMContentLoaded', function () {
                        var inputWrapper = document.querySelector('<?= $field_selector; ?>');
                        var input = document.getElementById('<?= $input_selector; ?>');
                        var button = document.createElement('a');
                        button.href = '#';
                        button.innerHTML = (input.value) ? 'Click to Show' : 'Click to Add';
                        button.className = 'acf-button button';
                        button.addEventListener('click', function (e) {
                            e.preventDefault();
                            this.style.display = 'none';
                            input.style.display = 'block';
                            return false;
                        });
                        inputWrapper.appendChild(button);
                    });
                }());
            </script>
            <?php
        }

        ?>
        <style type="text/css"><?= $css; ?></style>
        <?php

        return $field;
    }
}
