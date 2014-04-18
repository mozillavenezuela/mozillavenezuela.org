<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {	die('You are not allowed to call this page directly.');}

function nggallery_picturelist($controller) {
// *** show picture list
	global $wpdb, $nggdb, $user_ID, $ngg;

	// Look if its a search result
	$is_search = isset ($_GET['s']) ? true : false;
	$counter	= 0;

    $wp_list_table = new _NGG_Images_List_Table('nggallery-manage-images');

    if ($is_search) {

		// fetch the imagelist
		$picturelist = $ngg->manage_page->search_result;

		// we didn't set a gallery or a pagination
		$act_gid     = 0;
		$_GET['paged'] = 1;
		$page_links = false;

	} else {

		// GET variables
		$act_gid    = $ngg->manage_page->gid;

		// Load the gallery metadata
		$mapper = C_Gallery_Mapper::get_instance();
		$gallery = $mapper->find($act_gid);

		if (!$gallery) {
			nggGallery::show_error(__('Gallery not found.', 'nggallery'));
			return;
		}

		// Check if you have the correct capability
		if (!nggAdmin::can_manage_this_gallery($gallery->author)) {
			nggGallery::show_error(__('Sorry, you have no access here', 'nggallery'));
			return;
		}

		// look for pagination
        $_GET['paged'] = isset($_GET['paged']) && ($_GET['paged'] > 0) ? absint($_GET['paged']) : 1;
		$items_per_page = 50;

		$start = ( $_GET['paged'] - 1 ) * $items_per_page;

		// get picture values
		$image_mapper = C_Image_Mapper::get_instance();

		$total_number_of_images = count($image_mapper->select($image_mapper->get_primary_key_column())->
			where(array("galleryid = %d", $act_gid))->run_query(FALSE, TRUE));

		$picturelist = $image_mapper->select()->
			where(array("galleryid = %d", $act_gid))->
			order_by($ngg->options['galSort'], $ngg->options['galSortDir'])->
			limit($items_per_page, $start)->run_query();

		// get the current author
		$act_author_user    = get_userdata( (int) $gallery->author );

	}

		// list all galleries
		$gallerylist = $nggdb->find_all_galleries();

		//get the columns
		$image_columns   = $wp_list_table->get_columns();
		$hidden_columns  = get_hidden_columns('nggallery-manage-images');
		$num_columns     = count($image_columns) - count($hidden_columns);

		$attr = (nggGallery::current_user_can( 'NextGEN Edit gallery options' )) ? '' : 'disabled="disabled"';

?>
<script type="text/javascript">
<!--
function showDialog( windowId, title ) {
	var form = document.getElementById('updategallery');
	var elementlist = "";
	for (i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type == "checkbox") {
			if(form.elements[i].name == "doaction[]")
				if(form.elements[i].checked == true)
					if (elementlist == "")
						elementlist = form.elements[i].value;
					else
						elementlist += "," + form.elements[i].value ;
		}
	}
	jQuery("#" + windowId + "_bulkaction").val(jQuery("#bulkaction").val());
	jQuery("#" + windowId + "_imagelist").val(elementlist);
    // now show the dialog
	jQuery( "#" + windowId ).dialog({
		width: 640,
        resizable : false,
		modal: true,
        title: title,
		position: {
			my:		'center',
			at:		'center',
			of:		window.parent
		}
	});
    jQuery("#" + windowId + ' .dialog-cancel').click(function() { jQuery( "#" + windowId ).dialog("close"); });
}

jQuery(function (){

    jQuery('span.tooltip, label.tooltip').tooltip();

    // load a content via ajax
    jQuery('a.ngg-dialog').click(function() {
    	var dialogs = jQuery('.ngg-overlay-dialog:visible');
    	if (dialogs.size() > 0) {
    		return false;
    	}

      if ( jQuery( "#spinner" ).length == 0) {
      	jQuery("body").append('<div id="spinner"></div>');
      }

    	var $this = jQuery(this);
      var results = new RegExp('[\\?&]w=([^&#]*)').exec(this.href);
    	var width  = ( results ) ? results[1] : 600;
      var results = new RegExp('[\\?&]h=([^&#]*)').exec(this.href);
	    var height = ( results ) ? results[1] : 440;
      var container = window;

      if (window.parent) {
      	container = window.parent;
      }

      jQuery('#spinner').fadeIn();
      jQuery('#spinner').position({ my: "center", at: "center", of: container });

      var dialog = jQuery('<div class="ngg-overlay-dialog" style="display:hidden"></div>').appendTo('body');
      // load the remote content
      dialog.load(
          this.href,
          {},
          function () {
              jQuery('#spinner').hide();

              dialog.dialog({
                  title: ($this.attr('title')) ? $this.attr('title') : '',
                  position: { my: "center", at: "center", of: container },
                  width: width,
                  height: height,
                  modal: true,
                  resizable: false,
                  close: function() { dialog.remove(); }
              }).width(width - 30).height(height - 30);
          }
      );

      //prevent the browser to follow the link
      return false;
    });
});

