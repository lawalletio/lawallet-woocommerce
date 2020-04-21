<?
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class LND_Settings_Page_Generator {
    // Define settings structure
    protected static $structure = null;

    // Keep settings in memory
    protected $settings  = [];

    protected $title = 'No Title';

    // Singleton instance
    protected static $instance = false;

    // Define settings prefix
    public static $prefix = 'lnd_settings_gen';

    /**
     * Singleton control
     */
    public static function instance() {
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function __construct() {
        // Register settings
        $this->register_settings();

        // Load settings now
        $this->load_settings();
    }

    /**
     * Load settings
     *
     * @access public
     * @return void
     */
    public function load_settings() {
        // Load any stored settings
        $stored = get_option(static::$prefix, []);

        // Iterate over field structure and either assign stored value or revert to default value
        foreach (static::get_structure() as $tab_key => $tab) {
            foreach ($tab['children'] as $section_key => $section) {
                foreach ($section['children'] as $field_key => $field) {

                    // Set value
                    if (isset($stored[$field_key])) {
                        $this->settings[$field_key] = $stored[$field_key];
                    }
                    else {
                        $this->settings[$field_key] = isset($field['default']) ? $field['default'] : null;
                    }
                }
            }
        }
    }


    /**
     * Register settings with WordPress
     *
     * @access public
     * @return void
     */
    public function register_settings() {
        // Iterate over tabs
        foreach (static::get_structure() as $tab_key => $tab) {

            // Tab has no settings
            if (!static::tab_has_settings($tab)) {
                continue;
            }

            // Register tab
            register_setting(
                static::$prefix . '-' . $tab_key,
                static::$prefix,
                [$this, 'validate_settings']
            );

            // Iterate over sections
            foreach ($tab['children'] as $section_key => $section) {
                // Section has no settings
                if (!static::section_has_settings($section)) {
                    continue;
                }

                $settings_page_id = static::$prefix . '-' . str_replace('_', '-', $tab_key);

                // Register section
                add_settings_section(
                    $section_key,
                    $section['title'],
                    array($this, 'print_section_info'),
                    $settings_page_id
                );

                // Iterate over fields
                foreach ($section['children'] as $field_key => $field) {
                    // Register field
                    add_settings_field(
                        static::$prefix . '-' . $field_key,
                        $field['title'],
                        [$this, 'print_field_' . $field['type']],
                        $settings_page_id,
                        $section_key,
                        array(
                            'field_key'             => $field_key,
                            'field'                 => $field,
                            'data-hint'             => !empty($field['hint']) ? $field['hint'] : null,
                        )
                    );
                }
            }
        }
    }

    /**
     * Check if tab has at least one setting
     *
     * @access public
     * @param array $tab
     * @return bool
     */
    public static function tab_has_settings($tab) {
        foreach ($tab['children'] as $section_key => $section) {
            if (static::section_has_settings($section)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if section has at least one setting
     *
     * @access public
     * @param array $section
     * @return bool
     */
    public static function section_has_settings($section) {
        return !empty($section['children']);
    }

    /**
     * Get value of a single setting
     *
     * @access public
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null) {
        $instance = static::instance();

        // Get settings value
        $value = isset($instance->settings[$key]) ? $instance->settings[$key] : $default;

        // Allow developers to override value and return it
        return apply_filters(static::$prefix . '_value', $value, $key);
    }

    /**
     * Check value of a single setting
     *
     * Uses strict type comparison
     *
     * @access public
     * @param string $key
     * @param mixed $compare
     * @return bool
     */
    public static function check($key, $compare = null) {
        // Get value
        $value = static::get($key, false);

        // Value not available
        if ($value === false) {
            return false;
        }

        // Compare as bool
        if ($compare === null) {
            return (bool) $value;
        }

        // Value does not match
        if ($value !== $compare) {
            return false;
        }

        // Value matches
        return true;
    }

    /**
     * Print settings page
     *
     * @access public
     * @return void
     */
    public function print_settings_page() {
        if (!is_admin()) {
          throw new \Exception(__('You need to be admin to access these settings', 'lnd-woocommerce'), 403);
        }

        // Get current tab
        $current_tab = static::get_tab();

        // Print notices
        settings_errors(static::$prefix);

        // Print header
        include WC_LND_ADMIN_PATH . '/views/header.php';
        echo '<div class="wrap woocommerce">';

        $template_name = $this->get_tab_template($current_tab);
        call_user_func([$this, 'print_template_' . $template_name]);

        echo '</div>';
        // Print footer
        include WC_LND_ADMIN_PATH . '/views/footer.php';


    }

    public function get_tab_template($tab) {
      $tab_obj = $this->get_structure()[$tab];
      $template = isset($tab_obj['template']) ? $tab_obj['template'] : 'default';
      return $template;
    }

    public function print_template_default() {
      // Get current tab
      $current_tab = static::get_tab();

      // Open form container
      echo '<form method="post" action="options.php" enctype="multipart/form-data">';

      // Print settings page content
      include WC_LND_ADMIN_PATH . '/views/fields.php';

      // Close form container
      echo '</form>';
    }

    public static function get_structure() {
      return static::$structure;
    }

    /**
     * Get current settings tab
     *
     * @access public
     * @return string
     */
    public static function get_tab() {
        $structure = static::get_structure();

        // Check if we know tab identifier
        if (isset($_GET['tab']) && isset($structure[$_GET['tab']])) {
            return $_GET['tab'];
        } else {
            $array_keys = array_keys($structure);
            return array_shift($array_keys);
        }
    }

    /**
     * Print section info
     *
     * @access public
     * @param array $section
     * @return void
     */
    public function print_section_info($section) {
        foreach (static::get_structure() as $tab_key => $tab) {
            if (!empty($tab['children'][$section['id']]['info'])) {
                echo '<p>' . $tab['children'][$section['id']]['info'] . '</p>';
            }
        }
    }

    /**
     * Check if string begins with a given string
     *
     * @access public
     * @param string $string
     * @param string $substring
     * @return bool
     */
    public static function string_begins_with_substring($string, $substring) {
        return $substring === '' || strpos($string, $substring) === 0;
    }

    /**
     * Check if value is empty but not zero, that is - empty string,
     * null, boolean false or empty array
     *
     * @access public
     * @param mixed $value
     * @return bool
     */
    public static function is_empty($value) {
        return ($value === '' || $value === null || $value === false || ((is_array($value) || is_object($value)) && count($value) === 0));
    }

    /**
     * Render attributes
     *
     * @access public
     * @param array $params
     * @param array $custom
     * @param string $type
     * @return void
     */
    private static function attributes($params, $custom = array(), $type = 'text') {
        $html = '';

        // Get full list of attributes
        $attributes = array_merge(array('type', 'name', 'id', 'class', 'autocomplete', 'style', 'title', 'placeholder', 'value', 'required', 'disabled'), $custom);

        // Extract attributes and append to html string
        foreach ($attributes as $attribute) {
            if (isset($params[$attribute]) && !static::is_empty($params[$attribute])) {
                $html .= $attribute . '="' . $params[$attribute] . '" ';
            }
        }

        // Extract any data attributes
        foreach ($params as $param_key => $param) {
            if (static::string_begins_with_substring($param_key, 'data-') && !in_array($param_key, $attributes, true)) {
                $html .= $param_key . '="' . $param . '" ';
            }
        }

        // Return attributes string
        return $html;
    }


    /**
     * Upload file
     *
     * @access public
     * @return bool
     */
    public function upload_file($input_name, $destination, $accept_format=[]) {
        // Check if file was uploaded correctly
        if (!is_uploaded_file($_FILES[static::$prefix]['tmp_name'][$input_name])) {
            throw new Exception;
        }

        $format = strtolower(pathinfo($_FILES[static::$prefix]['name'][$input_name], PATHINFO_EXTENSION));
        if (count($accept_format)>=0 && !in_array($format, $accept_format)) {
          throw new \Exception(__('Invalid file format', 'lnd-woocommerce'), 415);
        }

        if (move_uploaded_file($_FILES[static::$prefix]['tmp_name'][$input_name], $destination)) {
          return true;
        } else {
          throw new \Exception(__("Couldn't upload the file", "lnd-woocommerce"), 500);
        }

    }

    /**
     * Render generic input field
     *
     * @access public
     * @param string $type
     * @param array $params
     * @param array $custom_attributes
     * @param bool $is_date
     * @return void
     */
    private static function print_tag_input($type, $params, $custom_attributes = array(), $is_date = false) {
        // Get attributes
        $attributes = static::attributes($params, $custom_attributes, $type);

        // Generate field html
        $field_html = '<input type="' . $type . '" ' . $attributes . '>';

        // Render field
        static::output($params, $field_html, $type, $is_date);
    }

    /**
     * Render checkbox or radio field
     *
     * @access public
     * @param string $type
     * @param array $params
     * @return void
     */
    public function print_tag_checkbox_or_radio($type, $params) {
        $field_html = '';

        // Single field?
        if (empty($params['options'])) {
            $attributes = static::attributes($params, array('value', 'checked'), $type);
            $field_html .= '<input type="' . $type . '" ' . $attributes . '/>';
        } else { // Set of fields - iterate over options and generate field for each option
            // Open list
            $field_html .= '<ul>';

            // Iterate over field options and display as individual items
            foreach ($params['options'] as $key => $label) {

                // Customize params
                $custom_params = $params;
                $custom_params['id'] = $custom_params['id'] . '_' . $key;

                // Get attributes
                $attributes = static::attributes($custom_params, array(), $type);

                // Check if this item needs to be checked
                if (isset($params['value'])) {
                    $values = (array) $params['value'];
                    $checked = in_array($key, $values) ? 'checked="checked"' : '';
                }
                else {
                    $checked = (isset($params['checked']) && in_array($key, $params['checked']) ? 'checked="checked"' : '');
                }

                // Generate HTML
                $field_html .= '<li><input type="' . $type . '" value="' . $key . '" ' . $checked . ' ' . $attributes . '>' . (!empty($label) ? ' ' . $label : '') . '</li>';
            }

            // Close list
            $field_html .= '</ul>';
        }

        // Render field
        static::output($params, $field_html, $type);
    }

    /**
     * Render text area field
     *
     * @access public
     * @param array $params
     * @return void
     */
    public static function print_tag_textarea($params) {
        // Get attributes
        $attributes = static::attributes($params, array('value', 'maxlength', 'placeholder', 'rows'), 'textarea');

        // Get value
        $value = !empty($params['value']) ? $params['value'] : '';

        // Generate field html
        $field_html = '<textarea ' . $attributes . '>' . $value . '</textarea>';

        // Render field
        static::output($params, $field_html, 'textarea');

        // Print hint
        $this->print_hint($args);
    }

    public function print_field_template($args = array()) {
      $title = $args['field']['title'];
      // Print settings page content
      include WC_LND_ADMIN_PATH . '/views/' . $args['field']['view'] . '.php';
    }

    /**
     * Render text field
     *
     * @access public
     * @param array $args
     * @param string $field_type
     * @return void
     */
    public function print_field_text($args = array(), $field_type = null) {
        // Get prefixed key

        // Configure field
        $config = array(
            'id'                        => $args['field_key'],
            'name'                      => static::$prefix . '[' . $args['field_key'] . ']',
            'value'                     => htmlspecialchars(static::get($args['field_key'])),
            'class'                     => 'wc_lnd_setting ' . 'wc_lnd_field_long ' . (!empty($args['field']['class']) ? $args['field']['class'] : ''),
            'title'                     => !empty($args['title']) ? $args['title'] : '',
            'placeholder'               => (isset($args['field']['placeholder']) && !static::is_empty($args['field']['placeholder'])) ? $args['field']['placeholder'] : '',
            'data-hint'                 => !empty($args['data-hint']) ? $args['data-hint'] : '',
        );

        // Validation
        if (!empty($args['field']['data-validation'])) {
            $config['data-validation'] = $args['field']['data-validation'];
        }

        // Check if field is required
        if (!empty($args['field']['required'])) {
            $config['required'] = 'required';
        }

        // Get field type
        $field_type = $field_type ?: 'text';

        // Print field
        static::print_tag_input($field_type, $config);
    }

    /**
     * Render number field
     *
     * @access public
     * @param array $args
     * @return void
     */
    public function print_field_number($args = array()) {
        static::print_field_text($args, 'number');
    }

    /**
     * Render decimal field
     *
     * @access public
     * @param array $args
     * @return void
     */
    public function print_field_decimal($args = array()) {
        static::print_field_text($args, 'decimal');
    }

    /**
     * Render text area field$
     *
     * @access public
     * @param array $args
     * @return void
     */
    public function print_field_textarea($args = array()) {
        // Get prefixed key
        $prefixed_key = $args['field_key'];

        // Configure field
        $config = array(
            'id'                        => $prefixed_key,
            'name'                      => static::$prefix . '[' . $prefixed_key . ']',
            'value'                     => htmlspecialchars(static::get($args['field_key'])),
            'class'                     => 'wc_lnd_setting wc_lnd_field_long ' . (!empty($args['field']['class']) ? $args['field']['class'] : ''),
            'title'                     => !empty($args['title']) ? $args['title'] : '',
            'placeholder'               => (isset($args['field']['placeholder']) && !static::is_empty($args['field']['placeholder'])) ? $args['field']['placeholder'] : '',
            'data-hint'                 => !empty($args['data-hint']) ? $args['data-hint'] : '',
        );

        // Validation
        if (!empty($args['field']['data-validation'])) {
            $config['data-validation'] = $args['field']['data-validation'];
        }

        // Print field
        static::textarea($config);
    }

    /**
     * Render checkbox field
     *
     * @access public
     * @param array $args
     * @return void
     */
    public function print_field_checkbox($args = array()) {
        // Get prefixed key
        $prefixed_key = $args['field_key'];
        // Print field
        $this->print_tag_checkbox_or_radio('checkbox', [
            'id'                    => $prefixed_key,
            'name'                  => static::$prefix . '[' . $prefixed_key . ']',
            'checked'               => (bool) static::get($args['field_key']),
            'class'                 => 'wc_lnd_setting ' . (!empty($args['field']['class']) ? $args['field']['class'] : ''),
            'title'                 => !empty($args['title']) ? $args['title'] : '',
            'disabled'              => !empty($args['disabled']) ? $args['disabled'] : '',
            'data-hint'             => !empty($args['data-hint']) ? $args['data-hint'] : '',
        ]);
    }

    /**
     * Render file field
     *
     * @access public
     * @param array $args
     * @return void
     */
    public function print_field_file($args = array()) {
        static::print_field_text($args, 'file');
    }

    /**
     * Render select field
     *
     * @access public
     * @param array $args
     * @param bool $is_multiselect
     * @param bool $is_grouped
     * @return void
     */
    public function print_field_select($args = array(), $is_multiselect = false, $is_grouped = false) {
        // Get prefixed key
        $prefixed_key = $args['field_key'];

        // Get value
        $value = static::get($args['field_key']);

        // Get options
        $options = $args['field']['options'];

        // Fix multiselect options
        // Note: this is designed to work with user-entered "tags" with no predefined options list
        if ($is_multiselect && empty($options)) {
            $options = $value;
        }

        // Print field
        static::print_tag_select(array(
            'id'                    => $prefixed_key,
            'name'                  => static::$prefix . '[' . $prefixed_key . ']' . ($is_multiselect ? '[]' : ''),
            'options'               => $options,
            'value'                 => $value,
            'class'                 => 'wc_lnd_setting wc_lnd_field_select wc_lnd_field_long ' . (!empty($args['field']['class']) ? $args['field']['class'] : ''),
            'title'                 => !empty($args['title']) ? $args['title'] : '',
            'data-hint'    => !empty($args['data-hint']) ? $args['data-hint'] : '',
        ), $is_multiselect, $is_grouped);
    }

    /**
     * Render select field
     *
     * @access public
     * @param array $params
     * @param bool $is_multiple
     * @param bool $is_grouped
     * @param bool $prepend_group_key
     * @return void
     */
    public static function print_tag_select($params, $is_multiple = false, $is_grouped = false, $prepend_group_key = false) {
        // Get attributes
        $attributes = static::attributes($params, array(), 'select');

        // Get options
        $options = static::print_tag_options($params, $is_grouped, $prepend_group_key);

        // Check if it's multiselect
        $multiple_html = $is_multiple ? 'multiple' : '';

        // Generate field html
        $field_html = '<select ' . $multiple_html . ' ' . $attributes . '>' . $options . '</select>';

        // Render field
        $field_type = $is_multiple ? 'multiselect' : ($is_grouped ? 'grouped_select' : 'select');
        static::output($params, $field_html, $field_type);
    }

    /**
     * Get options for select field
     *
     * @access public
     * @param array $params
     * @param bool $is_grouped
     * @param bool $prepend_group_key
     * @return string
     */
    private static function print_tag_options($params, $is_grouped = false, $prepend_group_key = false) {
        $html = '';
        $selected = array();

        // Get selected option(s)
        if (isset($params['value'])) {
            $selected = (array) $params['value'];
        }
        else if (!empty($params['selected'])) {
            $selected = (array) $params['selected'];
        }

        // Extract options and append to html string
        if (!empty($params['options']) && is_array($params['options'])) {

            // Fix array depth if options are not grouped
            if (!$is_grouped) {
                $params['options'] = array(
                    'not_grouped' => array(
                        'options' => $params['options'],
                    ),
                );
            }

            // Iterate over option groups
            foreach ($params['options'] as $group_key => $group) {

                // Option group start
                if ($is_grouped) {
                    $html .= '<optgroup label="' . $group['label'] . '">';
                }

                // Iterate over options
                foreach ($group['options'] as $option_key => $option) {

                    // Get option key
                    $option_key = (($is_grouped && $prepend_group_key) ? $group_key . '__' . $option_key : $option_key);

                    // Get option data
                    $option_data = '';

                    if (!empty($params['option_data'][$option_key])) {
                        foreach ($params['option_data'][$option_key] as $data_key => $data) {
                            $option_data .= 'data-' . $data_key . '="' . htmlspecialchars($data) . '" ';
                        }
                    }

                    // Check if option is selected
                    $selected_html = in_array($option_key, $selected) ? 'selected="selected"' : '';

                    // Format option html
                    $html .= '<option value="' . $option_key . '" ' . $option_data . ' ' . $selected_html . '>' . $option . '</option>';
                }

                // Option group end
                if ($is_grouped) {
                    $html .= '</optgroup>';
                }
            }
        }

        return $html;
    }

    /**
     * Render link field
     *
     * @access public
     * @param array $args
     * @return void
     */
    public function print_field_link($args = array()) {
        // Get properties
        $label = !empty($args['field']['link_label']) ? $args['field']['link_label'] : $args['field']['link_url'];

        // Print link
        echo '<a href="' . $args['field']['link_url'] . '">' . $label . '</a>';
    }

    /**
     * Validate settings
     *
     * @access public
     * @param array $input
     * @return void
     */
    public function validate_settings($input) {
        $structure = static::get_structure();

        // Track if this is a first or a second call to this function
        // When settings are saved for the first time, WordPress calls
        // it twice and $input is different on a second call
        if (!defined(static::$prefix . '_validated')) {
            define(static::$prefix . '_validated', true);
            $settings_already_validated = false;
            $field_key_prefix = static::$prefix;
        } else {
            $settings_already_validated = true;
            $field_key_prefix = '';
        }

        // Use serialized input data if available
        if (!empty($_POST[static::$prefix . '_serialized']) && !$settings_already_validated) {

            $unserialized_vars = array();

            // Explode vars
            $exploded_vars = explode('&', stripslashes($_POST[static::$prefix . '_serialized']));

            // Iterate over vars
            foreach ($exploded_vars as $var) {

                // Parse var
                $parsed_var = array();
                parse_str($var, $parsed_var);

                // Merge with main array
                if (!empty($parsed_var[static::$prefix]) && is_array($parsed_var[static::$prefix])) {
                    $unserialized_vars = static::array_merge_recursive_for_indexed_lists($unserialized_vars, $parsed_var[static::$prefix]);
                }
            }

            $input = !empty($unserialized_vars) ? $unserialized_vars : $input;
        }

        // Set output to current settings first
        $output = $this->settings;
        $field_array = array();
        $errors = array();

        // Attempt to validate settings
        try {

            // Check if request came from a correct page
            if (empty($_POST['current_tab']) || !isset($structure[$_POST['current_tab']])) {
                throw new Exception(__('Unable to validate settings.', 'lnd-woocommerce'));
            }

            // Reference current tab
            $current_tab = $_POST['current_tab'];

            // Iterate over fields and validate new values
            foreach ($structure[$current_tab]['children'] as $section_key => $section) {
                foreach ($section['children'] as $field_key => $field) {

                    $full_key = $field_key;

                    switch($field['type']) {

                        // Checkbox
                        case 'checkbox':
                            $output[$field_key] = static::is_empty($input[$full_key]) ? '0' : '1';
                            break;

                        // Select
                        case 'select':
                            if (isset($input[$full_key]) && isset($field['options'][$input[$full_key]])) {
                                $output[$field_key] = $input[$full_key];
                            }
                            break;

                        // Grouped select
                        case 'grouped_select':
                            if (isset($input[$full_key])) {
                                foreach ($field['options'] as $option_group) {
                                    if (isset($option_group['options'][$input[$full_key]])) {
                                        $output[$field_key] = $input[$full_key];
                                    }
                                }
                            }
                            break;

                        // Multiselect
                        // Note: this is designed to work with user-entered "tags" with no predefined options list
                        case 'multiselect':
                            $output[$field_key] = array();

                            if (!empty($input[$full_key]) && is_array($input[$full_key])) {
                                foreach ($input[$full_key] as $multiselect_value) {
                                    $sanitized = sanitize_key($multiselect_value);
                                    $output[$field_key][$sanitized] = $sanitized;
                                }
                            }

                            $output[$field_key] = array_unique($output[$field_key]);

                            break;

                        // Number
                        case 'number':
                            if (isset($input[$full_key]) && is_numeric($input[$full_key])) {
                                $output[$field_key] = (int) esc_attr(trim($input[$full_key]));
                            }
                            else {
                                $output[$field_key] = '';
                            }
                            break;

                        // Decimal
                        case 'decimal':
                            if (isset($input[$full_key]) && is_numeric($input[$full_key])) {
                                $output[$field_key] = (float) esc_attr(trim($input[$full_key]));
                            }
                            else {
                                $output[$field_key] = '';
                            }
                            break;

                        // Text input
                        default:
                            if (isset($input[$full_key])) {
                              $output[$field_key] = esc_attr(trim($input[$full_key]));
                            }
                            break;
                    }
                }
            }

            // Add notice
            if (!$settings_already_validated) {
                add_settings_error(
                    static::$prefix,
                    static::$prefix . '_updated',
                    __('Settings updated.', 'lnd-woocommerce'),
                    'updated'
                );
            }

        } catch (Exception $e) {

            // Add error
            add_settings_error(
                static::$prefix,
                static::$prefix . '_validation_failed',
                $e->getMessage()
            );
        }

        // Store new settings
        return $output;
    }

    /**
     * Check if current request is for a plugin's settings page
     *
     * @access public
     * @return bool
     */
    public static function is_settings_page() {
        return preg_match('/page=' . static::$prefix . '/i', $_SERVER['REQUEST_URI']);
    }

    /**
     * Render templates in footer
     *
     * @access public
     * @return void
     */
    public function render_templates_in_footer() {
        // Load only on our pages that use templates
        if (static::is_settings_page() && static::settings_page_uses_templates()) {

            // Get current tab
            $current_tab = static::get_tab();

            // Include view
            echo '<div>Footer Baby!</div>';
        }
    }

    /**
     * Check if current settings page uses templates
     *
     * @access public
     * @return bool
     */
    public static function settings_page_uses_templates() {
        return false; //Always false
    }

    /**
     * Get tab title by tab key
     *
     * @access public
     * @param string $key
     * @return string
     */
    public static function get_tab_title($key) {
        if (!empty(static::$structure[$key])) {
            return static::$structure[$key]['title'];
        }

        return false;
    }

    /**
     * Custom capability for settings
     *
     * @access public
     * @param string $capability
     * @return string
     */
    public function custom_settings_capability($capability) {
        return lnd_wc::get_admin_capability();
    }

    /**
     * Render field label
     *
     * @access public
     * @param array $params
     * @return string
     */
    private static function label($params) {
        echo static::label_html($params);
    }

    /**
     * Get field label html
     *
     * @access public
     * @param array $params
     * @return string
     */
    private static function label_html($params) {
        // Check if label needs to be displayed
        if (!empty($params['id']) && isset($params['label']) && !static::is_empty($params['label'])) {

            // Return label html
            return '<label for="' . $params['id'] . '">' . $params['label'] . '</label>';
        }

        return '';
    }

    /**
     * Output field based on context
     *
     * @access public
     * @param array $params
     * @param string $field_html
     * @param string $type
     * @param bool $is_date
     * @return void
     */
    private static function output($params, $field_html, $type, $is_date = false) {
        // Print label
        static::label($params);

        // Print field
        echo $field_html;

        // Print hint
        static::instance()->print_hint($params);
    }

    /**
     * Merge arrays recursively combining child arrays that have numeric keys (array_merge_recursive does not support this)
     *
     * @access public
     * @param array $all_vars
     * @param array $var
     * @return array
     */
    public static function array_merge_recursive_for_indexed_lists($all_vars, $var) {
        foreach ($var as $key => $value) {

            // Key does not exist in main array yet
            if (!isset($all_vars[$key])) {
                $all_vars[$key] = $value;
            }
            // Value is array
            else if (is_array($value)) {
                $all_vars[$key] = static::array_merge_recursive_for_indexed_lists($all_vars[$key], $value);
            }
            // Finite numerically indexed list of values
            else if (is_int($key)) {
                $all_vars[] = $value;
            }
        }

        return $all_vars;
    }

    /**
     * Print hint
     *
     * @access protected
     * @param array $args
     * @return void
     */
    protected function print_hint($args) {
        if (!empty($args['data-hint'])) {
            echo '<div class="wc_lnd_settings_hint">' . $args['data-hint'] . '</div>';
        }
    }
}
