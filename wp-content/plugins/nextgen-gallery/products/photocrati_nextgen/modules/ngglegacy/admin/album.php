<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

class nggManageAlbum {

	/**
	 * The selected album ID
	 *
	 * @since 1.3.0
	 * @access privat
	 * @var int
	 */
	var $currentID = 0;

	/**
	 * The array for the galleries
	 *
	 * @since 1.3.0
	 * @access privat
	 * @var array
	 */
	var $galleries = false;

	/**
	 * The array for the albums
	 *
	 * @since 1.3.0
	 * @access privat
	 * @var array
	 */
	var $albums = false;

	/**
	 * The amount of all galleries
	 *
	 * @since 1.4.0
	 * @access privat
	 * @var int
	 */
	var $num_galleries = false;

	/**
	 * The amount of all albums
	 *
	 * @since 1.4.0
	 * @access privat
	 * @var int
	 */
	var $num_albums = false;

	/**
	 * PHP4 compatibility layer for calling the PHP5 constructor.
	 *
	 */
	function nggManageAlbum() {
		return $this->__construct();
	}

	/**
	 * Gets the Pope component registry
	 * @return C_Component_Registry
	 */
	function get_registry()
	{
		if (!isset($this->_registry)) {
			$this->_registry = C_Component_Registry::get_instance();
		}

		return $this->_registry;
	}

	/**
	 * Init the album output
	 *
	 */
	function __construct() {
		return true;
	}

	function controller() {

		if (isset ($_POST['update']) || isset( $_POST['delete'] ) || isset( $_POST['add'] ) )
			$this->processor();

		if (isset ($_POST['update_album']) )
			$this->update_album();

		// get first all galleries & albums
		$this->albums = array();
		foreach (C_Album_Mapper::get_instance()->find_all() as $album) {
			$this->albums[$album->{$album->id_field}] = $album;
		}

		$this->galleries = array();
		foreach (C_Gallery_Mapper::get_instance()->find_all() as $gallery) {
			$this->galleries[$gallery->{$gallery->id_field}] = $gallery;
		}
		$this->num_albums  = count( $this->albums );
		$this->num_galleries  = count( $this->galleries );

		$this->output();
	}

	function _get_album($id)
	{
		$retval = NULL;

		if (isset($this->albums[$id])) {
			$retval = $this->albums[$id];
		}
		else $retval = C_Album_Mapper::get_instance()->find($id);

		return $retval;
	}

	function _get_gallery($id)
	{
		$retval = NULL;

		if (isset($this->galleries[$id])) {
			$retval = $this->galleries[$id];
		}
		else $retval = C_Gallery_Mapper::get_instance()->find($id);

		return $retval;
	}

	/**
	 * Finds a suitable preview pic for the album if one hasn't been set
	 * already
	 * @param stdClass|C_Album $album
	 * @return stdClass|C_Album
	 */
	function _set_album_preview_pic($album)
	{
		$sortorder		= array_merge($album->sortorder);

		while(!$album->previewpic) {
			// If the album is missing a preview pic, set one!
			if (($first_entity = array_pop($sortorder))) {

				// Is the first entity a gallery or album
				if (substr($first_entity, 0, 1) == 'a') {
					$subalbum = $this->_get_album(substr($first_entity, 1));
					if ($subalbum->previewpic) {
						$album->previewpic = $subalbum->previewpic;
					}
				}
				else {
					$gallery = $this->_get_gallery($first_entity);
					if ($gallery && $gallery->previewpic) {
						$album->previewpic = $gallery->previewpic;
					}
				}
			}
			else break;
		}

		return $album;
	}