function checkAll(form)
{
	for (i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type == "checkbox") {
			if(form.elements[i].name == "doaction[]") {
				if(form.elements[i].checked == true)
					form.elements[i].checked = false;
				else
					form.elements[i].checked = true;
			}
		}
	}
}

function getNumChecked(form)
{
	var num = 0;
	for (i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type == "checkbox") {
			if(form.elements[i].name == "doaction[]")
				if(form.elements[i].checked == true)
					num++;
		}
	}
	return num;
}

// this function check for a the number of selected images, sumbmit false when no one selected
function checkSelected() {

	var numchecked = getNumChecked(document.getElementById('updategallery'));

    if (typeof document.activeElement == "undefined" && document.addEventListener) {
    	document.addEventListener("focus", function (e) {
    		document.activeElement = e.target;
    	}, true);
    }

    if ( document.activeElement.name == 'post_paged' )
        return true;

	if(numchecked < 1) {
		alert('<?php echo esc_js(__('No images selected', 'nggallery')); ?>');
		return false;
	}

	actionId = jQuery('#bulkaction').val();

	switch (actionId) {
		case "copy_to":
			showDialog('selectgallery', '<?php echo esc_js(__('Copy image to...','nggallery')); ?>');
			return false;
			break;
		case "move_to":
			showDialog('selectgallery', '<?php echo esc_js(__('Move image to...','nggallery')); ?>');
			return false;
			break;
		case "add_tags":
			showDialog('entertags', '<?php echo esc_js(__('Add new tags','nggallery')); ?>');
			return false;
			break;
		case "delete_tags":
			showDialog('entertags', '<?php echo esc_js(__('Delete tags','nggallery')); ?>');
			return false;
			break;
		case "overwrite_tags":
			showDialog('entertags', '<?php echo esc_js(__('Overwrite','nggallery')); ?>');
			return false;
			break;
		case "resize_images":
			showDialog('resize_images', '<?php echo esc_js(__('Resize images','nggallery')); ?>');
			return false;
			break;
		case "new_thumbnail":
			showDialog('new_thumbnail', '<?php echo esc_js(__('Create new thumbnails','nggallery')); ?>');
			return false;
			break;
	}

	return confirm('<?php echo sprintf(esc_js(__("You are about to start the bulk edit for %s images \n \n 'Cancel' to stop, 'OK' to proceed.",'nggallery')), "' + numchecked + '") ; ?>');
}

jQuery(document).ready( function($) {
	if ($(this).data('ready')) return;

	// close postboxes that should be closed
	jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
	postboxes.add_postbox_toggles('ngg-manage-gallery');

	jQuery('.iedit').mouseover(
		function(e){
			jQuery(this).parent().find('.row-actions').css('visibility', 'hidden');
			jQuery(this).next('.row_actions:first').find('.row-actions:first').css('visibility', 'visible');
		}
	);

	$(this).data('ready', true);
});

//-->
</script>
<div class="wrap">
<?php //include('templates/social_media_buttons.php'); ?>
<?php screen_icon( 'nextgen-gallery' ); ?>
<?php if ($is_search) :?>
<h2><?php printf( __('Search results for &#8220;%s&#8221;', 'nggallery'), esc_html( get_search_query() ) ); ?></h2>
<form class="search-form" action="" method="get">
<p class="search-box">
	<label class="hidden" for="media-search-input"><?php _e( 'Search Images', 'nggallery' ); ?>:</label>
	<input type="hidden" id="page-name" name="page" value="nggallery-manage-gallery" />
	<input type="text" id="media-search-input" name="s" value="<?php the_search_query(); ?>" />
	<input type="submit" value="<?php _e( 'Search Images', 'nggallery' ); ?>" class="button" />
</p>
</form>

<br style="clear: both;" />

<form id="updategallery" class="nggform" method="POST" action="<?php echo $ngg->manage_page->base_page . '&amp;mode=edit&amp;s=' . get_search_query(); ?>" accept-charset="utf-8">
<?php wp_nonce_field('ngg_updategallery') ?>
<input type="hidden" name="page" value="manage-images" />

