<?php
/**
 * This is all the functionality related to the back end of the site
 *
 * @return Code_Docs_Admin
 */

class Code_Docs_Admin
{
	/**
	 * Static property to hold our singleton instance
	 * @var Code_Docs_Admin
	 */
	static $instance = false;


	/**
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a Singleton
	 *
	 * @return Code_Docs_Admin
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'post_types' ) );
		add_action( 'init', array( $this, 'taxonomies' ) );
		add_action( 'admin_enqueue_scripts', array(	$this, 'scripts_styles' ), 10 );
		//add_action( 'manage_posts_custom_column', array( $this, 'display_columns' ), 10, 2 );
		//add_action( 'admin_menu', array( $this, 'remove_ui_items' ) );
		//add_action( 'restrict_manage_posts', array( $this, 'sort_drops' ) );
		//add_action( 'admin_menu', array( $this, 'sort_pages' ) );
		//add_action( 'wp_ajax_save_sort', array( $this, 'save_sort' ) );
		add_filter( 'post_link', array( $this, 'docs_post_link' ), 10, 3 );
		add_filter( 'post_type_link', array( $this, 'docs_post_link' ), 10, 3 );
		add_filter( 'term_link', array( $this, 'docs_term_link'	), 10, 3 );
		add_filter( 'enter_title_here', array( $this, 'title_field' ) );
		add_filter( 'manage_edit-docs_columns', array( $this, 'docs_columns' ) );
	}
	

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return Code_Docs_Admin
	 */
	public static function getInstance() {
		if ( ! self::$instance )
			self::$instance = new self;
		return self::$instance;
	}


	/**
	 * Scripts and stylesheets
	 *
	 * @return Code_Docs_Admin
	 */
	public function scripts_styles() {

		$screen = get_current_screen();

		if ( is_object($screen) && 'docs' == $screen->post_type ) :
			wp_enqueue_style( 'cdm-admin', plugins_url( '/css/cdm.admin.css', __FILE__ ), array(), null, 'all' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'cdm-admin', plugins_url( '/js/cdm.admin.js', __FILE__ ) , array( 'jquery' ), CDM_VER, true );
		endif;
	}


	/**
	 * remove default metabox
	 *
	 * @return Code_Docs_Admin
	 */
	public function remove_ui_items() {
		remove_meta_box( 'tagsdiv-doc-type', 'docs', 'side' );
	}


	/**
	 * change title field for CPTs
	 *
	 * @return Code_Docs_Admin
	 */
	public function title_field( $title ) {
		 $screen = get_current_screen();
		 if  ( 'docs' == $screen->post_type )
			  $title = 'Documentation Title';
		 return $title;
	}


	/**
	 * remove, register, and display columns
	 *
	 * @return Code_Docs_Admin
	 */
	public function docs_columns( $columns ) {

		// remove stuff
		unset( $columns['date'] );
		unset( $columns['author'] );
		unset( $columns['comments'] );
		unset( $columns['featured'] );
		unset( $columns['post_type'] );

		// now add the custom stuff
		$columns['cdm-version']	= 'Version';

		return $columns;

	}


	/**
	 * Column mods
	 *
	 * @return Code_Docs_Admin
	 */
	public function display_columns( $column, $post_id ) {
	
		$data = get_post_meta( $post_id, '_cdm_extra_info', true );
		
		if ( 'cdm-version' == $column && isset( $data['version'] ) ) :
			echo '<span class="cdm-version">' . $data['version'] . '</span>';
		endif;
	}

	/**
	 * set up taxonomies
	 *
	 * @return Code_Docs_Admin
	 */

