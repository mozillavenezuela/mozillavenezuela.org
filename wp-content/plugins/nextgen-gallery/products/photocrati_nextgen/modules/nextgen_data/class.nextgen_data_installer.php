<?php

class C_NextGen_Data_Installer extends C_NggLegacy_Installer
{
	function get_registry()
	{
		return C_Component_Registry::get_instance();
	}

	function install()
	{
         $this->remove_table_extra_options();
	}

    function remove_table_extra_options()
    {
        global $wpdb;

        $likes = array(
            "option_name LIKE '%ngg_gallery%'",
            "option_name LIKE '%ngg_pictures%'",
            "option_name LIKE '%ngg_album%'"
        );

        $sql = "DELETE FROM {$wpdb->options} WHERE ".implode(" OR ", $likes);
        $wpdb->query($sql);
    }

	function uninstall($hard = FALSE)
	{
		if ($hard) {
            /* Yes: this is commented twice.
		// TODO for now never delete galleries/albums/content
#			$mappers = array(
#				$this->get_registry()->get_utility('I_Album_Mapper'),
#				$this->get_registry()->get_utility('I_Gallery_Mapper'),
#				$this->get_registry()->get_utility('I_Image_Mapper'),
#			);

#			foreach ($mappers as $mapper) {
#				$mapper->delete()->run_query();
#			}

#			// Remove ngg tags
#			global $wpdb;
#			$wpdb->query("DELETE FROM {$wpdb->terms} WHERE term_id IN (SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy='ngg_tag')");
#			$wpdb->query("DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy='ngg_tag'");
            */
		}
	}
}