<?php else :?>
<h2><?php echo _n( 'Gallery', 'Galleries', 1, 'nggallery' ); ?> : <?php echo esc_html ( nggGallery::i18n($gallery->title) ); ?></h2>

<br style="clear: both;" />

<form id="updategallery" class="nggform" method="POST" action="<?php echo $ngg->manage_page->base_page . '&amp;mode=edit&amp;gid=' . $act_gid . '&amp;paged=' . $_GET['paged']; ?>" accept-charset="utf-8">
<?php wp_nonce_field('ngg_updategallery') ?>
<input type="hidden" name="page" value="manage-images" />

<?php if ( nggGallery::current_user_can( 'NextGEN Edit gallery options' )) : ?>
<div id="poststuff">
	<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
	<div id="gallerydiv" class="postbox <?php echo postbox_classes('gallerydiv', 'ngg-manage-gallery'); ?>" >
		<h3><?php _e('Gallery settings', 'nggallery') ?><small> (<?php _e('Click here for more settings', 'nggallery') ?>)</small></h3>
		<div class="inside">
			<?php $controller->render_gallery_fields(); ?>

			<div class="submit">
				<?php if ( wpmu_enable_function('wpmuScanFolder') && nggGallery::current_user_can( 'NextGEN Scan folder' ) ) : ?>
				<input type="submit" class="button-secondary" name="scanfolder" value="<?php _e("Scan Folder for new images",'nggallery'); ?> " />
				<?php endif; ?>
				<input type="submit" class="button-primary action" name="updatepictures" value="<?php _e("Save Changes",'nggallery'); ?>" />
			</div>

		</div>
	</div>
</div> <!-- poststuff -->
<?php endif; ?>

<?php endif; ?>

<div class="tablenav top ngg-tablenav">
    <?php $ngg->manage_page->pagination( 'top', $_GET['paged'], $total_number_of_images, $items_per_page ); ?>
	<div class="alignleft actions">
	<select id="bulkaction" name="bulkaction">
		<option value="no_action" ><?php _e("Bulk actions",'nggallery'); ?></option>
		<option value="set_watermark" ><?php _e("Set watermark",'nggallery'); ?></option>
		<option value="new_thumbnail" ><?php _e("Create new thumbnails",'nggallery'); ?></option>
		<option value="resize_images" ><?php _e("Resize images",'nggallery'); ?></option>
		<option value="recover_images" ><?php _e("Recover from backup",'nggallery'); ?></option>
		<option value="delete_images" ><?php _e("Delete images",'nggallery'); ?></option>
		<option value="import_meta" ><?php _e("Import metadata",'nggallery'); ?></option>
		<option value="rotate_cw" ><?php _e("Rotate images clockwise",'nggallery'); ?></option>
		<option value="rotate_ccw" ><?php _e("Rotate images counter-clockwise",'nggallery'); ?></option>
		<option value="copy_to" ><?php _e("Copy to...",'nggallery'); ?></option>
		<option value="move_to"><?php _e("Move to...",'nggallery'); ?></option>
		<option value="add_tags" ><?php _e("Add tags",'nggallery'); ?></option>
		<option value="delete_tags" ><?php _e("Delete tags",'nggallery'); ?></option>
		<option value="overwrite_tags" ><?php _e("Overwrite tags",'nggallery'); ?></option>
	</select>
	<input class="button-secondary" type="submit" name="showThickbox" value="<?php _e('Apply', 'nggallery'); ?>" onclick="if ( !checkSelected() ) return false;" />

	<?php if (($ngg->options['galSort'] == "sortorder") && (!$is_search) ) { ?>
		<input class="button-secondary" type="submit" name="sortGallery" value="<?php _e('Sort gallery', 'nggallery');?>" />
	<?php } ?>

	<input type="submit" name="updatepictures" class="button-primary action"  value="<?php _e('Save Changes', 'nggallery');?>" />
	</div>
</div>
<table id="ngg-listimages" class="widefat fixed" cellspacing="0" >

	<thead>
		<?php $controller->render_image_row_header() ?>
	</thead>
	<tfoot>
		<?php $controller->render_image_row_header() ?>
	</tfoot>
	<tbody id="the-list">
<?php
if($picturelist) {

	$thumbsize 	= '';
	$storage = C_Gallery_Storage::get_instance();

	if ($ngg->options['thumbfix'])
		$thumbsize = 'width="' . $ngg->options['thumbwidth'] . '" height="' . $ngg->options['thumbheight'] . '"';

	foreach($picturelist as $picture) {

		//for search result we need to check the capatibiliy
		if ( !nggAdmin::can_manage_this_gallery($gallery->author) && $is_search )
			continue;

		$counter++;
		$picture->imageURL 	= $storage->get_image_url($picture);
		$picture->thumbURL 	= $storage->get_thumb_url($picture);
		$picture->imagePath = $storage->get_image_abspath($picture);
		$picture->thumbPath = $storage->get_thumb_abspath($picture);
		echo apply_filters('ngg_manage_images_row', $picture, $counter);
	}
}

