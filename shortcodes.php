function kpj_register_shortcodes() {
    add_shortcode( 'kpj_kalkulator', 'kpj_render_calculator_shortcode' );
    add_shortcode( 'kpj_porownanie', 'kpj_render_comparison_shortcode' );
}
add_action( 'init', 'kpj_register_shortcodes' );

function kpj_enqueue_shortcode_assets() {
    if ( ! is_singular() ) {
        return;
    }
    global $post;
    if ( ! has_shortcode( $post->post_content, 'kpj_kalkulator' ) && ! has_shortcode( $post->post_content, 'kpj_porownanie' ) ) {
        return;
    }
    $version = defined( 'KPJ_PLUGIN_VERSION' ) ? KPJ_PLUGIN_VERSION : false;
    wp_enqueue_style( 'kpj-shortcodes-style', KPJ_PLUGIN_URL . 'assets/css/shortcodes.css', array(), $version );
    wp_enqueue_script( 'kpj-shortcodes-script', KPJ_PLUGIN_URL . 'assets/js/shortcodes.js', array( 'jquery' ), $version, true );
    wp_localize_script( 'kpj-shortcodes-script', 'KPJData', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'kpj_shortcode_nonce' ),
        'i18n'     => array(
            'calc_error' => __( 'Wyst?pi? b??d podczas oblicze?.', 'kpj' ),
        ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'kpj_enqueue_shortcode_assets' );

function kpj_render_calculator_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'type'   => 'uop',
        'salary' => '',
    ), $atts, 'kpj_kalkulator' );
    $type   = sanitize_text_field( $atts['type'] );
    $salary = floatval( $atts['salary'] );
    static $instance = 0;
    $instance++;
    $suffix = uniqid();
    $salary_id       = 'kpj-salary-' . $suffix;
    $contract_type_id = 'kpj-contract-type-' . $suffix;
    ob_start();
    ?>
    <div class="kpj-calculator" data-type="<?php echo esc_attr( $type ); ?>" data-salary="<?php echo esc_attr( $salary ); ?>">
        <form class="kpj-calculator-form">
            <label for="<?php echo esc_attr( $salary_id ); ?>"><?php esc_html_e( 'Kwota brutto', 'kpj' ); ?>:</label>
            <input type="number" id="<?php echo esc_attr( $salary_id ); ?>" name="salary" value="<?php echo esc_attr( $salary ); ?>" min="0" step="0.01" required>
            <label for="<?php echo esc_attr( $contract_type_id ); ?>"><?php esc_html_e( 'Typ umowy', 'kpj' ); ?>:</label>
            <select id="<?php echo esc_attr( $contract_type_id ); ?>" name="type">
                <option value="uop" <?php selected( $type, 'uop' ); ?>><?php esc_html_e( 'Umowa o prac?', 'kpj' ); ?></option>
                <option value="uz" <?php selected( $type, 'uz' ); ?>><?php esc_html_e( 'Umowa zlecenie', 'kpj' ); ?></option>
                <option value="uod" <?php selected( $type, 'uod' ); ?>><?php esc_html_e( 'Umowa o dzie?o', 'kpj' ); ?></option>
            </select>
            <button type="submit" class="kpj-calc-submit"><?php esc_html_e( 'Oblicz', 'kpj' ); ?></button>
        </form>
        <div class="kpj-calc-results"></div>
    </div>
    <?php
    return ob_get_clean();
}

function kpj_render_comparison_shortcode( $atts ) {
    $atts    = shortcode_atts( array(
        'salary' => '',
        'types'  => 'uop,uz,uod',
    ), $atts, 'kpj_porownanie' );
    $salary  = floatval( $atts['salary'] );
    $raw     = explode( ',', $atts['types'] );
    $allowed = array( 'uop', 'uz', 'uod' );
    $types   = array();
    foreach ( $raw as $type ) {
        $type = sanitize_text_field( $type );
        if ( in_array( $type, $allowed, true ) ) {
            $types[] = $type;
        }
    }
    if ( empty( $types ) ) {
        $types = $allowed;
    }
    ob_start();
    ?>
    <div class="kpj-comparison" data-salary="<?php echo esc_attr( $salary ); ?>">
        <table class="kpj-comparison-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Typ umowy', 'kpj' ); ?></th>
                    <th><?php esc_html_e( 'Kwota netto', 'kpj' ); ?></th>
                    <th><?php esc_html_e( 'Koszt pracodawcy', 'kpj' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $types as $type ) : ?>
                <tr data-type="<?php echo esc_attr( $type ); ?>">
                    <td><?php echo esc_html( kpj_get_contract_label( $type ) ); ?></td>
                    <td class="kpj-netto"><?php echo esc_html( kpj_calculate_net_amount( $salary, $type ) ); ?></td>
                    <td class="kpj-cost"><?php echo esc_html( kpj_calculate_employer_cost( $salary, $type ) ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

function kpj_get_contract_label( $type ) {
    switch ( $type ) {
        case 'uop':
            return __( 'Umowa o prac?', 'kpj' );
        case 'uz':
            return __( 'Umowa zlecenie', 'kpj' );
        case 'uod':
            return __( 'Umowa o dzie?o', 'kpj' );
        default:
            return '';
    }
}