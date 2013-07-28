<?php
/**
 * This is all the functionality related to display the code
 *
 * @return Documentation_Manager_Display
 */

class Documentation_Manager_Display
{
    /**
     * Static property to hold our singleton instance
     * @var Documentation_Manager_Display
     */
    static $instance = false;


    /**
     * This is our constructor, which is private to force the use of
     * getInstance() to make this a Singleton
     *
     * @return Documentation_Manager_Display
     */

    private function __construct() {
		add_action      ( 'wp_enqueue_scripts',         array( $this, 'front_scripts'		),	10		);
//		add_filter		( 'the_title',					array( $this, 'underscore_title'	),	10,	2	);
		add_filter		( 'the_content',				array( $this, 'display_single'		),	7		);
		add_filter		( 'the_content',				array( $this, 'cleanup'				),	10		);

		add_shortcode	( 'codedoc',					array( $this, 'codedoc'				) 			);
    }

    /**
     * If an instance exists, this returns it.  If not, it creates one and
     * retuns it.
     *
     * @return Documentation_Manager_Display
     */

    public static function getInstance() {
        if ( !self::$instance )
            self::$instance = new self;
        return self::$instance;
    }

	/**
	 * Load CSS and JS files
	 *
	 * @return Reaktiv_Base
	 */

	public function front_scripts() {

		// CSS
		if ( is_singular( 'docs' ) ) :

            wp_enqueue_style( 'prism', plugins_url('/css/prism.css', __FILE__), array(), DGM_VER, 'all' );
        	wp_enqueue_style( 'dgm-front', plugins_url('/css/dgm.front.css', __FILE__), array(), DGM_VER, 'all' );

            wp_enqueue_script( 'prism', plugins_url('/js/prism.js', __FILE__) , array(), DGM_VER, false );
            wp_enqueue_script( 'dgm-front', plugins_url('/js/dgm.front.js', __FILE__) , array(), DGM_VER, true );


		endif;

	}

	/**
	 * remove the underscores from the actual title display, which can break responsive design
	 *
	 * @return Documentation_Manager_Display
	 */

	public function underscore_title( $title, $id ) {

		// bail on non-singulars
	    if ( !is_singular( 'docs' ) )
	    	return $title;

	    $title	= str_replace( '_', ' ', $title );

	    return $title;
	}

	/**
	 * call function for doc display on single item
	 *
	 * @return Documentation_Manager_Display
	 */

	public function display_single( $content ) {

		// check for my post types
		if ( !is_singular( 'docs' ) )
			return $content;

		// get post-specific data
		global $post;
		$post_id	= $post->ID;

		// get buttons from function
		$infoblocks	= get_post_meta( $post_id, '_dgm_info', true );

		if ( empty( $infoblocks ) )
			return $content;

		// Return ALL THE THINGS
		$block 	= '';

		foreach( $infoblocks as $info ) :

			$header	= $info['header'];
			$text	= $info['content'];

			$block	.= '<div class="info-block-single">';
			$block	.= '<h3>'.$header.'</h3>';
			$block	.= wpautop( $text );
			$block	.= '</div>';

		endforeach;

		// tweak the actual content itself
		$content = '<div class="info-block-single"><h3>Description</h3>'.$content.'</div>';

		return $content.$block;

	}


	/**
	 * cleanup the extra <p> tags
	 *
	 * @return Documentation_Manager_Display
	 */

	public function cleanup( $content ) {
	    $array = array(
	        '<p>['		=> '[',
	        ']</p>' 	=> ']',
	        ']<br>' 	=> ']',
	        ']<br />' 	=> ']'
	    );

    	$content = strtr( $content, $array );

    	return $content;
	}


	/**
	 * shortcode for code block within content
	 *
	 * @return Documentation_Manager_Display
	 */

	//http://prismjs.com/examples.html

	public function codedoc( $atts, $content = null ) {

		extract(shortcode_atts( array(
			'label'		=> '',
		), $atts ) );

		if ( empty( $label ) )
			return;

		global $post;

		$codeblocks	= get_post_meta( $post->ID, '_dgm_code', true );

		if ( empty( $codeblocks ) )
			return;

		$display	= '';

		foreach( $codeblocks as $code ) :

			$code_type	= $code['type'];
			$code_label = $code['label'];
			$code_block	= $code['block'];

			if ( !empty( $code_block ) && $label == $code_label ):

				$display	.= '<div class="doc-manager-output">';
				$display	.= '<pre class="line-numbers"><code class="language-'.$code_type.'">';
				$display	.= esc_attr( $code_block );
				$display	.= '</pre></code>';
				$display	.= '</div>';

			endif;

		endforeach;

		// now send it all back
		return $display;
	}

/// end class
}


// Instantiate our class
$Documentation_Manager_Display = Documentation_Manager_Display::getInstance();
