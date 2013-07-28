<?php
/**
 * This is all the functionality related to the back end of the site
 *
 * @return Documentation_Manager_Admin
 */

class Documentation_Manager_Admin
{
	/**
	 * Static property to hold our singleton instance
	 * @var Documentation_Manager_Admin
	 */
	static $instance = false;


	/**
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a Singleton
	 *
	 * @return Documentation_Manager_Admin
	 */
	private function __construct() {
		add_action      ( 'init',                                       array( $this, 'post_types'              )           );
		add_action      ( 'admin_enqueue_scripts',                      array( $this, 'scripts_styles'          ),  10      );
		add_action      ( 'manage_posts_custom_column',                 array( $this, 'display_columns'         ),  10, 2   );
		add_action		( 'admin_menu',									array( $this, 'remove_ui_items'			) 			);
		add_action		( 'restrict_manage_posts',						array( $this, 'sort_drops'				)           );
		add_action		( 'admin_menu',									array( $this, 'sort_pages'				)			);
		add_action		( 'wp_ajax_save_sort',							array( $this, 'save_sort'				) 			);

		add_filter		( 'post_link',									array( $this, 'docs_post_link'			),	10,	3	);
		add_filter		( 'post_type_link',								array( $this, 'docs_post_link'			),	10,	3	);
		add_filter		( 'term_link',									array( $this, 'docs_term_link'			),	10,	3	);
		add_filter      ( 'enter_title_here',                           array( $this, 'title_field'             )           );
		add_filter      ( 'manage_edit-docs_columns',					array( $this, 'docs_columns'			)           );

	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return Documentation_Manager_Admin
	 */

	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Scripts and stylesheets
	 *
	 * @return Documentation_Manager_Admin
	 */

	public function scripts_styles() {

		$screen = get_current_screen();

		if ( is_object($screen) && 'docs' == $screen->post_type ) :

			wp_enqueue_style( 'dgm-admin', plugins_url('/css/dgm.admin.css', __FILE__), array(), DGM_VER, 'all' );

			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script( 'dgm-admin', plugins_url('/js/dgm.admin.js', __FILE__) , array('jquery'), DGM_VER, true );

		endif;

	}

	/**
	 * remove default metabox
	 *
	 * @return Documentation_Manager_Admin
	 */

	public function remove_ui_items() {
		remove_meta_box( 'tagsdiv-doc-type', 'docs', 'side' );

	}

	/**
	 * change title field for CPTs
	 *
	 * @return Documentation_Manager_Admin
	 */

	public function title_field( $title ) {
		 $screen = get_current_screen();

		 if  ( 'docs' == $screen->post_type ) :
			  $title = 'Documentation Title';
		 endif;

		 return $title;
	}

	/**
	 * register and display columns
	 *
	 * @return Documentation_Manager_Admin
	 */

	public function docs_columns( $columns ) {

		// remove stuff
		unset($columns['date']);
		unset($columns['author']);
		unset($columns['comments']);
		unset($columns['featured']);
		unset($columns['post_type']);

		// now add the custom stuff
		$columns['dgm-version']	= 'Version';

		return $columns;

	}

	/**
	 * Column mods
	 *
	 * @return Documentation_Manager_Admin
	 */

	public function display_columns( $column, $post_id ) {

		switch ( $column ) {

		case 'dgm-version':
			$data	= get_post_meta( $post_id, '_dgm_extra_info', true );
			if ( isset( $data['version'] ) )
				echo '<span class="dgm-version">'.$data['version'].'</span>';

			break;


		// end all case breaks
		}

	}

	/**
	 * set up CPT and taxonomies
	 *
	 * @return Documentation_Manager_Admin
	 */

	public function post_types() {
		register_taxonomy(
			'doc-type',
			array( 'docs' ),
			array(
				'public'				=> true,
				'show_in_nav_menus'		=> true,
				'show_ui'				=> true,
				'publicly_queryable'	=> true,
				'exclude_from_search'	=> false,
				'hierarchical'			=> false,
				'query_var'				=> true,
				'show_admin_column'		=> true,
				'rewrite'           	=> array( 'slug' => '%post_type%', 'with_front' => false ),
				'labels'				=> array(
					'name'							=> __('Types',								'dgm' ),
					'singular_name'					=> __('Type',								'dgm' ),
					'search_items'					=> __('Search Types',						'dgm' ),
					'popular_items'					=> __('Popular Types',						'dgm' ),
					'all_items'						=> __('All Types',							'dgm' ),
					'parent_item'					=> __('Parent Type',						'dgm' ),
					'parent_item_colon'				=> __('Parent Type:',						'dgm' ),
					'edit_item'						=> __('Edit Type',							'dgm' ),
					'update_item'					=> __('Update Type',						'dgm' ),
					'add_new_item'					=> __('Add New Type',						'dgm' ),
					'new_item_name'					=> __('New Type',							'dgm' ),
					'add_or_remove_items'			=> __('Add or remove Types',				'dgm' ),
					'choose_from_most_used'			=> __('Choose from the most used Types',	'dgm' ),
					'separate_items_with_commas'	=> __('Separate Types with commas',			'dgm' ),
				),
			)
		);
		register_post_type( 'docs',
			array(
				'labels'    => array(
					'name'                  => __( 'Documentation',				'dgm' ),
					'singular_name'         => __( 'Item',                     	'dgm' ),
					'add_new'               => __( 'Add New Item',              'dgm' ),
					'add_new_item'          => __( 'Add New Item',              'dgm' ),
					'edit'                  => __( 'Edit Item',                 'dgm' ),
					'edit_item'             => __( 'Edit Item',                 'dgm' ),
					'new_item'              => __( 'New Item',                  'dgm' ),
					'view'                  => __( 'View Item',                 'dgm' ),
					'view_item'             => __( 'View Item',                 'dgm' ),
					'search_items'          => __( 'Search Items',              'dgm' ),
					'not_found'             => __( 'No Items found',            'dgm' ),
					'not_found_in_trash'    => __( 'No Items found in Trash',   'dgm' ),
				),
				'public'    => true,
					'show_in_menu'          => true,
					'show_in_nav_menus'     => false,
					'show_ui'               => true,
					'publicly_queryable'    => true,
					'exclude_from_search'   => false,
				'hierarchical'      => false,
				'menu_position'     => null,
				'capability_type'   => 'post',
				'taxonomies'		=> array( 'doc-type' ),
				'query_var'         => true,
				'menu_icon'         => plugins_url('/img/menu-docs.png', __FILE__),
				'rewrite'           => array( 'slug' => 'docs/%doc-type%', 'with_front' => false ),
				'has_archive'       => 'docs',
				'supports'          => array( 'title', 'editor' ),
			)
		);
		// set taxonomy registration
		register_taxonomy_for_object_type( 'docs', 'doc-type' );

	}


	/**
	 * create dropdown for showing just flagged
	 *
	 * @return Documentation_Manager_Admin
	 */

	public function sort_drops() {

		if ( !is_admin() )
			return;

		$screen = get_current_screen();

		if ( is_object( $screen ) && 'docs' == $screen->post_type ) :

			$terms	= get_terms( 'doc-type', 'hide_empty=0' );
			$choice = isset( $_GET['doc-type'] ) ? $_GET['doc-type'] : 'all';

			echo '<select name="doc-type">';
				echo '<option value="all" '.selected( $choice, 'all', false ).'>All Types</option>';
				foreach ( $terms as $term ) :
					echo '<option value="'.$term->slug.'" '.selected( $choice, $term->slug, false ).'>'.$term->name.'</option>';
				endforeach;
			echo '</select>';

		endif;


	}

	/**
	 * run filter on sorting
	 *
	 * @return Documentation_Manager_Admin
	 */

	public function sort_filters( $vars ) {

		if ( !is_admin() )
			return $vars;

		if ( !isset( $_GET['post_type'] ) )
			return $vars;

		if ( !isset( $_GET['doc-type'] ) )
			return $vars;

		if ( $_GET['post_type'] == 'docs' && $_GET['doc-type'] == 'all' )
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
	 * @return Documentation_Manager_Admin
	 */

	public function docs_post_link( $permalink, $post_id, $leavename ) {

		if ( strpos( $permalink, '%doc-type%') === false )
			return $permalink;

		// get post
		$post	= get_post( $post_id );
		if ( !$post )
			return $permalink;

		// set a base taxonomy term
		$taxonomy_slug = 'filters';

		// get taxonomy terms
		$terms	= wp_get_object_terms( $post->ID, 'doc-type' );
		if (!is_wp_error( $terms ) && !empty( $terms ) && is_object( $terms[0] ) )
			$taxonomy_slug = $terms[0]->slug;

		return str_replace('%doc-type%', $taxonomy_slug, $permalink );

	}

	/**
	 * include doc type in taxonomy URL
	 *
	 * @return Documentation_Manager_Admin
	 */

	public function docs_term_link( $term_link, $term, $taxonomy ) {

		if ( strpos( $term_link, '%post_type%') === false )
			return $term_link;

		$post_type = 'docs';

		return str_replace('%post_type%', 'docs', $term_link );

	}


	/**
	 * Create submenu pages for sorting
	 *
	 * @return Reaktiv_Admin
	 */

	public function sort_pages() {

		add_submenu_page('edit.php?post_type=docs', 'Sort Docs', 'Sort Docs', 'edit_posts', 'sort-docs', array( $this, 'docs_sort_order' ));

	}


	/**
	 * load content for each sorting page
	 *
	 * @return Reaktiv_Admin
	 */

	public function docs_sort_order() {

		$items = $this->sort_query_args( 'docs' );

		echo '<div class="wrap custom-sort-wrap">';
		echo '<div class="icon32" id="icon-docs-manager"><br></div>';
		echo '<h2>Sort Docs <img src="'.admin_url().'/images/loading.gif" id="loading-animation" /></h2>';
		echo '<ul id="custom-type-list">';
		foreach ( $items as $item ):
			echo '<li id="'.$item.'">'.get_the_title($item).'</li>';
		endforeach;
		echo '</ul>';
		echo '</div>';

	}

	/**
	 * save sort order via JS
	 *
	 * @return Reaktiv_Admin
	 */

	public function save_sort() {
		global $wpdb; // WordPress database class

		$order	= explode( ',', $_POST['order'] );
		$count	= 0;

		foreach ( $order as $item_id ) {
			$wpdb->update($wpdb->posts, array( 'menu_order' => $count ), array( 'ID' => $item_id) );
			$count++;
		}
		die(1);
	}

	/**
	 * abstract query for sorting
	 *
	 * @return Reaktiv_Admin
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

		$items = get_posts( $args);

		return $items;

	}

/// end class
}


// Instantiate our class
$Documentation_Manager_Admin = Documentation_Manager_Admin::getInstance();