	public function taxonomies() {

		$labels	= array(
			'name'							=> __( 'Types',								'cdm' ),
			'singular_name'					=> __( 'Type',								'cdm' ),
			'search_items'					=> __( 'Search Types',						'cdm' ),
			'popular_items'					=> __( 'Popular Types',						'cdm' ),
			'all_items'						=> __( 'All Types',							'cdm' ),
			'parent_item'					=> __( 'Parent Type',						'cdm' ),
			'parent_item_colon'				=> __( 'Parent Type:',						'cdm' ),
			'edit_item'						=> __( 'Edit Type',							'cdm' ),
			'update_item'					=> __( 'Update Type',						'cdm' ),
			'add_new_item'					=> __( 'Add New Type',						'cdm' ),
			'new_item_name'					=> __( 'New Type',							'cdm' ),
			'add_or_remove_items'			=> __( 'Add or remove Types',				'cdm' ),
			'choose_from_most_used'			=> __( 'Choose from the most used Types',	'cdm' ),
			'separate_items_with_commas'	=> __( 'Separate Types with commas',		'cdm' ),
		);

		$args = array(
			'labels'				=> $labels,
			'public'				=> true,
			'show_in_nav_menus'		=> true,
			'show_ui'				=> true,
			'publicly_queryable'	=> true,
			'exclude_from_search'	=> false,
			'hierarchical'			=> false,
			'query_var'				=> true,
			'show_admin_column'		=> true,
			'rewrite'				=> array( 'slug' => '%post_type%', 'with_front' => false ),
		);

		$args = apply_filters( 'cdm_custom_taxonomy_args', $args );

		register_taxonomy(
			'doc-type',
			'docs',
			$args
		);

	}
	/**
	 * set up CPT
	 *
	 * @return Code_Docs_Admin
	 */

	public function post_types() {

		$labels	= array(
			'name'					=> __( 'Documentation',				'cdm' ),
			'singular_name'			=> __( 'Item',						'cdm' ),
			'add_new'				=> __( 'Add New Item',				'cdm' ),
			'add_new_item'			=> __( 'Add New Item',				'cdm' ),
			'edit'					=> __( 'Edit Item',					'cdm' ),
			'edit_item'				=> __( 'Edit Item',					'cdm' ),
			'new_item'				=> __( 'New Item',					'cdm' ),
			'view'					=> __( 'View Item',					'cdm' ),
			'view_item'				=> __( 'View Item',					'cdm' ),
			'search_items'			=> __( 'Search Items',				'cdm' ),
			'not_found'				=> __( 'No Items found',			'cdm' ),
			'not_found_in_trash'	=> __( 'No Items found in Trash',	'cdm' ),
		);

		$args	= array(
			'labels'				=> $labels,
			'public'				=> true,
			'show_in_menu'			=> true,
			'show_in_nav_menus'		=> false,
			'show_ui'				=> true,
			'publicly_queryable'	=> true,
			'exclude_from_search'	=> false,
			'hierarchical'			=> false,
			'menu_position'			=> null,
			'capability_type'		=> 'post',
			'taxonomies'			=> array( 'doc-type' ),
			'query_var'				=> true,
			'menu_icon'				=> 'dashicons-welcome-widgets-menus',
			'rewrite'				=> array( 'slug' => 'docs/%doc-type%', 'with_front' => false ),
			'has_archive'			=> 'docs',
			'supports'				=> array( 'title', 'editor', 'excerpt' ),
		);

		$args = apply_filters( 'cdm_post_type_args', $args );

		register_post_type( 'docs', $args );
	}


	/**
	 * create dropdown for showing just flagged
	 *
	 * @return Code_Docs_Admin
	 */
	public function sort_drops() {

		if ( ! is_admin() )
			return;

		$screen = get_current_screen();

		if ( is_object( $screen ) && 'docs' == $screen->post_type ) :

			$terms	= get_terms( 'doc-type', 'hide_empty=0' );
			$choice = isset( $_GET['doc-type'] ) ? $_GET['doc-type'] : 'all';

			echo '<select name="doc-type">';
				echo '<option value="all" ' . selected( $choice, 'all', false ) . '>' . __( 'All Types', 'cdm' ) . '</option>';
				foreach ( $terms as $term ) :
					echo '<option value="' . $term->slug . '" ' . selected( $choice, $term->slug, false ) . '>' . $term->name . '</option>';
				endforeach;
			echo '</select>';
		endif;
	}


