function kpj_pracownik_register_rest_routes() {
    $namespace = 'kpj-pracownik/v1';

    register_rest_route(
        $namespace,
        '/calculate',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'kpj_pracownik_handle_calculation_request',
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'args'                => array(
                'gross_amount'  => array(
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param ) && $param >= 0;
                    },
                    'sanitize_callback' => 'kpj_pracownik_sanitize_float',
                ),
                'contract_type' => array(
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return in_array( $param, array( 'uop', 'uz', 'uod' ), true );
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        )
    );

    register_rest_route(
        $namespace,
        '/compare',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'kpj_pracownik_handle_comparison_request',
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'args'                => array(
                'scenarios' => array(
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        if ( ! is_array( $param ) || empty( $param ) ) {
                            return false;
                        }
                        foreach ( $param as $item ) {
                            if (
                                ! isset( $item['gross_amount'], $item['contract_type'] ) ||
                                ! is_numeric( $item['gross_amount'] ) ||
                                ! in_array( $item['contract_type'], array( 'uop', 'uz', 'uod' ), true )
                            ) {
                                return false;
                            }
                        }
                        return true;
                    },
                    'sanitize_callback' => 'kpj_pracownik_sanitize_scenarios',
                ),
            ),
        )
    );

    register_rest_route(
        $namespace,
        '/export',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'kpj_pracownik_handle_export_request',
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'args'                => array(
                'export_type' => array(
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return in_array( $param, array( 'pdf', 'email' ), true );
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'results'     => array(
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return is_array( $param ) && ! empty( $param );
                    },
                    'sanitize_callback' => 'kpj_pracownik_sanitize_results',
                ),
                'email'       => array(
                    'required'          => false,
                    'validate_callback' => function( $param, $request ) {
                        if ( $request->get_param( 'export_type' ) === 'email' ) {
                            return is_string( $param ) && is_email( $param );
                        }
                        return true;
                    },
                    'sanitize_callback' => 'sanitize_email',
                ),
            ),
        )
    );
}

function kpj_pracownik_handle_calculation_request( WP_REST_Request $request ) {
    $gross    = floatval( $request->get_param( 'gross_amount' ) );
    $contract = sanitize_text_field( $request->get_param( 'contract_type' ) );
    try {
        $result = KPJ_Pracownik_Calculator::calculate( $gross, $contract );
        return rest_ensure_response( $result );
    } catch ( Exception $e ) {
        return new WP_REST_Response(
            array( 'error' => esc_html( $e->getMessage() ) ),
            400
        );
    }
}

function kpj_pracownik_handle_comparison_request( WP_REST_Request $request ) {
    $scenarios  = $request->get_param( 'scenarios' );
    $comparison = array();
    try {
        foreach ( $scenarios as $scenario ) {
            $gross    = floatval( $scenario['gross_amount'] );
            $contract = sanitize_text_field( $scenario['contract_type'] );
            $comparison[] = array(
                'input'  => array(
                    'gross_amount'  => $gross,
                    'contract_type' => $contract,
                ),
                'result' => KPJ_Pracownik_Calculator::calculate( $gross, $contract ),
            );
        }
        return rest_ensure_response( $comparison );
    } catch ( Exception $e ) {
        return new WP_REST_Response(
            array( 'error' => esc_html( $e->getMessage() ) ),
            400
        );
    }
}

function kpj_pracownik_handle_export_request( WP_REST_Request $request ) {
    $type    = sanitize_text_field( $request->get_param( 'export_type' ) );
    $results = $request->get_param( 'results' );
    $email   = $request->get_param( 'email' );
    try {
        if ( 'pdf' === $type ) {
            $file_url = KPJ_Pracownik_Exporter::export_pdf( $results );
            return rest_ensure_response( array( 'file_url' => esc_url_raw( $file_url ) ) );
        }
        if ( 'email' === $type ) {
            if ( ! is_email( $email ) ) {
                return new WP_REST_Response(
                    array( 'error' => esc_html__( 'Invalid email address', 'kpj-pracownik' ) ),
                    400
                );
            }
            $sent = KPJ_Pracownik_Exporter::export_email( $results, $email );
            return rest_ensure_response( array( 'success' => (bool) $sent ) );
        }
        return new WP_REST_Response(
            array( 'error' => esc_html__( 'Invalid export type', 'kpj-pracownik' ) ),
            400
        );
    } catch ( Exception $e ) {
        return new WP_REST_Response(
            array( 'error' => esc_html( $e->getMessage() ) ),
            500
        );
    }
}

function kpj_pracownik_sanitize_float( $value ) {
    return floatval( $value );
}

function kpj_pracownik_sanitize_scenarios( $value ) {
    $sanitized = array();
    if ( is_array( $value ) ) {
        foreach ( $value as $item ) {
            $gross    = isset( $item['gross_amount'] ) ? floatval( $item['gross_amount'] ) : 0;
            $contract = isset( $item['contract_type'] ) ? sanitize_text_field( $item['contract_type'] ) : '';
            $sanitized[] = array(
                'gross_amount'  => $gross,
                'contract_type' => $contract,
            );
        }
    }
    return $sanitized;
}

function kpj_pracownik_sanitize_results( $value ) {
    $sanitized = array();
    if ( is_array( $value ) ) {
        foreach ( $value as $item ) {
            if ( is_array( $item ) ) {
                $row = array();
                foreach ( $item as $key => $val ) {
                    if ( is_scalar( $val ) ) {
                        $row[ $key ] = sanitize_text_field( $val );
                    } elseif ( is_array( $val ) ) {
                        $row[ $key ] = wp_json_encode( $val );
                    }
                }
                $sanitized[] = $row;
            }
        }
    }
    return $sanitized;
}