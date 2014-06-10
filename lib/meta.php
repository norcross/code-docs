<?php
/**
 * This is all the functionality related to the metaboxes
 *
 * @return Code_Docs_PostMeta
 */

class Code_Docs_PostMeta
{
	/**
	 * Static property to hold our singleton instance
	 *
	 * @var Code_Docs_PostMeta
	 */
	static $instance = false;


	/**
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a Singleton
	 *
	 * @return Code_Docs_PostMeta
	 */
	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'create_metaboxes' ) );
		add_action( 'save_post', array( $this, 'save_docs_meta' ), 1 );
	}


	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return Code_Docs_PostMeta
	 */
	public static function getInstance() {
		if ( ! self::$instance )
			self::$instance = new self;
		return self::$instance;
	}


	/**
	 * initial call for metaboxes
	 *
	 * @return Code_Docs_PostMeta
	 */
	public function create_metaboxes() {
		add_meta_box( 'cdm-code', __( 'Code Blocks', 'cdm' ), array( $this, 'doc_boxes' ), 'docs', 'normal', 'high' );
		add_meta_box( 'cdm-side', __( 'Version Detail', 'cdm' ), array( $this, 'side_box' ), 'docs', 'side', 'high' );
	}


	/**
	 * save metadata from various locations on the page
	 *
	 * @param  $post_id
	 * @return void
	 */
	public function save_docs_meta( $post_id ) {

		// make sure we aren't using autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		// do our nonce check. ALWAYS A NONCE CHECK
		if ( ! isset( $_POST['cdm_nonce'] ) || ! wp_verify_nonce( $_POST['cdm_nonce'], 'cdm_nonce' ) )
			return $post_id;

	    if ( ! current_user_can( 'edit_post', $post_id ) )
	        return $post_id;

	    if ( 'docs' !== $_POST['post_type'] )
			return $post_id;

		// run updating for code blocks
		$current_code = get_post_meta( $post_id, '_cdm_code', true );
		$updates_code = array();

	    // get code data via $_POST
	    $syntax	= $_POST['cdm-code-syntax'];
		$intro	= $_POST['cdm-code-intro'];
		$block	= $_POST['cdm-code-block'];

		$code_num = count( $syntax );

		for ( $i = 0; $i < $code_num; $i++ ) :

			if ( '' != $syntax[$i] )
				$updates_code[$i]['syntax'] = sanitize_text_field( $syntax[$i] );

			if ( '' != $intro[$i] )
				$updates_code[$i]['intro'] = wp_kses_post( $intro[$i] );

			if ( '' != $block[$i] )
				$updates_code[$i]['block'] = wp_kses_post( $block[$i] );
		endfor;

		// process array
		if ( ! empty( $updates_code ) && $updates_code != $current_code )
			update_post_meta( $post_id, '_cdm_code', $updates_code );

		elseif ( empty( $updates_code ) && $current_code )
			delete_post_meta( $post_id, '_cdm_code', $updates_code );


		// now deal with side data
		$data = $_POST['cdm-extra'];

		// quick check for the taxonomy setup
		if ( isset( $data['doctype'] ) && ! empty( $data['doctype'] ) && 'none' !== $data['doctype'] )
			wp_set_post_terms( $post_id, array( intval( $data['doctype'] ) ), 'doc-type', false );

		if ( ! isset( $data['doctype'] ) || empty( $data['doctype'] ) || 'none' == $data['doctype'] )
			wp_set_object_terms( $post_id, 4, 'doc-type' );

		// update if we're opening
		if ( isset( $data ) && ! empty( $data ) )
			update_post_meta( $post_id, '_dgm_extra_info', $data );

		// delete if we're closing
		if ( ! isset( $data ) || empty( $data ) )
			delete_post_meta( $post_id, '_dgm_extra_info' );
	}


	/**
	 * sidebox info
	 *
	 * @return Code_Docs_PostMeta
	 */
	public function side_box( $post ) {

		// grab our data
		$data = get_post_meta( $post->ID, '_dgm_extra_info', true );

		$version	= isset( $data['version'] )		? $data['version']		: '';
		$deprecated	= isset( $data['deprecated'] )	? $data['deprecated']	: '';
		$doctype	= isset( $data['doctype'] )		? $data['doctype']		: '';

		echo '<div class="cdm-version-option cdm-postmeta-box">';
			echo '<input type="text" class="small-text" name="cdm-extra[version]" id="cdm-version" value="' . $version . '">';
			echo '<label for="cdm-version"> ' . __( 'Version introduced', 'cdm' ) . '</label>';
		echo '</div>';

		$terms = get_terms( 'doc-type', 'hide_empty=0' );
		if ( $terms ) :
			echo '<div class="cdm-type-option cdm-postmeta-box">';
				echo '<select name="cdm-extra[doctype]" id="cdm-type">';
					foreach ( $terms as $term ) :
						echo '<option value="' . $term->term_id . '" ' . selected( $doctype, $term->term_id, false ) . '>' . $term->name . '</option>';
					endforeach;
				echo '</select>';
			echo '</div>';
		endif;

		echo '<div class="cdm-deprecated-option ratings-postmeta-box">';
			echo '<input type="checkbox" name="cdm-extra[deprecated]" id="cdm-deprecated" value="on" ' . checked( $deprecated, 'on', false ) . '>';
			echo '<label for="cdm-deprecated"> ' . __( 'Deprecated Item', 'cdm' ) . '</label>';
		echo '</div>';
	}


	/**
	 * build default syntax options and filter
	 *
	 * @return dropdown list of options
	 */
	static function syntax_types() {

		$types	= array(
			'html'			=> __( 'HTML',			'cdm' ),
			'css'			=> __( 'CSS',			'cdm' ),
			'javascript'	=> __( 'JavaScript',	'cdm' ),
			'php'			=> __( 'PHP',			'cdm' ),
			'multi'			=> __( 'Combination',	'cdm' ),
		);

		$types	= apply_filters( 'cdm_syntax_types', $types );

		return $types;
	}


	/**
	 * build out metabox for text info
	 *
	 * @return Code_Docs_PostMeta
	 */
	public function doc_boxes( $post ) {

		wp_nonce_field( 'cdm_nonce', 'cdm_nonce' );

		// get data sets
		$types = self::syntax_types();

		// get my code stuff
		$code = get_post_meta( $post->ID, '_cdm_code', true );

		// build table
		echo '<div id="cdm-code-table" class="form-table cdm-data-table">';

			echo '<div class="code-table-content">';
		
				// setup each field
				if ( ! empty( $code ) ) : 
				
					foreach ( $code as $item ) :
	
						$syntax	= ! empty( $item['syntax'] )	? $item['syntax']	: '';
						$intro	= ! empty( $item['intro'] )		? $item['intro']	: '';
						$block	= ! empty( $item['block'] )		? $item['block']	: '';
		
						echo '<div class="code-entry">';
		
							echo '<span class="code-syntax">';
		
								echo '<select name="cdm-code-syntax[]" class="code-syntax-drop">';
									echo '<option value="">'.__( 'Select a syntax', 'cdm' ).'</option>';
									foreach ( $types as $key => $value ) :
										echo '<option value="'.$key.'" '.selected( $syntax, $key, false ).'>'.esc_attr( $value ).'</option>';
									endforeach;
								echo '</select>';
		
								echo '<textarea name="cdm-code-intro[]" class="cdm-intro widefat" rows="6">'.esc_attr( $intro ).'</textarea>';
								
								echo '<p class="description">'.__( 'Optional description of the snippet.', 'cdm' ).'</p>';
		
							echo '</span>';
		
							echo '<span class="code-block">';
		
								echo '<textarea name="cdm-code-block[]" class="cdm-code code widefat">'.$block.'</textarea>';
								
								echo '<p class="description">'.__( 'Enter the code snippet here.', 'cdm' ).'</p>';
		
							echo '</span>';
		
							echo '<span class="code-remove last">';
								echo '<span class="remove-code button button-secondary">' . __( 'Remove', 'cdm' ) . '</span>';
							echo '</span>';
		
							echo '<i class="dashicons dashicons-image-flip-vertical cdm-sort-trigger"></i>';
		
						echo '</div>';
	
					endforeach;
	
				else :
			
					// show an empty one
					echo '<div class="code-entry">';
		
						echo '<span class="code-syntax">';
		
							echo '<select name="cdm-code-syntax[]" class="code-syntax-drop">';
								echo '<option value="">' . __( 'Select a syntax', 'cdm' ) . '</option>';
								foreach ( $types as $key => $value ) :
									echo '<option value="' . $key . '">' . esc_attr( $value ) . '</option>';
								endforeach;
							echo '</select>';
		
							echo '<textarea name="cdm-code-intro[]" class="cdm-intro widefat" rows="6"></textarea>';
							
							echo '<p class="description">' . __( 'Optional description of the snippet.', 'cdm' ) . '</p>';
		
						echo '</span>';
		
						echo '<span class="code-block">';
		
							echo '<textarea name="cdm-code-block[]" class="cdm-code code widefat"></textarea>';
							
							echo '<p class="description">' . __( 'Enter the code snippet here.', 'cdm' ) . '</p>';
		
						echo '</span>';
		
						echo '<span class="code-remove last">';
						// enpty because we don't need to remove an empty row
						echo '</span>';
		
						echo '<i class="dashicons dashicons-image-flip-vertical cdm-sort-trigger"></i>';
		
					echo '</div>';
	
				endif;

				// empty row for repeating
				echo '<div class="empty-code-row screen-reader-text">';
		
					echo '<span class="code-syntax">';
		
						echo '<select name="cdm-code-syntax[]" class="code-syntax-drop">';
							echo '<option value="">' . __( 'Select a syntax', 'cdm' ) . '</option>';
							foreach ( $types as $key => $value ) :
								echo '<option value="' . $key . '">' . esc_attr( $value ) . '</option>';
							endforeach;
						echo '</select>';
		
						echo '<textarea name="cdm-code-intro[]" class="cdm-intro widefat" rows="6"></textarea>';
						
						echo '<p class="description">'.__( 'Optional description of the snippet.', 'cdm' ).'</p>';
		
					echo '</span>';
		
					echo '<span class="code-block">';
		
						echo '<textarea name="cdm-code-block[]" class="cdm-code code widefat"></textarea>';
						
						echo '<p class="description">' . __( 'Enter the code snippet here.', 'cdm' ) . '</p>';
		
					echo '</span>';
		
					echo '<span class="code-remove last">';
						echo '<span class="remove-code button button-secondary">' . __( 'Remove', 'cdm' ) . '</span>';
					echo '</span>';
		
					echo '<i class="dashicons dashicons-image-flip-vertical cdm-sort-trigger"></i>';
		
				echo '</div>';
		
				echo '<div class="code-button">';
					echo '<input type="button" id="add-code" class="button button-primary" value="' . __( 'Add Code Block', 'cdm' ) . '">';
				echo '</div>';

			echo '</div>';
		echo '</div>';
	}


	/**
	 * save extra side metadata
	 *
	 * @return Code_Docs_PostMeta
	 */
	function save_side_meta( $post_id ) {

		// make sure we aren't using autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		// do our nonce check. ALWAYS A NONCE CHECK
		if ( ! isset( $_POST['dgm_side_nonce'] ) || ! wp_verify_nonce( $_POST['dgm_side_nonce'], 'dgm_side_nonce' ) )
			return $post_id;

	    if ( ! current_user_can( 'edit_post', $post_id ) )
	        return $post_id;

	    if ( 'docs' !== $_POST['post_type'] )
			return $post_id;


		// all clear. get data via $_POST and store it
		$data = $_POST['cdm-extra'];

		// quick check for the taxonomy setup
		if ( isset( $data['doctype'] ) && ! empty( $data['doctype'] ) && 'none' !== $data['doctype'] )
			wp_set_post_terms( $post_id, array( intval( $data['doctype'] ) ), 'doc-type', false );

		if ( ! isset( $data['doctype'] ) || empty( $data['doctype'] ) || 'none' == $data['doctype'] )
			wp_set_object_terms( $post_id, 4, 'doc-type' );

		// update if we're opening
		if ( isset( $data ) && ! empty( $data ) )
			update_post_meta( $post_id, '_dgm_extra_info', $data );

		// delete if we're closing
		if ( ! isset( $data ) || empty( $data ) )
			delete_post_meta( $post_id, '_dgm_extra_info' );
	}


/// end class
}


// Instantiate our class
$Code_Docs_PostMeta = Code_Docs_PostMeta::getInstance();
