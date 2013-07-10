<?php

/**
 * @package WordPress Code Snippet
 * @version 2.0.2
 * @author Allan Collins
 *
 * @todo TinyMCE Button Integration
 * @todo CodePress or Alternative - Disable CodePress for non-compatible browsers.
 */
class wcs {

    var $db;
 // $wpdb Database Object
    var $msg;
 // Add/Update message.
    var $lang = array(); // Array of used languages for snippet view.

    /*
     * Constructor: Sets $db variable, checks setup, checks remove and checks save options.
     */
    function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->setup();
        $this->remove();
        $this->save();
    }

    /*
     * Sets up WordPress Admin Pages.
     */

    function setupPages() {
        if (function_exists('add_menu_page')) {
            add_menu_page('Code Library', 'Code Library', 7, __FILE__, array($this, 'codeLibrary'));
        }
    }

    /*
     * Show "Code Library" Page.
     */

    function codeLibrary() {
        if (isset($_GET['wcsUpgrade'])) {
            $this->upgrade();
        }
        if (isset($_GET['wcsID'])) {
            $wcsID = $_GET['wcsID'] - 0;
            $this->editCode($wcsID);
        } else {
            $upgradeNeeded = $this->needUpgrade();  //Will check to see if there are any old version snippets.


            $q = "SELECT `id`,`name`,`lang`,`date` FROM wcs_lib";
            $lib = $this->db->get_results($q, OBJECT);
            include "pages/codelibrary.php";
        }
    }

    /*
     * Show Edit Code Page
     */

    function editCode($id) {
        if ($id != 0) {
            $q = "SELECT * FROM wcs_lib WHERE id='$id'";

            $snip = $this->db->get_row($q, OBJECT);
            $tmp = stripslashes($snip->snippet);
            $snip->snippet = $tmp;
            $titleType = "Update";
        } else {
            $snip = new stdClass();
            $snip->id = 0;
            $snip->name = "";
            $snip->snippet = "";
            $snip->lang = 0;
            $titleType = "Add";
        }
        include "pages/editcode.php";
    }

    /*
     * Setup MySQL Tables / Verify Table Exists.
     */

    function setup() {
        $q = "
CREATE TABLE IF NOT EXISTS `wcs_lib` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `lang` varchar(20) NOT NULL,
  `snippet` text NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";

        $r = $this->db->query($q);
        $q = "CREATE TABLE IF NOT EXISTS `wcs_lang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `short_name` varchar(20) NOT NULL,
  `codepress` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";


        $r = $this->db->query($q);



        $q = "INSERT INTO `wcs_lang` (`id`, `short_name`, `codepress`, `name`) VALUES
(1, 'Xml', 'html', 'HTML / XML'),
(2, 'Css', 'css', 'CSS'),
(3, 'JScript', 'javascript', 'Javascript'),
(4, 'Php', 'php', 'PHP'),
(5, 'Sql', 'sql', 'SQL'),
(6, 'CSharp', 'csharp', 'C#'),
(7, 'Vb', 'vbscript', 'VB'),
(8, 'Cpp', 'generic', 'C++'),
(9, 'Java', 'java', 'Java'),
(10, 'Python', 'generic', 'Python'),
(11, 'Ruby', 'ruby', 'Ruby'),
(12, 'Delphi', 'generic', 'Delphi');
";

        $r = $this->db->query($q);
    }

    /*
     * Admin Header
     */

    function adminHead() {
        echo "<link rel=\"stylesheet\" href=\"" . get_option('siteurl') . "/wp-content/plugins/wordpress-code-snippet/css/admin.css\" />";
        echo "<link rel=\"stylesheet\" href=\"" . get_option('siteurl') . "/wp-content/plugins/wordpress-code-snippet/ui-lightness/jquery-ui-1.7.3.custom.css\" />";
    }

    /*
     * Load Admin Javascript.
     */

    function adminInit() {
       // wp_enqueue_script('codepress');
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-resizable');
    }

    /*
     * Pulls the snippet object by the $id parameter.
     */

    function snipById($id) {
        $q = "SELECT * FROM wcs_lib WHERE id='$id'";
        $r = $this->db->get_row($q, OBJECT);
        return $r;
    }

    /*
     * Populates the language dropdown element.
     */

    function langDrop($id=0) {
        $q = "SELECT * FROM wcs_lang";
        $r = $this->db->get_results($q, OBJECT);
        foreach ($r as $v) {
            $selected = "";
            if ($v->id == $id) {
                $selected = "selected=\"selected\"";
            }
            echo "<option value=\"$v->id\" $selected>$v->name</option>";
        }
    }

    /*
     * Gets the language's name by the $id parameter.
     */

    function langById($id) {
        $q = "SELECT `name` FROM wcs_lang WHERE id='$id'";
        return $this->db->get_var($q);
    }

    /*
     * Gets the language's syntax highlighter name by the $id parameter.
     */

    function langByIdShort($id) {
        $q = "SELECT `short_name` FROM wcs_lang WHERE id='$id'";
        return $this->db->get_var($q);
    }

    /*
     * Gets the language's CodePress name by the $id parameter.
     */

    function langByIdCodepress($id) {
        $q = "SELECT `codepress` FROM wcs_lang WHERE id='$id'";
        return $this->db->get_var($q);
    }

    /*
     * Gets the language's ID by the $name parameter.
     */

    function getLangByName($name) {
        $q = "SELECT `id` FROM wcs_lang WHERE short_name='$name'";
        return $this->db->get_var($q);
    }

    /*
     * Checks if removal is necessary.
     * Removes by the wcsID querystring.
     */

    function remove() {
        if (!isset($_GET['removeit'])) {
            return false;
        }
        $id = $_GET['wcsID'] - 0;

        $q = "DELETE FROM wcs_lib WHERE id='$id'";
        $r = $this->db->query($q);
        if ($r) {
            $this->msg = "Snippet Deleted.";
        }
        unset($_GET['wcsID']);
    }

    /*
     * Checks if saving is necessary.
     * Saves by the wcsID querystring OR adds of string is set to 0.
     */

    function save() {
        if (!isset($_GET['wcs-update'])) {
            return false;
        }
        $id = $_GET['wcsID'] - 0;
        $snippet = addslashes($_POST['snippet']);
        $name = strip_tags($_POST['name']);
        $lang = $_POST['lang'];
        if ($id == 0) {
            $q = "INSERT INTO wcs_lib SET `name`='$name',`snippet`='$snippet',`lang`='$lang',`date`=NOW()";
        } else {
            $q = "UPDATE wcs_lib SET name='$name',snippet='$snippet',lang='$lang',`date`=NOW() WHERE id='$id'";
        }

        $r = $this->db->query($q);

        if ($r) {
            $this->msg = "Snippet Saved.";
            if ($id == 0) {
                $_GET['wcsID'] = $this->db->insert_id;
            }
        } else {
            $this->msg = "Error Saving Snippet.";
        }
    }

    /*
     * Javascript added to frontend header.
     */

    function pageHead() {
?>
        <script type="text/javascript" src="<?php bloginfo('url'); ?>/wp-content/plugins/wordpress-code-snippet/scripts/shCore.js"></script>
        <link href="<?php bloginfo('url'); ?>/wp-content/plugins/wordpress-code-snippet/styles/shCore.css" rel="stylesheet" type="text/css" />
        <link type="text/css" rel="Stylesheet" href="<?php bloginfo('url'); ?>/wp-content/plugins/wordpress-code-snippet/styles/shThemeDefault.css"/>
<?php
    }

    /*
     * Javascript added to frontend footer.
     */

    function pageFoot() {
 ?>
        <script type="text/javascript">
            SyntaxHighlighter.all()
        </script>
<?php
        $linkText = "";
        $link = get_option("wcsLink");
        if ($link) {
            $linkText = "<small>Wordpress Code Snippet by</small> <a href=\"http://www.allancollins.net/\"><small>Allan Collins</small></a>";
        }
        echo $linkText;
    }

    function ajax() {
        if (!isset($_GET['wcsjax'])) {
            return false;
        }
        if (isset($_POST['updatelink'])){
            if (isset($_POST['wcslink'])){
                update_option('wcsLink',true);
                echo "active";
            }else{
                update_option('wcsLink',false);
                echo "not active";
            }
           
        }
        die();
    }

    /*
     * Filters the content looking for WCS comment code.
     * Populates $this->lang array and then outputs needed language files.
     */

    function contentFilter($text) {
        $str = "<!--WCS[2]-->";
        preg_match_all('/<!--WCS\[(.*?)\]-->/msi', $text, $matches);

        if (isset($matches[1])) {
            foreach ($matches[1] as $id) {
                if ($id != '') {
                    $snip = $this->snipById($id);
                    $language = $this->langByIdShort($snip->lang);
                    if (!in_array($language, $this->lang)) {
                        $this->lang[] = $language;
                    }

                    $language = strtolower($language);
                    if ($language == 'jscript') {
                        $language = 'js';
                    }
                    $snip->snippet = htmlentities(stripslashes($snip->snippet));
                    $final = "<pre class=\"brush: $language\">$snip->snippet</pre>";
                    $text = str_replace("<!--WCS[$id]-->", $final, $text);
                }
            }
        }
?><?php
        $script = "";
        foreach ($this->lang as $lang) {
            ob_start();
?>
            <script type="text/javascript" src="<?php bloginfo('url'); ?>/wp-content/plugins/wordpress-code-snippet/scripts/shBrush<?php echo $lang; ?>.js"></script>
<?php
            $script.=ob_get_contents();
            ob_end_clean();
        }

        return $script . $text;
    }

    /*
     * Check to see if there are old snippets from the previous version of WCS.
     */

    function needUpgrade() {
         $prefix = $this->db->prefix;
        $q = "SELECT post_id FROM " . $prefix . "postmeta WHERE meta_key='code_editor' AND meta_value!='' LIMIT 1";
        $r = $this->db->get_var($q);
        if ($r) {
            return true;
        }
        return false;
    }

    /*
     * Upgrades from version 1.x of WordPress Code Snippet.
     */

    function upgrade() {
        $prefix = $this->db->prefix;

        $q = "SELECT post_id,meta_value FROM " . $prefix . "postmeta WHERE meta_key='code_editor' AND meta_value!=''";
        $r = $this->db->get_results($q, OBJECT);

        foreach ($r as $v) {
            $snippet = $v->meta_value;
            $snippet_lang = get_post_meta($v->post_id, "code_type", true);
            if ($snippet != '') {
                $langID = $this->getLangByName($snippet_lang);
                if (!$langID) {
                    $langID = 1;
                }
                $post = get_post($v->post_id);
                $snipArr = array('snippet' => $snippet, 'lang' => $langID, 'name' => $post->post_title);
                $newID = $this->upgradeSave($snipArr);
                if (!$newID) {
                    echo "Failed to import: $post->post_name. <br/>";
                    continue;
                }
                $content = $post->post_content . "<!--WCS[$newID]-->";

                $my_post = array();
                $my_post['ID'] = $post->ID;
                $my_post['post_content'] = $content;

// Update the post into the database
                wp_update_post($my_post);

                delete_post_meta($post->ID, "code_editor");
                delete_post_meta($post->ID, "code_type");
            }
        }

        $this->msg = "Finished Upgrading From Older Plugin Version.";
    }

    /*
     * Saves Upgrade Snippet.
     */

    function upgradeSave($arr) {
        // echo "<pre>";
//print_r($arr);
//echo "</pre>";
        extract($arr);
        $snippet = addslashes($snippet);
        $q = "INSERT INTO wcs_lib SET `name`='$name',`snippet`='$snippet',`lang`='$lang',`date`=NOW()";
        $r = $this->db->query($q);
        return $this->db->insert_id;
    }

    /*
     * @TODO TinyMCE Button Integration
     *
     */

    function addButtons() {

// Don't bother doing this stuff if the current user lacks permissions
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
            return;

// Add only in Rich Editor mode
        if (get_user_option('rich_editing') == 'true') {
            add_filter("mce_external_plugins", array($this, "add_tinymce_plugin"));
            add_filter('mce_buttons', array($this, 'register_button'));
        }
    }

    function add_tinymce_plugin($plugin_array) {

        $plugin_array['wcs'] = get_option('siteurl') . '/wp-content/plugins/wordpress-code-snippet/tinymce/editor_plugin.js';
        return $plugin_array;
    }

    function register_button($buttons) {
        array_push($buttons, "separator", "wcs");
        return $buttons;
    }

}
?>