	function processor() {
		global $wpdb;

		check_admin_referer('ngg_album');

		// Create album
		if ( isset($_POST['add']) && isset ($_POST['newalbum']) ) {

			if (!nggGallery::current_user_can( 'NextGEN Add/Delete album' ))
				wp_die(__('Cheatin&#8217; uh?'));

			$album = new stdClass();
			$album->name = $_POST['newalbum'];
			if (C_Album_Mapper::get_instance()->save($album)) {
				$this->currentID = $_REQUEST['act_album'] = $album->{$album->id_field};
				$this->albums[$this->currentID] = $album;
				do_action('ngg_add_album', $this->currentID);
					nggGallery::show_message(__('Update Successfully','nggallery'));
			}
			else {
				$this->currentID = $_REQUEST['act_album'] = 0;
			}
		}

		else if ( isset($_POST['update']) && isset($_REQUEST['act_album']) && $this->currentID = intval($_REQUEST['act_album']) ) {

            $gid = array();

			// Get the current album being updated
			$album = $this->_get_album($this->currentID);

			// Get the list of galleries/sub-albums to be added to this album
			parse_str($_REQUEST['sortorder']);

			// Set the new sortorder
			$album->sortorder = $gid;

			// Ensure that a preview pic has been sent
			$this->_set_album_preview_pic($album);

			// Save the changes
			C_Album_Mapper::get_instance()->save($album);

            //hook for other plugins
            do_action('ngg_update_album_sortorder', $this->currentID);

			nggGallery::show_message(__('Update Successfully','nggallery'));

		}

		if ( isset($_POST['delete']) ) {

			if (!nggGallery::current_user_can( 'NextGEN Add/Delete album' ))
				wp_die(__('Cheatin&#8217; uh?'));

			$this->currentID = $_REQUEST['act_album'];

			if (C_Album_Mapper::get_instance()->destroy($this->currentID)) {
				//hook for other plugins
				do_action('ngg_delete_album', $this->currentID);

				// jump back to main selection
				$this->currentID = $_REQUEST['act_album'] = 0;

				nggGallery::show_message(__('Album deleted','nggallery'));
			}

		}

	}

	function update_album() {

		check_admin_referer('ngg_thickbox_form');

		if (!nggGallery::current_user_can( 'NextGEN Edit album settings' ))
			wp_die(__('Cheatin&#8217; uh?'));

		$this->currentID = $_REQUEST['act_album'];
		$album = $this->_get_album($this->currentID);
		$album->name		= stripslashes($_POST['album_name']);
		$album->albumdesc	= stripslashes($_POST['album_desc']);
		$album->previewpic	= (int)$_POST['previewpic'];
		$album->pageid		= (int)$_POST['pageid'];
		$result = C_Album_Mapper::get_instance()->save($album);

		//hook for other plugin to update the fields
		do_action('ngg_update_album', $this->currentID, $_POST);

		if ($result)
			nggGallery::show_message(__('Update Successfully','nggallery'));
	}

