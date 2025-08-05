function kpj_register_gutenberg_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	$dir        = plugin_dir_path( __FILE__ );
	$asset_file = $dir . 'build/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = include $asset_file;

	wp_register_script(
		'kpj-pracownik-kalkulator-editor-script',
		plugins_url( 'build/index.js', __FILE__ ),
		$asset['dependencies'],
		$asset['version']
	);

	wp_register_style(
		'kpj-pracownik-kalkulator-editor-style',
		plugins_url( 'build/index.css', __FILE__ ),
		array( 'wp-edit-blocks' ),
		filemtime( $dir . 'build/index.css' )
	);

	wp_register_style(
		'kpj-pracownik-kalkulator-style',
		plugins_url( 'build/style-index.css', __FILE__ ),
		array(),
		filemtime( $dir . 'build/style-index.css' )
	);

	wp_register_script(
		'kpj-pracownik-kalkulator-script',
		plugins_url( 'build/script-index.js', __FILE__ ),
		array(),
		filemtime( $dir . 'build/script-index.js' ),
		true
	);

	register_block_type(
		'kpj/pracownik-kalkulator',
		array(
			'editor_script' => 'kpj-pracownik-kalkulator-editor-script',
			'editor_style'  => 'kpj-pracownik-kalkulator-editor-style',
			'style'         => 'kpj-pracownik-kalkulator-style',
			'script'        => 'kpj-pracownik-kalkulator-script',
		)
	);
}
add_action( 'init', 'kpj_register_gutenberg_block' );

function kpj_register_block_category( $categories, $post ) {
	if ( empty( $post ) || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
		return $categories;
	}

	$new = array(
		array(
			'slug'  => 'kpj-kalkulator',
			'title' => __( 'KPJ Kalkulator', 'kpj-kalkulator' ),
		),
	);

	return array_merge( $new, $categories );
}
add_filter( 'block_categories_all', 'kpj_register_block_category', 10, 2 );