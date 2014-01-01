<?php
/**
 * This is all the functionality related to display the code
 *
 * @return Code_Docs_Front_End
 */

class Code_Docs_Front_End
{
    /**
     * Static property to hold our singleton instance
     * @var Code_Docs_Front_End
     */
    static $instance = false;


    /**
     * This is our constructor, which is private to force the use of
     * getInstance() to make this a Singleton
     *
     * @return Code_Docs_Front_End
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
     * @return Code_Docs_Front_End
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

            wp_enqueue_style( 'prism', plugins_url('/prism/prism.css', __FILE__), array(), CDM_VER, 'all' );
        	wp_enqueue_style( 'cdm-front', plugins_url('/css/cdm.front.css', __FILE__), array(), CDM_VER, 'all' );

            wp_enqueue_script( 'prism', plugins_url('/prism/prism.js', __FILE__) , array(), CDM_VER, false );
            wp_enqueue_script( 'cdm-front', plugins_url('/js/cdm.front.js', __FILE__) , array(), CDM_VER, true );


		endif;

	}

	/**
	 * remove the underscores from the actual title display, which can break responsive design
	 *
	 * @return Code_Docs_Front_End
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
	 * @return Code_Docs_Front_End
	 */

	public function display_single( $content ) {

		// check for my post types
		if ( ! is_singular( 'docs' ) )
			return $content;

		// get post-specific data
		global $post;
		$post_id	= $post->ID;

		// get buttons from function

		$codeblocks	= get_post_meta( $post->ID, '_cdm_code', true );

		if ( empty( $codeblocks ) )
			return $content;

		// Return ALL THE THINGS
		$display 	= '';

		foreach( $codeblocks as $block ) :
			if ( isset( $block['block'] ) && ! empty( $block['block'] ) ):

				$syntax	= isset( $block['syntax'] ) ? esc_attr( $block['syntax'] ) : 'html';

				$display	.= '<div class="cdm-output">';

				if ( isset( $block['intro'] ) && ! empty( $block['intro'] ) )
					$display	.= wpautop( esc_attr( $block['intro'] ) );

				$display	.= '<pre class="line-numbers"><code class="language-'.$syntax.'">';
				$display	.= esc_attr( $block['block'] );
				$display	.= '</code></pre>';
				$display	.= '</div>';

			endif;
		endforeach;


		return $content.$display;

	}


	/**
	 * cleanup the extra <p> tags
	 *
	 * @return Code_Docs_Front_End
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
	 * @return Code_Docs_Front_End
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
$Code_Docs_Front_End = Code_Docs_Front_End::getInstance();