// In the case you have no capaptibility to see the search result
if ( $counter == 0 )
	echo '<tr><td colspan="' . $num_columns . '" align="center"><strong>'.__('No entries found','nggallery').'</strong></td></tr>';

?>

		</tbody>
	</table>
    <div class="tablenav bottom">
    <input type="submit" class="button-primary action" name="updatepictures" value="<?php _e('Save Changes', 'nggallery'); ?>" />
    <?php $ngg->manage_page->pagination( 'bottom', $_GET['paged'], $total_number_of_images, $items_per_page  ); ?>
    </div>
	</form>
	<br class="clear"/>
	</div><!-- /#wrap -->

	<!-- #entertags -->
	<div id="entertags" style="display: none;" >
		<form id="form-tags" method="POST" accept-charset="utf-8">
		<?php wp_nonce_field('ngg_thickbox_form') ?>
		<input type="hidden" id="entertags_imagelist" name="TB_imagelist" value="" />
		<input type="hidden" id="entertags_bulkaction" name="TB_bulkaction" value="" />
		<input type="hidden" name="page" value="manage-images" />
		<table width="100%" border="0" cellspacing="3" cellpadding="3" >
		  	<tr>
		    	<th><?php _e("Enter the tags",'nggallery'); ?> : <input name="taglist" type="text" style="width:90%" value="" /></th>
		  	</tr>
		  	<tr align="right">
		    	<td class="submit">
		    		<input class="button-primary" type="submit" name="TB_EditTags" value="<?php _e("OK",'nggallery'); ?>" />
		    		&nbsp;
		    		<input class="button-secondary dialog-cancel" type="reset" value="&nbsp;<?php _e("Cancel",'nggallery'); ?>&nbsp;" />
		    	</td>
			</tr>
		</table>
		</form>
	</div>
	<!-- /#entertags -->

	<!-- #selectgallery -->
	<div id="selectgallery" style="display: none;" >
		<form id="form-select-gallery" method="POST" accept-charset="utf-8">
		<?php wp_nonce_field('ngg_thickbox_form') ?>
		<input type="hidden" id="selectgallery_imagelist" name="TB_imagelist" value="" />
		<input type="hidden" id="selectgallery_bulkaction" name="TB_bulkaction" value="" />
		<input type="hidden" name="page" value="manage-images" />
		<table width="100%" border="0" cellspacing="3" cellpadding="3" >
		  	<tr>
		    	<th>
		    		<?php _e('Select the destination gallery:', 'nggallery'); ?>&nbsp;
		    		<select name="dest_gid" style="width:90%" >
		    			<?php
		    				foreach ($gallerylist as $gallery) {
		    					if ($gallery->gid != $act_gid) {
		    			?>
						<option value="<?php echo $gallery->gid; ?>" ><?php echo $gallery->gid; ?> - <?php echo esc_attr( stripslashes($gallery->title) ); ?></option>
						<?php
		    					}
		    				}
		    			?>
		    		</select>
		    	</th>
		  	</tr>
		  	<tr align="right">
		    	<td class="submit">
		    		<input type="submit" class="button-primary" name="TB_SelectGallery" value="<?php _e("OK",'nggallery'); ?>" />
		    		&nbsp;
		    		<input class="button-secondary dialog-cancel" type="reset" value="<?php _e("Cancel",'nggallery'); ?>" />
		    	</td>
			</tr>
		</table>
		</form>
	</div>
	<!-- /#selectgallery -->

	<!-- #resize_images -->
	<div id="resize_images" style="display: none;" >
		<form id="form-resize-images" method="POST" accept-charset="utf-8">
		<?php wp_nonce_field('ngg_thickbox_form') ?>
		<input type="hidden" id="resize_images_imagelist" name="TB_imagelist" value="" />
		<input type="hidden" id="resize_images_bulkaction" name="TB_bulkaction" value="" />
		<input type="hidden" name="page" value="manage-images" />
		<table width="100%" border="0" cellspacing="3" cellpadding="3" >
			<tr valign="top">
				<td>
					<strong><?php _e('Resize Images to', 'nggallery'); ?>:</strong>
				</td>
				<td>
					<input type="text" size="5" name="imgWidth" value="<?php echo $ngg->options['imgWidth']; ?>" /> x <input type="text" size="5" name="imgHeight" value="<?php echo $ngg->options['imgHeight']; ?>" />
					<br /><small><?php _e('Width x height (in pixel). NextGEN Gallery will keep ratio size','nggallery') ?></small>
				</td>
			</tr>
		  	<tr align="right">
		    	<td colspan="2" class="submit">
		    		<input class="button-primary" type="submit" name="TB_ResizeImages" value="<?php _e('OK', 'nggallery'); ?>" />
		    		&nbsp;
		    		<input class="button-secondary dialog-cancel" type="reset" value="&nbsp;<?php _e('Cancel', 'nggallery'); ?>&nbsp;" />
		    	</td>
			</tr>
		</table>
		</form>
	</div>
	<!-- /#resize_images -->

	<!-- #new_thumbnail -->
	<div id="new_thumbnail" style="display: none;" >
		<form id="form-new-thumbnail" method="POST" accept-charset="utf-8">
		<?php wp_nonce_field('ngg_thickbox_form') ?>
		<input type="hidden" id="new_thumbnail_imagelist" name="TB_imagelist" value="" />
		<input type="hidden" id="new_thumbnail_bulkaction" name="TB_bulkaction" value="" />
		<input type="hidden" name="page" value="manage-images" />
    <table width="100%" border="0" cellspacing="3" cellpadding="3" >
			<tr valign="top">
				<th align="left"><?php _e('Width x height (in pixel)','nggallery') ?></th>
				<td>
				<?php include(dirname(__FILE__) . '/thumbnails-template.php'); ?>
				</td>
			</tr>
			<tr valign="top">
				<th align="left"><?php _e('Set fix dimension','nggallery') ?></th>
				<td><input type="checkbox" name="thumbfix" value="1" <?php checked('1', $ngg->options['thumbfix']); ?> />
				<br /><small><?php _e('Ignore the aspect ratio, no portrait thumbnails','nggallery') ?></small></td>
			</tr>
		  	<tr align="right">
		    	<td colspan="2" class="submit">
		    		<input class="button-primary" type="submit" name="TB_NewThumbnail" value="<?php _e('OK', 'nggallery');?>" />
		    		&nbsp;
		    		<input class="button-secondary dialog-cancel" type="reset" value="&nbsp;<?php _e('Cancel', 'nggallery'); ?>&nbsp;" />
		    	</td>
			</tr>
		</table>
		</form>
	</div>
	<!-- /#new_thumbnail -->

	<script type="text/javascript">
	/* <![CDATA[ */
	jQuery(document).ready(function(){columns.init('nggallery-manage-images');});
	/* ]]> */
	</script>
	<?php
}