	function output() {

	global $wpdb, $nggdb;

	if (isset($_REQUEST['act_album'])) $this->currentID = intval($_REQUEST['act_album']);

	//TODO:Code MUST be optimized, how to flag a used gallery better ?
	$used_list = $this->get_used_galleries();

	$album = $this->_get_album($this->currentID);
?>

<script type="text/javascript">

jQuery(document).ready(
	function($)
	{
		if ($(this).data('ready')) return;

		if (window.Frame_Event_Publisher) {

			// Refresh when a new gallery has been added
			Frame_Event_Publisher.listen_for('attach_to_post:manage_galleries attach_to_post:new_gallery', function(){
				window.location.href = window.location.href;
			});

			// Updates the thumbnail image when a previewpic has been modified
			Frame_Event_Publisher.listen_for('attach_to_post:thumbnail_modified', function(data){
				var image_id = data.image[data.image.id_field];
				var $image = $('img[rel="'+image_id+'"]');
				if ($image.length > 0) {
					$image.attr('src', data.image.thumb_url);
				}
			});
		}

        jQuery("#previewpic").nggAutocomplete( {
            type: 'image',domain: "<?php echo home_url('index.php', is_ssl() ? 'https' : 'http'); ?>",width: "95%"
        });

		jQuery('#selectContainer').sortable( {
			items: '.groupItem',
			placeholder: 'sort_placeholder',
			opacity: 0.7,
			tolerance: 'intersect',
			distance: 2,
			forcePlaceholderSize: true ,
			connectWith: ['#galleryContainer']
		} );

		jQuery('#galleryContainer').sortable( {
			items: '.groupItem',
			placeholder: 'sort_placeholder',
			opacity: 0.7,
			tolerance: 'intersect',
			distance: 2,
			forcePlaceholderSize: true ,
			connectWith: ['#selectContainer', '#albumContainer']
		} );

		jQuery('#albumContainer').sortable( {
			items: '.groupItem',
			placeholder: 'sort_placeholder',
			opacity: 0.7,
			tolerance: 'intersect',
			distance: 2,
			forcePlaceholderSize: true ,
			connectWith: ['#galleryContainer']
		} );

		jQuery('a.min').on('click', toggleContent);

		// Hide used galleries
		jQuery('a#toggle_used').click(function()
			{
				jQuery('#selectContainer div.inUse').toggle();
				return false;
			}
		);

		// Maximize All Portlets (whole site, no differentiation)
		jQuery('a#all_max').click(function()
			{
				jQuery('div.itemContent:hidden').show();
				return false;
			}
		);

		// Minimize All Portlets (whole site, no differentiation)
		jQuery('a#all_min').click(function()
			{
				jQuery('div.itemContent:visible').hide();
				return false;
			}
		);
	   // Auto Minimize if more than 4 (whole site, no differentiation)
	   if(jQuery('a.min').length > 4)
	   {
	   		jQuery('a.min').html('[+]');
	   		jQuery('div.itemContent:visible').hide();
	   		jQuery('#selectContainer div.inUse').toggle();
	   };

	   $(this).data('ready', true);
	}
);

var toggleContent = function(e)
{
	var targetContent = jQuery('div.itemContent', this.parentNode.parentNode);
	if (targetContent.css('display') == 'none') {
		targetContent.slideDown(300);
		jQuery(this).html('[-]');
	} else {
		targetContent.slideUp(300);
		jQuery(this).html('[+]');
	}
	return false;
};

function ngg_serialize(s)
{
	//serial = jQuery.SortSerialize(s);
	serial = jQuery('#galleryContainer').sortable('serialize');
	jQuery('input[name=sortorder]').val(serial);
	return serial;
}

function showDialog() {
	jQuery( "#editalbum").dialog({
		width: 640,
        resizable : false,
		modal: true,
        title: '<?php echo esc_js( __('Edit Album', 'nggallery') ); ?>',
		position: {
			my:		'center',
			at:		'center',
			of:		window.parent
		}
	});
    jQuery('#editalbum .dialog-cancel').click(function() { jQuery( "#editalbum" ).dialog("close"); });
}

</script>

<div class="wrap album" id="wrap" >
	<?php //include('templates/social_media_buttons.php'); ?>
    <?php screen_icon( 'nextgen-gallery' ); ?>
	<h2><?php esc_html_e('Manage Albums', 'nggallery') ?></h2>
	<form id="selectalbum" method="POST" onsubmit="ngg_serialize()" accept-charset="utf-8">
		<?php wp_nonce_field('ngg_album') ?>
		<input name="sortorder" type="hidden" />
		<div class="albumnav tablenav">
			<div class="alignleft actions">
				<?php esc_html_e('Select album', 'nggallery') ?>
				<select id="act_album" name="act_album" onchange="this.form.submit();">
					<option value="0" ><?php esc_html_e('No album selected', 'nggallery') ?></option>
					<?php
						if( is_array($this->albums) ) {
							foreach($this->albums as $a) {
								$selected = ($this->currentID == $a->id) ? 'selected="selected" ' : '';
								echo '<option value="' . $a->id . '" ' . $selected . '>' . $a->id . ' - ' . esc_attr( $a->name ) . '</option>'."\n";
							}
						}
					?>
				</select>
				<?php if ($album && $this->currentID){ ?>
					<input class="button-primary" type="submit" name="update" value="<?php esc_attr_e('Update', 'nggallery'); ?>"/>
					<?php if(nggGallery::current_user_can( 'NextGEN Edit album settings' )) { ?>
					<input class="button-secondary" type="submit" name="showThickbox" value="<?php esc_attr_e( 'Edit album', 'nggallery'); ?>" onclick="showDialog(); return false;" />
					<?php } ?>
					<?php if(nggGallery::current_user_can( 'NextGEN Add/Delete album' )) { ?>
					<input class="button-secondary action "type="submit" name="delete" value="<?php esc_attr_e('Delete', 'nggallery'); ?>" onclick="javascript:check=confirm('<?php echo esc_js('Delete album ?','nggallery'); ?>');if(check==false) return false;"/>
					<?php } ?>
				<?php } else { ?>
					<?php if(nggGallery::current_user_can( 'NextGEN Add/Delete album' )) { ?>
					<span><?php esc_html_e('Add new album', 'nggallery'); ?>&nbsp;</span>
					<input class="search-input" id="newalbum" name="newalbum" type="text" value="" />
					<input class="button-secondary action" type="submit" name="add" value="<?php esc_attr_e('Add', 'nggallery'); ?>"/>
					<?php } ?>
				<?php } ?>
			</div>
		</div>
	</form>

	<br class="clear"/>

	<div>
		<div style="float:right;">
		  <a href="#" title="<?php esc_attr_e('Show / hide used galleries','nggallery'); ?>" id="toggle_used"><?php esc_html_e('[Show all]', 'nggallery'); ?></a>
		| <a href="#" title="<?php esc_attr_e('Maximize the widget content','nggallery'); ?>" id="all_max"><?php esc_html_e('[Maximize]', 'nggallery'); ?></a>
		| <a href="#" title="<?php esc_attr_e('Minimize the widget content','nggallery'); ?>" id="all_min"><?php esc_html_e('[Minimize]', 'nggallery'); ?></a>
		</div>
		<?php esc_html_e('After you create and select a album, you can drag and drop a gallery or another album into your new album below','nggallery'); ?>
	</div>

	<br class="clear" />

	<div class="container">

		<!-- /#album container -->
		<div class="widget widget-right">
			<div class="widget-top">
				<h3><?php esc_html_e('Select album', 'nggallery'); ?></h3>
			</div>
			<div id="albumContainer" class="widget-holder">
			<?php
			if( is_array( $this->albums ) ) {
				foreach($this->albums as $a) {
					$this->get_container('a' . $a->id);
				}
			}
		?>
			</div>
		</div>

		<!-- /#select container -->
		<div class="widget widget-right">
			<div class="widget-top">
				<h3><?php esc_html_e('Select gallery', 'nggallery'); ?></h3>
			</div>
			<div id="selectContainer" class="widget-holder">
		<?php

		if( is_array( $this->galleries ) ) {
			//get the array of galleries
			$sort_array = $album ? $album->sortorder : array();
			foreach($this->galleries as $gallery) {
				if (!in_array($gallery->gid, $sort_array)) {
					if (in_array($gallery->gid,$used_list))
						$this->get_container($gallery->gid,true);
					else
						$this->get_container($gallery->gid,false);
				}
			}
		}
		?>
			</div>
		</div>

		<!-- /#target-album -->
		<div class="widget target-album widget-left">
		<?php if ($album && $this->currentID){ ?>
				<div class="widget-top">
					<h3><?php esc_html_e('Album ID', 'nggallery');  ?> <?php echo $album->id . ' : ' . esc_html( $album->name ); ?> </h3>
				</div>
				<div id="galleryContainer" class="widget-holder target">
				<?php
				$sort_array = $album->sortorder;
				foreach($sort_array as $galleryid) {
					$this->get_container($galleryid, false);
				}
			}
			else
			{
				?>
				<div class="widget-top">
					<h3><?php esc_html_e('No album selected!', 'nggallery'); ?></h3>
				</div>
				<div class="widget-holder target">
				<?php
			}
		?>
			</div>
		</div><!-- /#target-album -->

	</div><!-- /#container -->
</div><!-- /#wrap -->

<?php if ($album && $this->currentID): ?>
<!-- #editalbum -->
<div id="editalbum" style="display: none;" >
	<form id="form-edit-album" method="POST" accept-charset="utf-8">
	<?php wp_nonce_field('ngg_thickbox_form') ?>
	<input type="hidden" id="current_album" name="act_album" value="<?php echo $this->currentID; ?>" />
	<table width="100%" border="0" cellspacing="3" cellpadding="3" >
	  	<tr>
	    	<th>
	    		<?php esc_html_e('Album name:', 'nggallery'); ?><br />
				<input class="search-input" id="album_name" name="album_name" type="text" value="<?php echo esc_attr( $album->name ); ?>" style="width:95%" />
	    	</th>
	  	</tr>
	  	<tr>
	    	<th>
	    		<?php esc_html_e('Album description:', 'nggallery'); ?><br />
	    		<textarea class="search-input" id="album_desc" name="album_desc" cols="50" rows="2" style="width:95%" ><?php echo esc_attr( $album->albumdesc ); ?></textarea>
	    	</th>
	  	</tr>
	  	<tr>
	    	<th>
	    		<?php esc_html_e('Select a preview image:', 'nggallery'); ?><br />
					<select id="previewpic" name="previewpic" style="width:95%" >
                        <?php if ($album->previewpic == 0) ?>
		                <option value="0"><?php esc_html_e('No picture', 'nggallery'); ?></option>
						<?php
                            if ($album->previewpic == 0)
                                echo '<option value="0" selected="selected">' . __('No picture', 'nggallery') . '</option>';
                            else {
                                $picture = nggdb::find_image($album->previewpic);
                                echo '<option value="' . $picture->pid . '" selected="selected" >'. $picture->pid . ' - ' . ( empty($picture->alltext) ? esc_attr( $picture->filename ) : esc_attr( $picture->alltext ) ) .' </option>'."\n";
                            }
						?>
					</select>
	    	</th>
	  	</tr>
        <tr>
            <th>
                <?php esc_html_e('Page Link to', 'nggallery')?><br />
                <?php
                if (!isset($album->pageid))
                    $album->pageid = 0;

                wp_dropdown_pages(array(
                    'echo' => TRUE,
                    'name' => 'pageid',
                    'selected' => $album->pageid,
                    'show_option_none' => esc_html('Not linked', 'nggallery'),
                    'option_none_value' => 0
                )); ?>
            </th>
        </tr>

        <?php do_action('ngg_edit_album_settings', $this->currentID); ?>

	  	<tr align="right">
	    	<td class="submit">
	    		<input type="submit" class="button-primary" name="update_album" value="<?php esc_attr_e('OK', 'nggallery'); ?>" />
	    		&nbsp;
	    		<input class="button-secondary dialog-cancel" type="reset" value="<?php esc_attr_e('Cancel', 'nggallery'); ?>"/>
	    	</td>
		</tr>
	</table>
	</form>
</div>
<!-- /#editalbum -->
<?php endif; ?>

<?php

	}

