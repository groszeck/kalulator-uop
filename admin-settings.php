function kpj_register_admin_menu() {
    add_options_page(
        __( 'KPJ Pracownik Kalkulator Settings', 'kpj-pracownik-kalkulator' ),
        __( 'KPJ Kalkulator', 'kpj-pracownik-kalkulator' ),
        'manage_options',
        'kpj_pracownik_kalkulator_settings',
        'kpj_render_settings_page'
    );
}

function kpj_register_settings() {
    register_setting(
        'kpj_pracownik_kalkulator_options_group',
        'kpj_pracownik_kalkulator_settings',
        'kpj_sanitize_settings_input'
    );

    add_settings_section(
        'kpj_general_section',
        __( 'General Settings', 'kpj-pracownik-kalkulator' ),
        'kpj_general_section_callback',
        'kpj_pracownik_kalkulator_settings'
    );

    add_settings_field(
        'default_contract',
        __( 'Default Contract Type', 'kpj-pracownik-kalkulator' ),
        'kpj_field_default_contract_render',
        'kpj_pracownik_kalkulator_settings',
        'kpj_general_section'
    );
    add_settings_field(
        'enable_pdf',
        __( 'Enable PDF Export', 'kpj-pracownik-kalkulator' ),
        'kpj_field_enable_pdf_render',
        'kpj_pracownik_kalkulator_settings',
        'kpj_general_section'
    );
    add_settings_field(
        'enable_email',
        __( 'Enable Email Export', 'kpj-pracownik-kalkulator' ),
        'kpj_field_enable_email_render',
        'kpj_pracownik_kalkulator_settings',
        'kpj_general_section'
    );
    add_settings_field(
        'email_recipient',
        __( 'Email Recipient', 'kpj-pracownik-kalkulator' ),
        'kpj_field_email_recipient_render',
        'kpj_pracownik_kalkulator_settings',
        'kpj_general_section'
    );
    add_settings_field(
        'share_social',
        __( 'Enable Social Sharing', 'kpj-pracownik-kalkulator' ),
        'kpj_field_share_social_render',
        'kpj_pracownik_kalkulator_settings',
        'kpj_general_section'
    );
}

function kpj_general_section_callback() {
    echo '<p>' . esc_html__( 'Configure settings for the salary calculator.', 'kpj-pracownik-kalkulator' ) . '</p>';
}

function kpj_field_default_contract_render() {
    $options = kpj_get_settings();
    $value   = $options['default_contract'];
    $types   = array(
        'UoP' => __( 'Umowa o Prac?', 'kpj-pracownik-kalkulator' ),
        'UZ'  => __( 'Umowa Zlecenie', 'kpj-pracownik-kalkulator' ),
        'UoD' => __( 'Umowa o Dzie?o', 'kpj-pracownik-kalkulator' ),
    );
    echo '<select name="kpj_pracownik_kalkulator_settings[default_contract]">';
    foreach ( $types as $key => $label ) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr( $key ),
            selected( $value, $key, false ),
            esc_html( $label )
        );
    }
    echo '</select>';
}

function kpj_field_enable_pdf_render() {
    $options = kpj_get_settings();
    $checked = $options['enable_pdf'] ? 'checked' : '';
    printf(
        '<input type="checkbox" name="kpj_pracownik_kalkulator_settings[enable_pdf]" value="1" %s />',
        esc_attr( $checked )
    );
}

function kpj_field_enable_email_render() {
    $options = kpj_get_settings();
    $checked = $options['enable_email'] ? 'checked' : '';
    printf(
        '<input type="checkbox" name="kpj_pracownik_kalkulator_settings[enable_email]" value="1" %s />',
        esc_attr( $checked )
    );
}

function kpj_field_email_recipient_render() {
    $options = kpj_get_settings();
    printf(
        '<input type="email" name="kpj_pracownik_kalkulator_settings[email_recipient]" value="%s" class="regular-text" />',
        esc_attr( $options['email_recipient'] )
    );
}

function kpj_field_share_social_render() {
    $options = kpj_get_settings();
    $checked = $options['share_social'] ? 'checked' : '';
    printf(
        '<input type="checkbox" name="kpj_pracownik_kalkulator_settings[share_social]" value="1" %s />',
        esc_attr( $checked )
    );
}

function kpj_sanitize_settings_input( $input ) {
    $sanitized = array();
    $defaults  = kpj_get_default_settings();

    if ( isset( $input['default_contract'] ) && in_array( $input['default_contract'], array( 'UoP', 'UZ', 'UoD' ), true ) ) {
        $sanitized['default_contract'] = $input['default_contract'];
    } else {
        $sanitized['default_contract'] = $defaults['default_contract'];
    }

    $sanitized['enable_pdf']   = ! empty( $input['enable_pdf'] ) ? 1 : 0;
    $sanitized['enable_email'] = ! empty( $input['enable_email'] ) ? 1 : 0;
    $sanitized['share_social'] = ! empty( $input['share_social'] ) ? 1 : 0;

    if ( ! empty( $input['email_recipient'] ) && is_email( $input['email_recipient'] ) ) {
        $sanitized['email_recipient'] = sanitize_email( $input['email_recipient'] );
    } else {
        add_settings_error(
            'kpj_pracownik_kalkulator_settings',
            'invalid_email',
            __( 'Please enter a valid email address.', 'kpj-pracownik-kalkulator' ),
            'error'
        );
        $sanitized['email_recipient'] = $defaults['email_recipient'];
    }

    return $sanitized;
}

function kpj_get_default_settings() {
    return array(
        'default_contract' => 'UoP',
        'enable_pdf'       => 0,
        'enable_email'     => 0,
        'email_recipient'  => '',
        'share_social'     => 0,
    );
}

function kpj_get_settings() {
    $options = get_option( 'kpj_pracownik_kalkulator_settings', array() );
    return wp_parse_args( $options, kpj_get_default_settings() );
}

function kpj_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'KPJ Pracownik Kalkulator Settings', 'kpj-pracownik-kalkulator' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'kpj_pracownik_kalkulator_options_group' );
            do_settings_sections( 'kpj_pracownik_kalkulator_settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}