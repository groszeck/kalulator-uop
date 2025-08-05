const TRANSIENT_KEY        = 'kpj_dashboard_stats';
    const TRANSIENT_EXPIRATION = 300; // 5 minutes

    protected static $contract_type_labels = array(
        'UoP' => 'UoP',
        'UZ'  => 'UZ',
        'UoD' => 'UoD',
    );

    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_dashboard_widget' ) );
    }

    public static function register_dashboard_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_add_dashboard_widget(
            'kpj_dashboard_widget',
            esc_html__( 'KPJ Kalkulator Stats', 'kpj-pracownik-kalkulator' ),
            array( __CLASS__, 'render_dashboard_widget' )
        );
    }

    public static function render_dashboard_widget() {
        $stats = self::fetch_dashboard_stats();
        echo self::format_dashboard_stats( $stats );
    }

    protected static function fetch_dashboard_stats() {
        $cached = get_transient( self::TRANSIENT_KEY );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'kpj_calculations';
        $stats = array(
            'total_calculations' => 0,
            'last_week'          => 0,
            'contract_types'     => array_fill_keys( array_keys( self::$contract_type_labels ), 0 ),
        );

        $like      = $wpdb->esc_like( $table );
        $show_table = $wpdb->get_var(
            $wpdb->prepare( "SHOW TABLES LIKE %s", $like )
        );

        if ( $show_table === $table ) {
            $stats['total_calculations'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
            $stats['last_week'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM `{$table}` WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );

            $results = $wpdb->get_results(
                "SELECT `contract_type`, COUNT(*) as cnt FROM `{$table}` GROUP BY `contract_type`",
                ARRAY_A
            );

            if ( ! empty( $results ) ) {
                foreach ( $results as $row ) {
                    $type = $row['contract_type'];
                    $cnt  = (int) $row['cnt'];
                    if ( isset( $stats['contract_types'][ $type ] ) ) {
                        $stats['contract_types'][ $type ] = $cnt;
                    }
                }
            }
        }

        set_transient( self::TRANSIENT_KEY, $stats, self::TRANSIENT_EXPIRATION );

        return $stats;
    }

    protected static function format_dashboard_stats( $stats ) {
        ob_start();
        ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__( 'Metric', 'kpj-pracownik-kalkulator' ); ?></th>
                    <th><?php echo esc_html__( 'Value', 'kpj-pracownik-kalkulator' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo esc_html__( 'Total Calculations', 'kpj-pracownik-kalkulator' ); ?></td>
                    <td><?php echo esc_html( $stats['total_calculations'] ); ?></td>
                </tr>
                <tr>
                    <td><?php echo esc_html__( 'Calculations Last 7 Days', 'kpj-pracownik-kalkulator' ); ?></td>
                    <td><?php echo esc_html( $stats['last_week'] ); ?></td>
                </tr>
                <?php foreach ( $stats['contract_types'] as $type => $count ) : 
                    $raw_label        = isset( self::$contract_type_labels[ $type ] ) ? self::$contract_type_labels[ $type ] : $type;
                    $translated_label = esc_html__( $raw_label, 'kpj-pracownik-kalkulator' );
                    $label            = sprintf(
                        esc_html__( 'Contract Type: %s', 'kpj-pracownik-kalkulator' ),
                        $translated_label
                    );
                ?>
                    <tr>
                        <td><?php echo $label; ?></td>
                        <td><?php echo esc_html( $count ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
}

KPJ_Dashboard_Widget_Module::init();