	/**
	 * Create the album or gallery container
	 *
	 * @param integer $id (the prefix 'a' indidcates that you look for a album
	 * @param bool $used (object will be hidden)
	 * @return $output
	 */
	function get_container($id = 0, $used = false) {
		global $wpdb, $nggdb;

		$obj =  array();
		$preview_image = '';
        $class = '';

		// if the id started with a 'a', then it's a sub album
		if (substr( $id, 0, 1) == 'a') {

			if ( !$album = $this->_get_album(substr( $id, 1)))
				return;

			$obj['id']   = $album->id;
			$obj['name'] = $obj['title'] = $album->name;
            $obj['type'] = 'album';
			$class = 'album_obj';

			// get the post name
			$post = get_post($album->pageid);
			$obj['pagenname'] = ($post == null) ? '---' : $post->post_title;

			// for speed reason we limit it to 50
			if ( $this->num_albums < 50 ) {
				$thumbURL = "";
				if ($album->previewpic) {
					$image = $nggdb->find_image( $album->previewpic );
                    if ($image) $thumbURL = @add_query_arg('timestamp', time(), $image->thumbURL);
				}
				$preview_image = $thumbURL  ? '<div class="inlinepicture"><img rel="'.$album->previewpic.'" src="' . nextgen_esc_url( $thumbURL ). '" /></div>' : '';
			}

			// this indicates that we have a album container
			$prefix = 'a';

		} else {
			if ( !$gallery = $nggdb->find_gallery( $id ) )
				return;

			$obj['id']    = $gallery->gid;
			$obj['name']  = $gallery->name;
			$obj['title'] = $gallery->title;
            $obj['type']  = 'gallery';

			// get the post name
			$post = get_post($gallery->pageid);
			$obj['pagenname'] = ($post == null) ? '---' : $post->post_title;

			// for spped reason we limit it to 50
			if ( $this->num_galleries < 50 ) {
				// set image url
				$thumbURL = "";
				if ($gallery->previewpic) {
					$image = $nggdb->find_image( $gallery->previewpic );
					$thumbURL = @add_query_arg('timestamp', time(), $image->thumbURL);
				}
				$preview_image = ( !is_null($thumbURL) )  ? '<div class="inlinepicture"><img rel="'.$gallery->previewpic.'" src="' . nextgen_esc_url( $thumbURL ). '" /></div>' : '';
			}

			$prefix = '';
		}

		// add class if it's in use in other albums
		$used = $used ? ' inUse' : '';

		echo '<div id="gid-' . $prefix . $obj['id'] . '" class="groupItem' . $used . '">
				<div class="innerhandle">
					<div class="item_top ' . $class . '">
						<a href="#" class="min" title="close">[-]</a>
						ID: ' . $obj['id'] . ' | ' . wp_html_excerpt( esc_html ( nggGallery::i18n( $obj['title'] ) ) , 25) . '
					</div>
					<div class="itemContent">
							' . $preview_image . '
							<p><strong>' . __('Name', 'nggallery') . ' : </strong>' . esc_html ( nggGallery::i18n( $obj['name'] ) ). '</p>
							<p><strong>' . __('Title', 'nggallery') . ' : </strong>' . esc_html ( nggGallery::i18n( $obj['title'] ) ) . '</p>
							<p><strong>' . __('Page', 'nggallery'). ' : </strong>' . esc_html ( nggGallery::i18n( $obj['pagenname'] ) ) . '</p>
							' . apply_filters('ngg_display_album_item_content', '', $obj) . '
						</div>
				</div>
			   </div>';
	}

	/**
	 * get all used galleries from all albums
	 *
	 * @return array $used_galleries_ids
	 */
	function get_used_galleries() {

		$used = array();

		if ($this->albums) {
			foreach($this->albums as $album) {
				foreach($album->sortorder as $galleryid) {
					if (!in_array($galleryid, $used))
						$used[] = $galleryid;
				}
			}
		}

		return $used;
	}

	/**
	 * PHP5 style destructor
	 *
	 * @return bool Always true
	 */
	function __destruct() {
		return true;
	}

}
