<?php

class C_Image_Mapper extends C_CustomTable_DataMapper_Driver
{
    public static $_instances = array();

	/**
	 * Defines the gallery image mapper
	 * @param type $context
	 */
	function define($context=FALSE, $not_used=FALSE)
	{
		// Add 'attachment' context
		if (!is_array($context)) $context = array($context);
		array_push($context, 'attachment');

		// Define the mapper
		$this->_primary_key_column = 'pid';
		parent::define('ngg_pictures', $context);
		$this->add_mixin('Mixin_NextGen_Table_Extras');
		$this->add_mixin('Mixin_Gallery_Image_Mapper');
		$this->implement('I_Image_Mapper');
		$this->set_model_factory_method('image');

		// Define the columns
		$this->define_column('pid', 		'BIGINT', 0);
		$this->define_column('image_slug',	'VARCHAR(255)');
		$this->define_column('post_id',		'BIGINT', 0);
		$this->define_column('galleryid',	'BIGINT', 0);
		$this->define_column('filename',	'VARCHAR(255)');
		$this->define_column('description',	'TEXT');
		$this->define_column('alttext',		'TEXT');
		$this->define_column('imagedate',	'DATETIME');
		$this->define_column('exclude',		'INT', 0);
		$this->define_column('sortorder',	'BIGINT', 0);
		$this->define_column('meta_data',	'TEXT');
        $this->define_column('extras_post_id', 'BIGINT', 0);
		$this->define_column('updated_at',  'BIGINT');

		// Mark the columns which should be unserialized
		$this->add_serialized_column('meta_data');
	}

	function initialize($object_name=FALSE)
	{
		parent::initialize('ngg_pictures');
	}

    static function get_instance($context = False)
    {
        if (!isset(self::$_instances[$context]))
        {
            self::$_instances[$context] = new C_Image_Mapper($context);
        }
        return self::$_instances[$context];
    }
}

/**
 * Sets the alttext property as the post title
 */
class Mixin_Gallery_Image_Mapper extends Mixin
{
	function destroy($image)
	{
		$retval = $this->call_parent('destroy',$image);
		C_Photocrati_Cache::flush();
		return $retval;
	}


    function _save_entity($entity)
    {
		$entity->updated_at = time();

        // If successfully saved, then import metadata and
        $retval = $this->call_parent('_save_entity', $entity);
        if ($retval) {
            include_once(NGGALLERY_ABSPATH.'/admin/functions.php');
            $image_id = $this->get_id($entity);
			if (!isset($entity->meta_data['saved'])) {
				nggAdmin::import_MetaData($image_id);
			}
			C_Photocrati_Cache::flush('displayed_gallery_rendering');
        }
        return $retval;
    }

    /**
     * Retrieves the id from an image
     * @param $image
     * @return bool
     */
    function get_id($image)
    {
        $retval = FALSE;

        // Have we been passed an entity and is the id_field set?
        if ($image instanceof stdClass) {
            if (isset($image->id_field)) {
                $retval = $image->{$image->id_field};
            }
        }

        // Have we been passed a model?
        else $retval = $image->id();

        // If we still don't have an id, then we'll lookup the primary key
        // and try fetching it manually
        if (!$retval) {
            $key = $this->object->get_primary_key_column();
            $retval = $image->$key;

        }

        return $retval;
    }


	function get_post_title($entity)
	{
		return $entity->alttext;
	}

	function set_defaults($entity)
	{
		// If not set already, we'll add an exclude property. This is used
		// by NextGEN Gallery itself, as well as the Attach to Post module
		$this->object->_set_default_value($entity, 'exclude', 0);

		// Ensure that the object has a description attribute
		$this->object->_set_default_value($entity, 'description', '');

		// If not set already, set a default sortorder
		$this->object->_set_default_value($entity, 'sortorder', 0);

		// The imagedate must be set
        if ((!isset($entity->imagedate)) OR is_null($entity->imagedate) OR $entity->imagedate == '0000-00-00 00:00:00')
            $entity->imagedate = date("Y-m-d H:i:s");

		// If a filename is set, and no alttext is set, then set the alttext
		// to the basename of the filename (legacy behavior)
		if (isset($entity->filename)) {
			$path_parts = pathinfo( $entity->filename);
			$alttext = ( !isset($path_parts['filename']) ) ?
				substr($path_parts['basename'], 0,strpos($path_parts['basename'], '.')) :
				$path_parts['filename'];
			$this->object->_set_default_value($entity, 'alttext', $alttext);
		}

        // Set unique slug
        if (isset($entity->alttext) && !isset($entity->image_slug)) {
            $entity->image_slug = nggdb::get_unique_slug( sanitize_title_with_dashes( $entity->alttext ), 'image' );
        }

		// Ensure that the exclude parameter is an integer or boolean-evaluated
		// value
		if (is_string($entity->exclude)) $entity->exclude = intval($entity->exclude);

		// Trim alttext and description
		$entity->description = trim($entity->description);
		$entity->alttext	 = trim($entity->alttext);
	}
}