	/**
	 * run filter on sorting
	 *
	 * @return Code_Docs_Admin
	 */
	public function sort_filters( $vars ) {

		if ( ! is_admin() )
			return $vars;

		if ( ! isset( $_GET['post_type'] ) )
			return $vars;

		if ( ! isset( $_GET['doc-type'] ) )
			return $vars;

		if ( 'docs' == $_GET['post_type'] && 'all' == $_GET['doc-type'] )
			return $vars;

		// set our query terms
		$tax_query	= array(
			'taxonomy'	=> 'doc-type',
			'field'		=> 'slug',
			'terms'		=> $_GET['doc-type']
		);

		$vars = array_merge( $vars, array(
			'tax_query'	=> $tax_query
		));

		return $vars;

	}


	/**
	 * include doc type taxonomy in URL
	 *
	 * @return Code_Docs_Admin
	 */
	public function docs_post_link( $permalink, $post_id, $leavename ) {

		if ( strpos( $permalink, '%doc-type%') === false )
			return $permalink;

		// get post
		$post = get_post( $post_id );
		if ( ! $post )
			return $permalink;

		// set a base taxonomy term
		$taxonomy_slug = 'filters';

		// get taxonomy terms
		$terms = wp_get_object_terms( $post->ID, 'doc-type' );
		
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) && is_object( $terms[0] ) )
			$taxonomy_slug = $terms[0]->slug;

		return str_replace( '%doc-type%', $taxonomy_slug, $permalink );
	}


	/**
	 * include doc type in taxonomy URL
	 *
	 * @return Code_Docs_Admin
	 */
	public function docs_term_link( $term_link, $term, $taxonomy ) {

		if ( strpos( $term_link, '%post_type%' ) === false )
			return $term_link;

		$post_type = 'docs';

		return str_replace( '%post_type%', $post_type, $term_link );
	}


	/**
	 * Create submenu pages for sorting
	 *
	 * @return Code_Docs_Admin
	 */
	public function sort_pages() {
		add_submenu_page(
			'edit.php?post_type=docs',
			__( 'Sort Docs', 'cdm' ),
			__( 'Sort Docs', 'cdm' ),
			'edit_posts', 'sort-docs',
			array( $this, 'docs_sort_order' )
		);
	}


	/**
	 * load content for each sorting page
	 *
	 * @return Code_Docs_Admin
	 */
	public function docs_sort_order() {

		$items = $this->sort_query_args( 'docs' );

		echo '<div class="wrap custom-sort-wrap">';
			echo '<div class="icon32" id="icon-docs-manager"><br></div>';
			echo '<h2>' . __( 'Sort Docs', 'cdm' ) . ' <img src="' . admin_url() . 'images/loading.gif" id="loading-animation" /></h2>';
			echo '<ul id="custom-type-list">';
				foreach ( $items as $item ):
					echo '<li id="' . $item . '">' . get_the_title( $item ) . '</li>';
				endforeach;
			echo '</ul>';
		echo '</div>';
	}


	/**
	 * save sort order via JS
	 *
	 * @return Code_Docs_Admin
	 */
	public function save_sort() {
		global $wpdb; // WordPress database class

		$order	= explode( ',', $_POST['order'] );
		$count	= 0;

		foreach ( $order as $item_id ) :
			$wpdb->update( $wpdb->posts, array( 'menu_order' => $count ), array( 'ID' => $item_id ) );
			$count++;
		endforeach;
		
		die( 1 );
	}


	/**
	 * abstract query for sorting
	 *
	 * @return Code_Docs_Admin
	 */
	public function sort_query_args( $type ) {

		$args = array(
			'fields'		=> 'ids',
			'post_type'		=> $type,
			'post_status'	=> 'publish',
			'nopaging'		=> true,
			'order'			=> 'ASC',
			'orderby'		=> 'menu_order'
		);

		$items = get_posts( $args );

		return $items;
	}


/// end class
}


// Instantiate our class
$Code_Docs_Admin = Code_Docs_Admin::getInstance();
