const CLEANUP_RETENTION_DAYS = 7;
    const CLEANUP_HOOK = 'kpj_exports_cleanup';

    public static function init() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_export_scripts' ] );
        add_action( 'wp_ajax_kpj_export_pdf', [ __CLASS__, 'handle_export_pdf' ] );
        add_action( 'wp_ajax_nopriv_kpj_export_pdf', [ __CLASS__, 'handle_export_pdf' ] );
        add_action( 'wp_ajax_kpj_send_email_report', [ __CLASS__, 'handle_send_email_report' ] );
        add_action( 'wp_ajax_nopriv_kpj_send_email_report', [ __CLASS__, 'handle_send_email_report' ] );

        add_action( 'init', [ __CLASS__, 'maybe_schedule_cleanup' ] );
        add_action( self::CLEANUP_HOOK, [ __CLASS__, 'cleanup_exports' ] );
    }

    public static function enqueue_export_scripts() {
        wp_enqueue_script(
            'kpj-export',
            plugin_dir_url( __FILE__ ) . 'assets/js/export.js',
            [ 'jquery' ],
            defined( 'KPJ_PLUGIN_VERSION' ) ? KPJ_PLUGIN_VERSION : false,
            true
        );
        wp_localize_script(
            'kpj-export',
            'kpjExport',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'kpj_export_nonce' ),
            ]
        );
    }

    public static function prepare_export_data( $input ) {
        return [
            'contract_type' => sanitize_text_field( $input['contract_type'] ?? '' ),
            'gross_salary'  => floatval( $input['gross_salary'] ?? 0 ),
            'zus_employer'  => floatval( $input['zus_employer'] ?? 0 ),
            'zus_employee'  => floatval( $input['zus_employee'] ?? 0 ),
            'tax_base'      => floatval( $input['tax_base'] ?? 0 ),
            'tax'           => floatval( $input['tax'] ?? 0 ),
            'net_salary'    => floatval( $input['net_salary'] ?? 0 ),
        ];
    }

    public static function generate_pdf_report( $data ) {
        if ( ! class_exists( 'Dompdf\Dompdf' ) ) {
            return new WP_Error( 'dompdf_missing', __( 'Dompdf library not found.', 'kpj' ) );
        }

        try {
            $html  = '<h1>' . esc_html__( 'Raport Wynagrodzenia', 'kpj' ) . '</h1>';
            $html .= '<table>';
            foreach ( $data as $key => $value ) {
                $label = esc_html( ucwords( str_replace( '_', ' ', $key ) ) );
                $html  .= '<tr><td>' . $label . ':</td><td>' . esc_html( $value ) . '</td></tr>';
            }
            $html .= '</table>';

            $dompdf = new Dompdf\Dompdf();
            $dompdf->loadHtml( $html );
            $dompdf->setPaper( 'A4', 'portrait' );
            $dompdf->render();
            $output = $dompdf->output();
        } catch ( Exception $e ) {
            return new WP_Error( 'pdf_generation_failed', $e->getMessage() );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;
        if ( ! $wp_filesystem ) {
            return new WP_Error( 'filesystem_init_failed', __( 'Unable to initialize filesystem.', 'kpj' ) );
        }

        $upload     = wp_upload_dir();
        $export_dir = trailingslashit( $upload['basedir'] ) . 'kpj-exports';
        if ( ! $wp_filesystem->is_dir( $export_dir ) ) {
            if ( ! $wp_filesystem->mkdir( $export_dir, FS_CHMOD_DIR ) ) {
                return new WP_Error( 'mkdir_failed', __( 'Failed to create export directory.', 'kpj' ) );
            }
        }

        $filename  = 'report-' . time() . '.pdf';
        $file_path = trailingslashit( $export_dir ) . $filename;
        $written   = $wp_filesystem->put_contents( $file_path, $output, FS_CHMOD_FILE );
        if ( $written === false ) {
            return new WP_Error( 'file_write_failed', __( 'Failed to write PDF file.', 'kpj' ) );
        }

        $url = trailingslashit( $upload['baseurl'] ) . 'kpj-exports/' . $filename;

        return [
            'path' => $file_path,
            'url'  => $url,
        ];
    }

    public static function send_email_report( $email, $data ) {
        $export = self::generate_pdf_report( $data );
        if ( is_wp_error( $export ) ) {
            return $export;
        }

        $subject = __( 'Raport Wynagrodzenia', 'kpj' );
        $message = __( 'W za??czeniu przesy?am raport wynagrodzenia.', 'kpj' );
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        $sent = wp_mail( $email, $subject, $message, $headers, [ $export['path'] ] );
        if ( ! $sent ) {
            return new WP_Error( 'email_send_failed', __( 'Wyst?pi? b??d podczas wysy?ki email.', 'kpj' ) );
        }
        return true;
    }

    public static function handle_export_pdf() {
        check_ajax_referer( 'kpj_export_nonce', 'nonce' );
        $data   = self::prepare_export_data( $_POST );
        $export = self::generate_pdf_report( $data );

        if ( is_wp_error( $export ) ) {
            wp_send_json_error( $export->get_error_message() );
        }
        wp_send_json_success( [ 'url' => $export['url'] ] );
    }

    public static function handle_send_email_report() {
        check_ajax_referer( 'kpj_export_nonce', 'nonce' );
        $email = sanitize_email( $_POST['email'] ?? '' );
        if ( ! is_email( $email ) ) {
            wp_send_json_error( __( 'Nieprawid?owy adres email.', 'kpj' ) );
        }

        $data = self::prepare_export_data( $_POST );
        $result = self::send_email_report( $email, $data );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }
        wp_send_json_success( __( 'Email wys?any pomy?lnie.', 'kpj' ) );
    }

    public static function maybe_schedule_cleanup() {
        if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
            wp_schedule_event( time(), 'daily', self::CLEANUP_HOOK );
        }
    }

    public static function cleanup_exports() {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;
        if ( ! $wp_filesystem ) {
            return;
        }

        $upload     = wp_upload_dir();
        $export_dir = trailingslashit( $upload['basedir'] ) . 'kpj-exports';
        if ( ! $wp_filesystem->is_dir( $export_dir ) ) {
            return;
        }

        $files = $wp_filesystem->dirlist( $export_dir );
        if ( empty( $files ) ) {
            return;
        }

        $now    = time();
        $expire = self::CLEANUP_RETENTION_DAYS * DAY_IN_SECONDS;

        foreach ( $files as $file => $attrs ) {
            if ( ! empty( $attrs['mtime'] ) ) {
                $file_time = $attrs['mtime'];
            } else {
                $file_time = $now;
            }
            if ( ( $now - $file_time ) > $expire ) {
                $file_path = trailingslashit( $export_dir ) . $file;
                $wp_filesystem->delete( $file_path );
            }
        }
    }
}

Kpj_Export_Module::init();