/**
 * Construtor class to create the table layout
 *
 * @package WordPress
 * @subpackage List_Table
 * @since 1.8.0
 * @access private
 */
class _NGG_Images_List_Table extends WP_List_Table {
	var $_screen;
	var $_columns;

	function _NGG_Images_List_Table( $screen ) {
		if ( is_string( $screen ) )
			$screen = convert_to_screen( $screen );

		$this->_screen = $screen;
		$this->_columns = array() ;

		add_filter( 'manage_' . $screen->id . '_columns', array( &$this, 'get_columns' ), 0 );
	}

	function get_column_info() {

		$columns = get_column_headers( $this->_screen );
		$hidden = get_hidden_columns( $this->_screen );
		$_sortable = $this->get_sortable_columns();
        $sortable = array();

		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) )
				continue;

			$data = (array) $data;
			if ( !isset( $data[1] ) )
				$data[1] = false;

			$sortable[$id] = $data;
		}

		return array( $columns, $hidden, $sortable );
	}

    // define the columns to display, the syntax is 'internal name' => 'display name'
	function get_columns() {
    	$columns = array();

    	$columns['cb'] = '<input name="checkall" type="checkbox" onclick="checkAll(document.getElementById(\'updategallery\'));" />';
    	$columns['id'] = __('ID');
    	$columns['thumbnail'] = __('Thumbnail', 'nggallery');
    	$columns['filename'] = __('Filename', 'nggallery');
    	$columns['alt_title_desc'] = __('Alt &amp; Title Text', 'nggallery') . ' / ' . __('Description', 'nggallery');
    	$columns['tags'] = __('Tags (comma separated list)', 'nggallery');
    	$columns = apply_filters('ngg_manage_images_columns', $columns);

    	return $columns;
	}

	function get_sortable_columns() {
		return array();
	}

	function the_list()
	{

	}
}

?>
