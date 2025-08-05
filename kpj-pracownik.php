const VERSION = '1.0.0';
    private static $instance = null;

    /**
     * Constructor.
     */
    private function __construct() {
        $this->define_constants();
        $this->load_textdomain();
        $this->load_modules();
        $this->init_hooks();
    }

    /**
     * Get singleton instance.
     *
     * @return KPJ_Pracownik
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Activation hook.
     */
    public static function activate() {
        self::get_instance()->on_activate();
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate() {
        self::get_instance()->on_deactivate();
    }

    /**
     * On plugin activation.
     */
    private function on_activate() {
        flush_rewrite_rules();
    }

    /**
     * On plugin deactivation.
     */
    private function on_deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Define plugin constants.
     */
    private function define_constants() {
        define( 'KPJ_PRACOWNIK_VERSION', self::VERSION );
        define( 'KPJ_PRACOWNIK_FILE', __FILE__ );
        define( 'KPJ_PRACOWNIK_PATH', plugin_dir_path( __FILE__ ) );
        define( 'KPJ_PRACOWNIK_URL', plugin_dir_url( __FILE__ ) );
        define( 'KPJ_PRACOWNIK_BASENAME', plugin_basename( __FILE__ ) );
    }

    /**
     * Load plugin textdomain for translations.
     */
    private function load_textdomain() {
        load_plugin_textdomain(
            'kpj-pracownik',
            false,
            dirname( KPJ_PRACOWNIK_BASENAME ) . '/languages'
        );
    }

    /**
     * Require all module files.
     */
    private function load_modules() {
        foreach ( glob( KPJ_PRACOWNIK_PATH . 'includes/*.php' ) as $file ) {
            require_once $file;
        }
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        // Frontend assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        // Shortcode
        add_shortcode( 'kpj_pracownik', array( $this, 'render_shortcode' ) );
        // Admin settings page
        add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
        // AJAX handlers
        add_action( 'wp_ajax_kpj_calculate', array( $this, 'ajax_calculate' ) );
        add_action( 'wp_ajax_nopriv_kpj_calculate', array( $this, 'ajax_calculate' ) );
    }

    /**
     * Enqueue frontend styles and scripts.
     */
    public function enqueue_assets() {
        wp_register_style(
            'kpj-pracownik-style',
            KPJ_PRACOWNIK_URL . 'assets/css/style.css',
            array(),
            KPJ_PRACOWNIK_VERSION
        );
        wp_enqueue_style( 'kpj-pracownik-style' );

        wp_register_script(
            'kpj-pracownik-script',
            KPJ_PRACOWNIK_URL . 'assets/js/main.js',
            array( 'jquery' ),
            KPJ_PRACOWNIK_VERSION,
            true
        );
        wp_localize_script(
            'kpj-pracownik-script',
            'kpj_pracownik_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'kpj_pracownik_nonce' ),
            )
        );
        wp_enqueue_script( 'kpj-pracownik-script' );
    }

    /**
     * Render the calculator shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(), $atts, 'kpj_pracownik' );
        ob_start();
        include KPJ_PRACOWNIK_PATH . 'templates/calculator-form.php';
        return ob_get_clean();
    }

    /**
     * AJAX calculate callback.
     */
    public function ajax_calculate() {
        check_ajax_referer( 'kpj_pracownik_nonce' );
        $data = isset( $_POST['data'] ) ? (array) wp_unslash( $_POST['data'] ) : array();
        if ( class_exists( 'KPJ_Calculator' ) ) {
            $result = KPJ_Calculator::calculate( $data );
        } else {
            $result = array();
        }
        wp_send_json_success( $result );
    }

    /**
     * Register plugin settings page.
     */
    public function register_settings_page() {
        add_options_page(
            __( 'KPJ Pracownik Settings', 'kpj-pracownik' ),
            __( 'KPJ Pracownik', 'kpj-pracownik' ),
            'manage_options',
            'kpj-pracownik',
            array( $this, 'settings_page' )
        );
    }

    /**
     * Render settings page.
     */
    public function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( isset( $_POST['kpj_pracownik_settings'] ) ) {
            $settings = wp_kses_post_deep( $_POST['kpj_pracownik_settings'] );
            update_option( 'kpj_pracownik_settings', $settings );
            echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'kpj-pracownik' ) . '</p></div>';
        }
        $settings = get_option( 'kpj_pracownik_settings', array() );
        include KPJ_PRACOWNIK_PATH . 'templates/settings.php';
    }
}

register_activation_hook( __FILE__, array( 'KPJ_Pracownik', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'KPJ_Pracownik', 'deactivate' ) );
add_action( 'plugins_loaded', array( 'KPJ_Pracownik', 'get_instance' ) );