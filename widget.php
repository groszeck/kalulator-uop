public function __construct() {
        parent::__construct(
            'kpj_calc_widget',
            __( 'KPJ Kalkulator Wynagrodze?', 'kpj-calc' ),
            array(
                'description' => __( 'Kalkulator wynagrodze? brutto ? netto', 'kpj-calc' ),
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        if ( $title ) {
            echo $args['before_title'];
            echo esc_html( apply_filters( 'widget_title', $title, $instance, $this->id_base ) );
            echo $args['after_title'];
        }

        echo wp_kses_post( do_shortcode( '[kpj_pracownik_kalkulator]' ) );

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $defaults = array(
            'title' => __( 'Kalkulator Wynagrodze?', 'kpj-calc' ),
        );
        $instance = wp_parse_args( (array) $instance, $defaults );

        $field_id   = $this->get_field_id( 'title' );
        $field_name = $this->get_field_name( 'title' );
        $title      = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
        ?>
        <p>
            <label for="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Tytu?:', 'kpj-calc' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" type="text" value="<?php echo $title; ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
        return $instance;
    }
}

function kpj_register_calc_widget() {
    if ( class_exists( 'KPJ_Calc_Widget' ) ) {
        register_widget( 'KPJ_Calc_Widget' );
    }
}
add_action( 'widgets_init', 'kpj_register_calc_widget' );