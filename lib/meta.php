<?php
/**
 * This is all the functionality related to the metaboxes
 *
 * @return Documentation_Manager_Meta
 */

class Documentation_Manager_Meta
{
	/**
	 * Static property to hold our singleton instance
	 * @var Documentation_Manager_Meta
	 */
	static $instance = false;


	/**
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a Singleton
	 *
	 * @return Documentation_Manager_Meta
	 */
	private function __construct() {

		add_action		( 'add_meta_boxes',					array( $this,	'create_metaboxes'		)			);
		add_action      ( 'save_post',                      array( $this,   'save_main_meta'		),	1		);
		add_action      ( 'save_post',                      array( $this,   'save_side_meta'		),	1		);
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return Documentation_Manager_Meta
	 */

	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

	/**
	 * initial call for metaboxes
	 *
	 * @return Documentation_Manager_Meta
	 */

	public function create_metaboxes() {

		add_meta_box( 'dgm-meta', __('Documentation Detail', 'dgm'),	array( $this, 'doc_boxes'), 	'docs',	'normal',	'high' );
		add_meta_box( 'dgm-side', __('Version Detail', 'dgm'),			array( $this, 'side_box'),		'docs',	'side',		'high' );

	}


	/**
	 * save extra side metadata
	 *
	 * @return Documentation_Manager_Meta
	 */

	function save_side_meta( $post_id ) {

		// make sure we aren't using autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		// do our nonce check. ALWAYS A NONCE CHECK
		if ( ! isset( $_POST['dgm_side_nonce'] ) || ! wp_verify_nonce( $_POST['dgm_side_nonce'], 'dgm_side_nonce' ) )
			return $post_id;

	    if ( !current_user_can( 'edit_post', $post_id ) )
	        return $post_id;

	    if ( 'docs' !== $_POST['post_type'] )
			return $post_id;


		// all clear. get data via $_POST and store it
		$data	= $_POST['dgm-extra'];

		// quick check for the taxonomy setup
		if ( isset( $data['doctype'] ) && !empty( $data['doctype'] ) && $data['doctype'] !== 'none' )
			wp_set_post_terms( $post_id, array( intval( $data['doctype'] ) ), 'doc-type', false );

		if ( !isset( $data['doctype'] ) || empty( $data['doctype'] ) || $data['doctype'] == 'none' )
			wp_set_object_terms( $post_id, 4, 'doc-type' );

		// update if we're opening
		if ( isset( $data ) && !empty( $data ) )
			update_post_meta( $post_id, '_dgm_extra_info', $data );

		// delete if we're closing
		if ( !isset( $data ) || empty( $data ) )
			delete_post_meta( $post_id, '_dgm_extra_info' );

	}

	/**
	 * save info metadata
	 *
	 * @return Documentation_Manager_Meta
	 */

	function save_main_meta( $post_id ) {

		// run various checks to make sure we aren't doing anything weird
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		if ( ! isset( $_POST['dgm_nonce'] ) || ! wp_verify_nonce( $_POST['dgm_nonce'], 'dgm_nonce' ) )
			return $post_id;

	    if ( !current_user_can( 'edit_post', $post_id ) )
	        return $post_id;

	    if ( 'docs' !== $_POST['post_type'] )
			return $post_id;

	    // get data via $_POST
		$current_info = get_post_meta( $post_id, '_dgm_info', true );
		$updates_info = array();

	    // get info data via $_POST
	    $header		= $_POST['dgm-info-header'];
		$content	= $_POST['dgm-info-content'];

		$info_num	= count( $header );

		for ( $i = 0; $i < $info_num; $i++ ) {

			if ( $header[$i] != '' )
				$updates_info[$i]['header'] = $header[$i];

			if ( $content[$i] != '' )
				$updates_info[$i]['content'] = htmlspecialchars_decode( $content[$i] );

		}

		// process array
		if ( !empty( $updates_info ) && $updates_info != $current_info )
			update_post_meta( $post_id, '_dgm_info', $updates_info );

		elseif ( empty($updates_info) && $current_info )
			delete_post_meta( $post_id, '_dgm_info'  );


		// run updating for code blocks
		$current_code = get_post_meta( $post_id, '_dgm_code', true );
		$updates_code = array();

	    // get code data via $_POST
	    $type	= $_POST['dgm-code-type'];
		$label	= $_POST['dgm-code-label'];
		$block	= $_POST['dgm-code-block'];

		$code_num	= count( $type );

		for ( $i = 0; $i < $code_num; $i++ ) {

			if ( $type[$i] != '' )
				$updates_code[$i]['type'] = $type[$i];

			if ( $label[$i] != '' )
				$updates_code[$i]['label'] = sanitize_text_field( $label[$i] );

			if ( $block[$i] != '' )
				$updates_code[$i]['block'] = htmlspecialchars_decode( $block[$i] );

		}

		// process array
		if ( !empty( $updates_code ) && $updates_code != $current_code )
			update_post_meta( $post_id, '_dgm_code', $updates_code );

		elseif ( empty($updates_code) && $current_code )
			delete_post_meta( $post_id, '_dgm_code', $updates_code );

	}

	/**
	 * sidebox info
	 *
	 * @return Documentation_Manager_Meta
	 */

	public function side_box( $post ) {

		// Use nonce for verification
		wp_nonce_field( 'dgm_side_nonce', 'dgm_side_nonce' );

		// grab our data
		$data	= get_post_meta( $post->ID, '_dgm_extra_info', true );

		$version	= isset( $data['version'] )		? $data['version']		: '';
		$deprecated	= isset( $data['deprecated'] )	? $data['deprecated']	: '';
		$doctype	= isset( $data['doctype'] )		? $data['doctype']		: '';

		echo '<div class="dgm-version-option dgm-postmeta-box">';
			echo '<input type="text" class="small-text" name="dgm-extra[version]" id="dgm-version" value="'.$version.'">';
			echo '<label for="dgm-version"> '.__( 'Version introduced', 'dgm' ).'</label>';
		echo '</div>';

		echo '<div class="dgm-type-option dgm-postmeta-box">';
			$terms = get_terms( 'doc-type', 'hide_empty=0' );
			echo '<select name="dgm-extra[doctype]" id="dgm-type">';
				foreach ( $terms as $term ) :
					echo '<option value="'.$term->term_id.'" '.selected( $doctype, $term->term_id, false ).'>'.$term->name.'</option>';
				endforeach;
			echo '</select>';
		echo '</div>';

		echo '<div class="dgm-deprecated-option ratings-postmeta-box">';
			echo '<input type="checkbox" name="dgm-extra[deprecated]" id="dgm-deprecated" value="on" '.checked( $deprecated, 'on', false ).'>';
			echo '<label for="dgm-deprecated"> '.__('Deprecated Item', 'dgm').'</label>';
		echo '</div>';


	}

	/**
	 * build out metabox for text info
	 *
	 * @return Documentation_Manager_Meta
	 */

	public function doc_boxes( $post ) {

		wp_nonce_field( 'dgm_nonce', 'dgm_nonce' );

		// get my info box stuff
		$info	= get_post_meta( $post->ID, '_dgm_info', true );

		// build table
		echo '<div id="dgm-info-table" class="form-table dgm-data-table">';
		echo '<h4>Information Detail</h4>';

		echo '<div class="info-table-content">';
		// setup each field
		if ( !empty( $info ) ) : foreach ( $info as $item ) {

			$header		= !empty( $item['header'] )		? $item['header']	: '';
			$content	= !empty( $item['content'] )	? $item['content']	: '';

			echo '<div class="info-entry">';

				echo '<div class="info-header">';
					echo '<span class="info-label">';
						echo '<label for="info-header">'.__('Header', 'dgm' ).'</label>';
					echo '</span>';


					echo '<span class="info-field-entry">';
						echo '<input class="info-header widefat" type="text" name="dgm-info-header[]" value="'.$header.'">';
					echo '</span>';

				echo '</div>';

				echo '<div class="info-content">';
					echo '<span class="info-label">';
						echo '<label for="info-content">'.__('Content', 'dgm' ).'</label>';
					echo '</span>';

					echo '<span class="info-field-entry">';
						echo '<textarea name="dgm-info-content[]" class="info-content widefat">'.$content.'</textarea>';
					echo '</span>';

					echo '<span class="info-remove last">';
						echo '<span class="remove-info button button-secondary button-remove">Remove</span>';
					echo '</span>';

				echo '</div>';

			echo '</div>';

		}

		else :
			// show an empty one
			echo '<div class="info-entry">';

				echo '<div class="info-header">';
					echo '<span class="info-label">';
						echo '<label for="info-header">'.__('Header', 'dgm' ).'</label>';
					echo '</span>';


					echo '<span class="info-field-entry">';
						echo '<input class="info-header widefat" type="text" name="dgm-info-header[]" value="">';
					echo '</span>';

				echo '</div>';

				echo '<div class="info-content">';
					echo '<span class="info-label">';
						echo '<label for="info-content">'.__('Content', 'dgm' ).'</label>';
					echo '</span>';

					echo '<span class="info-field-entry">';
						echo '<textarea name="dgm-info-content[]" class="info-content widefat"></textarea>';
					echo '</span>';

					echo '<span class="info-remove last">';
						// empty since it's the first one
					echo '</span>';

				echo '</div>';

			echo '</div>';

		endif;

		// empty row for repeating
		echo '<div class="empty-info-row screen-reader-text">';

			echo '<div class="info-header">';
				echo '<span class="info-label">';
					echo '<label for="info-header">'.__('Header', 'dgm' ).'</label>';
				echo '</span>';


				echo '<span class="info-field-entry">';
					echo '<input class="info-header widefat" type="text" name="dgm-info-header[]" value="">';
				echo '</span>';

			echo '</div>';

			echo '<div class="info-content">';
				echo '<span class="info-label">';
					echo '<label for="info-content">'.__('Content', 'dgm' ).'</label>';
				echo '</span>';

				echo '<span class="info-field-entry">';
					echo '<textarea name="dgm-info-content[]" class="info-content widefat"></textarea>';
				echo '</span>';

				echo '<span class="info-remove last">';
					echo '<span class="remove-info button button-secondary button-remove">Remove</span>';
				echo '</span>';

			echo '</div>';

		echo '</div>';

		echo '<div class="info-button">';
			echo '<input type="button" id="add-info" class="button button-primary" value="Add Content Block">';
		echo '</div>';

		echo '</div>';
		echo '</div>';


		// get my code stuff
		$code	= get_post_meta( $post->ID, '_dgm_code', true );

		// build table
		echo '<div id="dgm-code-table" class="form-table dgm-data-table">';
		echo '<h4>Code Blocks</h4>';

		echo '<div class="code-table-headers">';
			echo '<span class="code-type">'.__('Type', 'dgm' ).'</span>';
			echo '<span class="code-label">'.__('Label', 'dgm' ).'</span>';
			echo '<span class="code-block">'.__('Code', 'dgm' ).'</span>';
			echo '<span class="code-remove last">&nbsp;</span>'; // empty to match the rows
		echo '</div>';

		echo '<div class="code-table-content">';
		// setup each field
		if ( !empty( $code ) ) : foreach ( $code as $item ) {

			$type	= !empty( $item['type'] )	? $item['type']		: '';
			$label	= !empty( $item['label'] )	? $item['label']	: '';
			$block	= !empty( $item['block'] )	? $item['block']	: '';

			echo '<div class="code-entry">';

				echo '<span class="code-type">';
					echo '<select name="dgm-code-type[]" class="code-type">';
						echo '<option value="">(Select)</option>';
						echo '<option value="html" '.selected( $type, 'html', false ).'>HTML</option>';
						echo '<option value="css" '.selected( $type, 'css', false ).'>CSS</option>';
						echo '<option value="javascript" '.selected( $type, 'javascript', false ).'>JavaScript</option>';
						echo '<option value="php" '.selected( $type, 'php', false ).'>PHP</option>';
						echo '<option value="multi" '.selected( $type, 'multi', false ).'>Combination</option>';
					echo '</select>';
				echo '</span>';

				echo '<span class="code-label">';
					echo '<input class="code-label widefat" type="text" name="dgm-code-label[]" value="'.$label.'">';
				echo '</span>';

				echo '<span class="code-block">';
					echo '<textarea name="dgm-code-block[]" class="dgm-code code widefat">'.$block.'</textarea>';
				echo '</span>';

				echo '<span class="code-remove last">';
					echo '<span class="remove-code button button-secondary">Remove</span>';
				echo '</span>';

			echo '</div>';

		}

		else :
			// show an empty one
			echo '<div class="code-entry">';

				echo '<span class="code-type">';
					echo '<select name="dgm-code-type[]" class="code-type">';
						echo '<option value="">(Select)</option>';
						echo '<option value="html">HTML</option>';
						echo '<option value="css">CSS</option>';
						echo '<option value="javascript">JavaScript</option>';
						echo '<option value="php">PHP</option>';
						echo '<option value="multi">Combination</option>';
					echo '</select>';
				echo '</span>';

				echo '<span class="code-label">';
					echo '<input class="code-label widefat" type="text" name="dgm-code-label[]" value="">';
				echo '</span>';

				echo '<span class="code-block">';
					echo '<textarea name="dgm-code-block[]" class="dgm-code code widefat"></textarea>';
				echo '</span>';

				echo '<span class="code-remove last">';
				// enpty because we don't need to remove an empty row
				echo '</span>';

			echo '</div>';

		endif;

		// empty row for repeating
		echo '<div class="empty-code-row screen-reader-text">';

				echo '<span class="code-type">';
					echo '<select name="dgm-code-type[]" class="code-type">';
						echo '<option value="">(Select)</option>';
						echo '<option value="html">HTML</option>';
						echo '<option value="css">CSS</option>';
						echo '<option value="javascript">JavaScript</option>';
						echo '<option value="php">PHP</option>';
						echo '<option value="multi">Combination</option>';
					echo '</select>';
				echo '</span>';

				echo '<span class="code-label">';
					echo '<input class="code-label widefat" type="text" name="dgm-code-label[]" value="">';
				echo '</span>';

				echo '<span class="code-block">';
					echo '<textarea name="dgm-code-block[]" class="dgm-code code widefat"></textarea>';
				echo '</span>';

				echo '<span class="code-remove last">';
					echo '<span class="remove-code button button-secondary">Remove</span>';
				echo '</span>';

		echo '</div>';

		echo '<div class="code-button">';
			echo '<input type="button" id="add-code" class="button button-primary" value="Add Code Block">';
		echo '</div>';

		echo '</div>';
		echo '</div>';

	}



/// end class
}


// Instantiate our class
$Documentation_Manager_Meta = Documentation_Manager_Meta::getInstance();
