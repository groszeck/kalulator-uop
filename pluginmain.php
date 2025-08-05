function kpjpk_activate() {
    // Add activation tasks here (e.g., create default options).
    // Flush rewrite rules if custom post types or endpoints are registered in future.
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'kpjpk_activate' );

/**
 * Deactivation hook callback.
 */
function kpjpk_deactivate() {
    // Add deactivation tasks here (e.g., clear scheduled hooks).
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'kpjpk_deactivate' );

/**
 * Load plugin textdomain for translations.
 */
function kpjpk_load_textdomain() {
    load_plugin_textdomain(
        KPJPK_TEXTDOMAIN,
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}

/**
 * Initialize plugin: load textdomain, register shortcodes, hooks.
 */
function kpjpk_init() {
    kpjpk_load_textdomain();

    // Register shortcodes, custom post types, hooks, etc.
    // Example: add_shortcode( 'kpj_calculator', 'kpjpk_render_calculator' );
}
add_action( 'init', 'kpjpk_init' );

/**
 * Enqueue global frontend and admin assets.
 */
function kpjpk_enqueue_global_assets() {
    // Styles
    wp_register_style(
        'kpjpk-styles',
        KPJPK_PLUGIN_URL . 'assets/css/kpjpk-styles.css',
        array(),
        KPJPK_VERSION
    );
    wp_enqueue_style( 'kpjpk-styles' );

    // Scripts
    wp_register_script(
        'kpjpk-scripts',
        KPJPK_PLUGIN_URL . 'assets/js/kpjpk-scripts.js',
        array( 'jquery' ),
        KPJPK_VERSION,
        true
    );
    wp_enqueue_script( 'kpjpk-scripts' );

    // Localize script with dynamic data
    wp_localize_script(
        'kpjpk-scripts',
        'kpjpkData',
        array(
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'kpjpk_nonce' ),
            'i18nCalc'  => array(
                'netto'   => __( 'NETTO', KPJPK_TEXTDOMAIN ),
                'brutto'  => __( 'BRUTTO', KPJPK_TEXTDOMAIN ),
            ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'kpjpk_enqueue_global_assets' );
add_action( 'admin_enqueue_scripts', 'kpjpk_enqueue_global_assets' );