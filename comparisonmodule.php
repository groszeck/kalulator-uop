public static function init() {
        add_shortcode( 'kpj_comparison', array( __CLASS__, 'shortcode' ) );
        add_action( 'wp_ajax_kpj_compare', array( __CLASS__, 'ajax_compare' ) );
        add_action( 'wp_ajax_nopriv_kpj_compare', array( __CLASS__, 'ajax_compare' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
    }

    public static function register_assets() {
        $url = plugin_dir_url( __FILE__ );
        wp_register_script(
            'kpj-comparison',
            $url . 'comparison-module.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );
    }

    public static function compare_contracts( $gross, $options = array() ) {
        $defaults = array_fill_keys( array_keys( self::$contract_types ), true );
        $opts     = wp_parse_args( $options, $defaults );
        $gross    = floatval( $gross );
        $results  = array();

        foreach ( self::$contract_types as $type => $method ) {
            if ( ! empty( $opts[ $type ] ) ) {
                $calc = call_user_func( array( 'KPJ_Calculator', $method ), $gross );
                $results[ $type ] = array(
                    'gross' => $gross,
                    'net'   => isset( $calc['net'] ) ? $calc['net'] : 0,
                );
            }
        }

        return $results;
    }

    public static function format_comparison_result( $results ) {
        if ( ! is_array( $results ) || empty( $results ) ) {
            return '<p>' . esc_html__( 'Brak danych do por?wnania.', 'kpj' ) . '</p>';
        }
        $output  = '<table class="kpj-comparison-table"><thead><tr>';
        $output .= '<th>' . esc_html__( 'Umowa', 'kpj' ) . '</th>';
        $output .= '<th>' . esc_html__( 'Brutto', 'kpj' ) . '</th>';
        $output .= '<th>' . esc_html__( 'Netto', 'kpj' ) . '</th>';
        $output .= '</tr></thead><tbody>';
        foreach ( $results as $key => $row ) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html( self::get_contract_label( $key ) ) . '</td>';
            $output .= '<td>' . esc_html( self::format_currency( $row['gross'] ) ) . '</td>';
            $output .= '<td>' . esc_html( self::format_currency( $row['net'] ) ) . '</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody></table>';
        return $output;
    }

    public static function get_comparison_presets() {
        $defaults = array( 2000, 3000, 4000, 5000, 6000, 7000, 8000, 10000 );
        $presets  = apply_filters( 'kpj_comparison_presets', $defaults );
        $valid    = array();
        foreach ( (array) $presets as $value ) {
            if ( is_numeric( $value ) ) {
                $valid[] = floatval( $value );
            }
        }
        if ( empty( $valid ) ) {
            $valid = $defaults;
        }
        return array_unique( $valid );
    }

    public static function shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'types'   => 'uop,uz,uod',
            'default' => '',
            'presets' => '',
        ), $atts, 'kpj_comparison' );

        // parse and validate contract types
        $types = array_map( 'trim', explode( ',', $atts['types'] ) );
        $types = array_intersect( $types, array_keys( self::$contract_types ) );
        if ( empty( $types ) ) {
            $types = array_keys( self::$contract_types );
        }

        // parse presets override
        if ( ! empty( $atts['presets'] ) ) {
            $custom = array();
            foreach ( explode( ',', $atts['presets'] ) as $v ) {
                if ( is_numeric( $v ) ) {
                    $custom[] = floatval( $v );
                }
            }
            $presets = $custom ? array_unique( $custom ) : self::get_comparison_presets();
        } else {
            $presets = self::get_comparison_presets();
        }
        sort( $presets );

        // default selected
        $default = floatval( $atts['default'] );
        if ( ! in_array( $default, $presets, true ) ) {
            $default = reset( $presets );
        }

        wp_enqueue_script( 'kpj-comparison' );
        wp_localize_script(
            'kpj-comparison',
            'KPJCompare',
            array(
                'ajax_url'      => admin_url( 'admin-ajax.php' ),
                'nonce'         => wp_create_nonce( 'kpj_compare_nonce' ),
                'contractTypes' => array_values( $types ),
                'defaultGross'  => $default,
                'presets'       => $presets,
            )
        );

        ob_start();
        ?>
        <div class="kpj-comparison-module">
            <label for="kpj-compare-value"><?php esc_html_e( 'Kwota brutto:', 'kpj' ); ?></label>
            <select id="kpj-compare-value">
                <?php foreach ( $presets as $value ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>"<?php selected( $value, $default, true ); ?>>
                        <?php echo esc_html( self::format_currency( $value ) ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="kpj-compare-run"><?php esc_html_e( 'Por?wnaj', 'kpj' ); ?></button>
            <div id="kpj-compare-result"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function ajax_compare() {
        check_ajax_referer( 'kpj_compare_nonce', 'nonce' );
        $gross   = isset( $_POST['gross'] ) ? floatval( $_POST['gross'] ) : 0;
        $options = array();
        foreach ( array_keys( self::$contract_types ) as $type ) {
            $options[ $type ] = filter_input( INPUT_POST, "options[{$type}]", FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
            if ( is_null( $options[ $type ] ) ) {
                $options[ $type ] = true;
            }
        }
        $results = self::compare_contracts( $gross, $options );
        wp_send_json_success( array( 'html' => self::format_comparison_result( $results ) ) );
    }

    private static function get_contract_label( $type ) {
        $labels = array(
            'uop' => __( 'Umowa o prac?', 'kpj' ),
            'uz'  => __( 'Umowa zlecenie', 'kpj' ),
            'uod' => __( 'Umowa o dzie?o', 'kpj' ),
        );
        return isset( $labels[ $type ] ) ? $labels[ $type ] : ucfirst( $type );
    }

    private static function format_currency( $value ) {
        $formatted = number_format_i18n( (float) $value, 2, ',', ' ' );
        return $formatted . ' ' . __( 'z?', 'kpj' );
    }
}

KPJ_Comparison_Module::init();