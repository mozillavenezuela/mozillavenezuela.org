<?php

class A_Gallery_Storage_Frame_Event extends Mixin
{
	function initialize()
	{
		$this->object->add_post_hook(
			'generate_thumbnail',
			'After a new thumbnail has been generated, emits a frame event',
			get_class(),
			'emit_modified_thumbnail_event'
		);
	}

	function emit_modified_thumbnail_event($image)
	{
        $controller = $this->get_registry()->get_utility('I_Display_Type_Controller');
		$events     = $this->get_registry()->get_utility('I_Frame_Event_Publisher', 'attach_to_post');
		$mapper	    = $this->get_registry()->get_utility('I_Image_Mapper');
		$storage    = $this->get_registry()->get_utility('I_Gallery_Storage');
        $app        = $this->get_registry()->get_utility('I_Router')->get_routed_app();

		$image	= $mapper->find($image);
        $image->thumb_url = $controller->set_param_for(
            $app->get_routed_url(TRUE),
            'timestamp',
            time(),
            NULL,
            $storage->get_thumb_url($image)
        );

        if (is_admin()) {

			$event = new stdClass();
			$event->pid = $image->{$image->id_field};
			$event->id_field = $image->id_field;
			$event->thumb_url = $image->thumb_url;

			$events->add_event(
				array(
					'event' => 'thumbnail_modified',
					'image' => $event,
				)
			);
		}
    }
}