<?php
require_once dirname(__FILE__) . '/admin/JAW_Walker_Category_Checklist.php';

/*
  Plugin Name: jQuery Archive List Widget
  Plugin URI: http://skatox.com/blog/jquery-archive-list-widget/
  Description: A widget for displaying an archive list with some effects.
  Version: 3.0.3
  Author: Miguel Useche
  Author URI: http://migueluseche.com/
  License: GPL2
  Copyleft 2009-2015  Miguel Useche  (email : migueluseche@skatox.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class JQArchiveList extends WP_Widget
{
    public $config;

    const JS_FILENAME = "jal.js";

    public $defaults = array(
            'title' => '',
            'symbol' => 1,
            'ex_sym' => '►',
            'con_sym' => '▼',
            'only_sym_link' => 0,
            'effect' => 'slide',
            'fx_in' => 'slideDown',
            'fx_out' => 'slideUp',
            'month_format' => 'full',
            'showpost' => 0,
            'showcount' => 0,
            'expand' => 'none',
            'excluded'=>NULL,
            'type'=>'post'
        );

    public function __construct()
    {
        add_shortcode('jQuery Archive List', array($this,'filter'));
        add_filter('widget_text', 'do_shortcode');

        if (function_exists("load_plugin_textdomain")) {
            load_plugin_textdomain('jalw_i18n', null, basename(dirname(__FILE__)) . '/lang');
        }
        load_default_textdomain();

        parent::__construct(
            'jal_widget',
            'jQuery Archive List Widget',
            array(
                'description' => __(
                    __('A widget for displaying an archive list with some effects.', 'jalw_i18n')
                )
            )
        );
    }

    /**
     * Function to enqueue custom JS file to create animations
     */
    protected function enqueueScript()
    {
        if (function_exists("wp_enqueue_script")) {
            wp_enqueue_script('jquery_archive_list', plugins_url( self::JS_FILENAME , __FILE__ ), array('jquery'), false, true);
        }
    }

    function widget($args, $instance)
    {
        $instance['excluded'] = empty($instance['excluded']) ? array() : implode(',', unserialize($instance['excluded']));
        $instance['type']     = empty($instance['type']) ? 'post' : $instance['type'];
        $this->config         = $instance;

        $this->enqueueScript();

        //Prints widget
        extract($args);
        echo $before_widget;
        echo $before_title;
        echo $this->config['title'];
        echo $after_title;
        echo $this->buildHtml();
        echo $after_widget;
    }

    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;

        if(empty($new_instance['title'])) {
            $instance['title'] =  __('Archives', 'jalw_i18n');
        }
        else {
            $instance['title'] =  stripslashes(strip_tags($new_instance['title']));
        }

        $instance['symbol']        = $new_instance['symbol'];
        $instance['effect']        = stripslashes($new_instance['effect']);
        $instance['month_format']  = stripslashes($new_instance['month_format']);
        $instance['showpost']      = empty($new_instance['showpost']) ? 0 : 1;
        $instance['showcount']     = empty($new_instance['showcount']) ? 0 : 1;
        $instance['onlycategory']  = empty($new_instance['onlycategory']) ? 0 : 1;
        $instance['only_sym_link'] = empty($new_instance['only_sym_link']) ? 0 : 1;
        $instance['expand']        = $new_instance['expand'];
        $instance['excluded']      = !empty($new_instance['excluded']) ? serialize($new_instance['excluded']) : NULL;
        $instance['type']          = !empty($new_instance['type']) ? stripslashes($new_instance['type']) : 'post';

        switch ($new_instance['symbol'])
        {
            case '0':
                $instance['ex_sym']  = ' ';
                $instance['con_sym'] = ' ';
                break;
            case '1':
                $instance['ex_sym']  = '►';
                $instance['con_sym'] = '▼';
                break;
            case '2':
                $instance['ex_sym']  = '(+)';
                $instance['con_sym'] = '(-)';
                break;
            case '3':
                $instance['ex_sym']  = '[+]';
                $instance['con_sym'] = '[-]';
                break;
            default:
                $instance['ex_sym']  = '>';
                $instance['con_sym'] = 'v';
                break;
        }

        switch ($new_instance['effect']) {
            case 'slide':
                $instance['fx_in']  = 'slideDown';
                $instance['fx_out'] = 'slideUp';
                break;
            case 'fade':
                $instance['fx_in']  = 'fadeIn';
                $instance['fx_out'] = 'fadeOut';
                break;
            default:
                $instance['fx_in']  = 'none';
                $instance['fx_out'] = 'none';
        }
        return $instance;
    }

    function form($instance)
    {
        $instance = wp_parse_args( (array) $instance, $this->defaults );
    ?>
        <dl>
            <dt><strong><?php _e('Title', 'jalw_i18n') ?></strong></dt>
            <dd>
                <input name="<?php echo $this->get_field_name( 'title' )?>" type="text" value="<?php echo $instance['title']; ?>" />
            </dd>
            <dt><strong><?php _e('Trigger Symbol', 'jalw_i18n') ?></strong></dt>
            <dd>
                <select id="<?php echo $this->get_field_id( 'symbol' ) ?>" name="<?php echo $this->get_field_name( 'symbol' ) ?>">
                    <option value="0"  <?php if ($instance['symbol'] == '0') echo 'selected="selected"' ?> >
                        <?php _e('Empty Space', 'jalw_i18n') ?>
                    </option>
                    <option value="1" <?php if ($instance['symbol'] == '1') echo 'selected="selected"' ?> >
                        ► ▼
                    </option>
                    <option value="2" <?php if ($instance['symbol'] == '2') echo 'selected="selected"' ?> >
                        (+) (-)
                    </option>
                    <option value="3" <?php if ($instance['symbol'] == '3') echo 'selected="selected"' ?> >
                        [+] [-]
                    </option>
                </select>
            </dd>
            <dt><strong><?php _e('Effect', 'jalw_i18n') ?></strong></dt>
            <dd>
                <select id="<?php echo $this->get_field_id( 'effect' ) ?>" name="<?php echo $this->get_field_name( 'effect' ) ?>">
                    <option value="none" <?php if ($instance['effect'] == '') echo 'selected="selected"'?>>
                        <?php _e('None', 'jalw_i18n') ?>
                    </option>
                    <option value="slide"  <?php if ($instance['effect'] == 'slide') echo 'selected="selected"' ?> >
                        <?php _e('Slide (Accordion)', 'jalw_i18n') ?>
                    </option>
                    <option value="fade" <?php if ($instance['effect'] == 'fade') echo 'selected="selected"' ?> >
                        <?php _e('Fade', 'jalw_i18n') ?>
                    </option>
                </select>
            </dd>
            <dt><strong><?php _e('Month Format', 'jalw_i18n') ?></strong></dt>
            <dd>
                <select id="<?php echo $this->get_field_id( 'month_format' ) ?>" name="<?php echo $this->get_field_name( 'month_format' ) ?>">
                    <option value="full" <?php if ($instance['month_format'] == 'full') echo 'selected="selected"'?> >
                        <?php _e('Full Name (January)', 'jalw_i18n') ?>
                    </option>
                    <option value="short" <?php if ($instance['month_format'] == 'short') echo 'selected="selected"'?> >
                        <?php _e('Short Name (Jan)', 'jalw_i18n') ?>
                    </option>
                    <option value="number" <?php if ($instance['month_format'] == 'number') echo 'selected="selected"' ?> >
                        <?php _e('Number (01)', 'jalw_i18n') ?>
                    </option>
                </select>
            </dd>
            <dt><strong><?php _e('Expand', 'jalw_i18n') ?></strong></dtd>
            <dd>
                <select id="<?php echo $this->get_field_id( 'expand' ) ?>" name="<?php echo $this->get_field_name( 'expand' ) ?>">
                    <option value="" <?php if ($instance['expand'] == '') echo 'selected="selected"'?>>
                        <?php _e('None', 'jalw_i18n') ?>
                    </option>
                    <option value="all" <?php if ($instance['expand'] == 'all') echo 'selected="selected"'?> >
                        <?php _e('All', 'jalw_i18n') ?>
                    </option>
                    <option value="current" <?php if ($instance['expand'] == 'current') echo 'selected="selected"'?> >
                        <?php _e('Current or post date', 'jalw_i18n') ?>
                    </option>
                    <option value="current_post" <?php if ($instance['expand'] == 'current_post') echo 'selected="selected"'?> >
                        <?php _e('Only post date', 'jalw_i18n') ?>
                    </option>
                    <option value="current_date" <?php if ($instance['expand'] == 'current_date') echo 'selected="selected"'?> >
                        <?php _e('Only current date', 'jalw_i18n') ?>
                    </option>
                </select>
            </dd>

            <dt><strong><?php _e('Post type', 'jalw_i18n') ?></strong></dtd>
            <dd>
                <select id="<?php echo $this->get_field_id( 'type' ) ?>" name="<?php echo $this->get_field_name( 'type' ) ?>">
                <?php
                    $types = get_post_types( NULL, 'objects');

                    foreach($types as $type_id => $type)
                    {
                        $checked =  $instance['type'] == $type_id ? 'selected="selected"' : '';
                        echo "<option value=\"{$type_id}\" {$checked}>{$type->label}</option>";
                    }
                ?>
                </select>
            </dd>

            <dt><strong><?php _e('Extra options', 'jalw_i18n') ?></strong></dt>
            <dd>
                <input id="<?php echo $this->get_field_id( 'showcount' ) ?>" name="<?php echo $this->get_field_name( 'showcount' ) ?>" type="checkbox" <?php if ($instance['showcount']) echo 'checked="checked"' ?> />
                <label for="<?php echo $this->get_field_id( 'showcount' ) ?>">
                    <?php _e('Show number of posts', 'jalw_i18n') ?>
                </label>
            </dd>
            <dd>
                <input id="<?php echo $this->get_field_id( 'showpost' ) ?>" name="<?php echo $this->get_field_name( 'showpost' ) ?>" type="checkbox" <?php if ($instance['showpost']) echo 'checked="checked"' ?> />
                <label for="<?php echo $this->get_field_id( 'showpost' ) ?>">
                    <?php _e('Show posts under months', 'jalw_i18n') ?>
                </label>
            </dd>
            <dd>
                <input id="<?php echo $this->get_field_id( 'onlycategory' ) ?>" name="<?php echo $this->get_field_name( 'onlycategory' ) ?>" type="checkbox" <?php if ($instance['onlycategory']) echo 'checked="checked"' ?> />
                <label for="<?php echo $this->get_field_id( 'onlycategory' ) ?>">
                    <?php _e('Show only post from selected category in a category page', 'jalw_i18n') ?>
                </label>
            </dd>
            <dd>
                <input id="<?php echo $this->get_field_id( 'only_sym_link' ) ?>" name="<?php echo $this->get_field_name( 'only_sym_link' ) ?>" type="checkbox" value="1" <?php if ($instance['only_sym_link']) echo 'checked="checked"' ?> />
                <label for="<?php echo $this->get_field_id( 'only_sym_link' ) ?>">
                    <?php _e('Only expand/reduce by clicking the symbol', 'jalw_i18n') ?>
                </label>
            </dd>
            <dt><strong><?php _e('Exclude categories', 'jalw_i18n') ?></strong></dt>
            <dd>
                <div style="height: 200px; overflow-y: scroll;">
                    <?php
                        $walker = new JAW_Walker_Category_Checklist(
                            $this->get_field_name('excluded'),
                            $this->get_field_id('excluded')
                        );
                        wp_category_checklist( 0, 0, unserialize($instance['excluded']), NULL, $walker, true);
                    ?>
                </div>
            </dd>
        </dl>
        <?php
    }

    protected function buildSqlWhere($year = NULL, $month = NULL)
    {
        global $wpdb;

        $where = "WHERE post_type = '{$this->config['type']}' AND post_status = 'publish' ";

        if ( $year ) {
            $where .= sprintf("AND YEAR(post_date) = %s ", $year);
        }

        if ( $month ) {
            $where .= sprintf("AND MONTH(post_date) = %s ", $month);
        }

        if (!empty($this->config['excluded'])) {
            $where .= "AND {$wpdb->term_taxonomy}.term_id NOT IN ({$this->config['excluded']}) ";
        }

        if($this->config['onlycategory'] && is_category()) {
            $where .= sprintf("AND {$wpdb->term_taxonomy}.term_id=%d ", get_query_var('cat'));
        }

        if (!empty($this->config['excluded']) || ($this->config['onlycategory'] && is_category())) {
            $where .= "AND {$wpdb->term_taxonomy}.taxonomy = 'category' ";
        }

        return apply_filters('getarchives_where', $where);
    }

    protected function buildSqlJoin()
    {
        global $wpdb;

        $join = '';

        if (!empty($this->config['excluded']) || ($this->config['onlycategory'] && is_category())) {
            $join = " LEFT JOIN {$wpdb->term_relationships} ON({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)";
            $join .= " LEFT JOIN {$wpdb->term_taxonomy} ON({$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id) ";
        }

        return apply_filters('getarchives_join', $join);
    }

    protected function getYears()
    {
        global $wpdb;

        $where = $this->buildSqlWhere();
        $join = $this->buildSqlJoin();

        $sql = "SELECT JAL.year, COUNT(JAL.ID) as `posts` FROM (" .
            "SELECT DISTINCT YEAR(post_date) AS `year`, ID " .
            "FROM {$wpdb->posts} {$join} {$where}" .
            ") JAL GROUP BY JAL.year ORDER BY JAL.year DESC";

        return $wpdb->get_results($sql);
    }

    protected function getMonths($year)
    {
        global $wpdb;

        $where = $this->buildSqlWhere($year);
        $join = $this->buildSqlJoin();

        $sql = "SELECT JAL.year, JAL.month, COUNT(JAL.ID) as `posts` FROM (" .
            "SELECT DISTINCT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`,ID " .
            "FROM {$wpdb->posts} {$join} {$where}" .
            ") JAL GROUP BY JAL.year,JAL.month ORDER BY JAL.year,JAL.month DESC";

        return $wpdb->get_results($sql);
    }

    protected function getPosts($year, $month)
    {
        global $wpdb;

        if (empty($year) || empty($month)) {
            return null;
        }

        $where = $this->buildSqlWhere($year, $month);
        $join  = $join = $this->buildSqlJoin();

        $sql = "SELECT DISTINCT ID, post_title, post_name " .
            "FROM {$wpdb->posts} {$join} {$where}" .
            "ORDER BY post_date DESC";

        return $wpdb->get_results($sql);
    }

    /**
     * Builds archive list's HTML code
     */
    protected function buildHtml()
    {
        global $wp_locale;

        $years = $this->getYears();
        $html = '<ul class="jaw_widget">';
        $postId = !empty($this->config['expand']) ? get_the_ID() : -1;


        if ($postId >= 0)
        {
            $postData  = get_post($postId);
            $postYear  = 1 * substr($postData->post_date_gmt, 0, 4);
            $postMonth = 1 * substr($postData->post_date_gmt, 5, 2);
        } else {
            $postData = NULL;
            $postYear = NULL;
            $postMonth = NULL;
        }

        if(count($years) < 1) {
            $html .= '<li>' . __('There are no post to show.', 'jalw_i18n') . '</li>';
        }

        //Prints Years
        for ($i = 0; $i < count($years); $i++)
        {
            $expandByPostDate = $years[$i]->year == $postYear && ($this->config['expand'] == 'current' || $this->config['expand'] == 'current_post');
            $expandByCurrDate = $years[$i]->year == date('Y') && ($this->config['expand'] == 'current' || $this->config['expand'] == 'current_date');
            $expandYear = $expandByCurrDate || $expandByPostDate || ($this->config['expand'] == 'all');

            $yearLink = get_year_link($years[$i]->year);

            if ($expandYear)
            {
                $class  = 'jaw_years expanded';
                $symbol = htmlspecialchars($this->config['con_sym']);
            } else {
                $class  = 'jaw_years';
                $symbol = htmlspecialchars($this->config['ex_sym']);
            }

            $html.= "\n<li class=\"{$class}\">" .
                "<a class=\"jaw_years\" title=\"{$years[$i]->year}\" href=\"{$yearLink}\">" .
                "<span class=\"jaw_symbol\">{$symbol}</span>";

            if($this->config['only_sym_link']) {
                $html.= "</a><a href=\"{$yearLink}\" title=\"{$years[$i]->year}\">";
            }
            $html.= " {$years[$i]->year}";


            //Prints number of post_date
            if ($this->config['showcount']) {
                $html.= " ({$years[$i]->posts})";
            }

            $html.= '</a><ul>';

            //Prints Months
            $months = $this->getMonths($years[$i]->year);

            foreach ($months as $month)
            {
                $month_url = get_month_link($years[$i]->year, $month->month);

                $expandByPostDateMonth = ($postId >= 0) && ($month->month == $postMonth) && ($years[$i]->year == $postYear) && ($this->config['expand'] == 'current' || $this->config['expand'] == 'current_post');
                $expandByCurrDateMonth = ($years[$i]->year == date('Y') && $month->month == date('n'))  && ($this->config['expand'] == 'current' || $this->config['expand'] == 'current_date');
                $expandMonth = $expandByCurrDateMonth || $expandByPostDateMonth || ( $this->config['expand'] == 'all' );

                //Applies selected month format
                switch ($this->config['month_format'])
                {
                    case 'short':
                        $monthFormat = $wp_locale->get_month_abbrev($wp_locale->get_month($month->month));
                        break;
                    case 'number':
                        $monthFormat = $month->month < 10 ? '0' . $month->month : $month->month;
                        break;
                    default:
                        $monthFormat = $wp_locale->get_month($month->month);
                        break;
                }

                if ( $expandMonth )
                {
                    $expandClass = 'expanded';
                    $sym_key = 'con_sym';

                } else {
                    $expandClass = '';
                    $sym_key = 'ex_sym';
                }

                $style = $expandYear ? 'list-item' : 'none';
                $html.= "\n\t<li class=\"jaw_months {$expandClass}\" style=\"display:{$style};\">" .
                    "<a class=\"jaw_months\" href=\"{$month_url}\" title=\"{$monthFormat}\">";

                if ($this->config['showpost'])
                {
                    $sym_key = $expandMonth ? 'con_sym' : 'ex_sym';
                    $html.= '<span class="jaw_symbol">' . htmlspecialchars($this->config[$sym_key]) . '</span> ';
                }

                if ( $this->config['only_sym_link'] ) {
                    $html .= "</a><a href=\"{$month_url}\" title=\"{$monthFormat}\">";
                }

                $html.= $monthFormat;

                if ( $this->config['showcount'] ) {
                    $html.= " ({$month->posts})";
                }

                $html.= '</a>';

                if ($this->config['showpost'])
                {
                    $html.= '<ul>';
                    $posts = $this->getPosts($years[$i]->year, $month->month);

                    foreach ($posts as $post)
                    {
                        $activeClass = get_the_ID() == $post->ID ? 'active' : '';
                        $style = $expandMonth ? 'list-item' : 'none';
                        $html.= "\n\t\t<li class=\"jaw_posts {$activeClass}\" style=\"display:{$style}\">";
                        $html.= sprintf( "<a href=\"%s\" title=\"%s\">%s</a>",
                            get_permalink($post->ID),
                            htmlspecialchars($post->post_title),
                            $post->post_title
                        );
                        $html.='</li>';
                    }

                    $html.= '</ul>';
                }
                $html.= '</li>';
            }
            $html.= '</ul></li>';
        }

        $html.= '</ul>';
        $html.= $this->printHiddenInput('fx_in');
        $html.= $this->printHiddenInput('ex_sym');
        $html.= $this->printHiddenInput('con_sym');
        $html.= $this->printHiddenInput('only_sym_link');

        return $html;
    }

    protected function printHiddenInput($fieldName)
    {
        return sprintf(
            '<input type="hidden" id="%s" name="%s" class="%s" value="%s" />',
            $this->get_field_id($fieldName),
            $this->get_field_name($fieldName),
            $fieldName,
            $this->config[$fieldName]
        );
    }

    /**
     * Function to clean input from user
     * @return int 1 or 0 if true or false
     */
    protected function fixAttr($attr)
    {
        $val = 0;

        switch ($attr) {
            case 'yes':
            case 'true':
            case '1':
                $val = 1;
                break;
        }
        return $val;
    }

    /**
     * Function wich filters any [jQuery Archive List] text inside post to display archive list
     */
    public function filter($attr)
    {
        $this->enqueueScript();
        $this->config = shortcode_atts($this->defaults, $attr);

        return $this->buildHtml();
    }
}

function jal_register_widget() {
    register_widget('JQArchiveList');
}

add_action('widgets_init', 'jal_register_widget');
