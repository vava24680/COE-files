<?php
/**
 * The main plugin file for Posts Table with Search & Sort.
 *
 * @package   Posts_Data_Table
 * @author    Andrew Keith <andy@barn2.co.uk>
 * @license   GPL-2.0+
 * @link      http://barn2.co.uk
 * @copyright 2016 Barn2 Media
 *
 * @wordpress-plugin
 * Plugin Name:       Posts Table with Search & Sort
 * Description:       This plugin provides a shortcode to show a list of your site's posts in a searchable and sortable table.
 * Version:           1.0.6
 * Author:            Barn2 Media
 * Author URI:        http://barn2.co.uk
 * Text Domain:       posts-data-table
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Prevent direct file access
if ( ! defined ( 'ABSPATH' ) ) {
	exit;
}

// Current version of this plugin
define( 'POSTS_DATA_TABLE_VERSION', '1.0.5' );

class Posts_Data_Table_Plugin {

    private $shortcode = 'posts_data_table';

    private $shortcode_defaults = array(
        'columns' => 'title,content,date,author,category',
        'rows_per_page' => 20,
        'sort_by' => 'date',
        'sort_order' => '',
        'category' => '',
        'search_on_click' => true,
        'wrap' => true,
        'content_length' => 15,
        'scroll_offset' => 15
    );

    private $table_count = 1;

    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_settings_link' ) );
    }

    public function init() {
        // Don't init plugin if Pro version exists
        if ( !class_exists( 'Posts_Table_Pro_Plugin' ) ) {
            // Load the text domain - should go on 'plugins_loaded' hook
            $this->load_textdomain();

            // Register shortcode
            add_shortcode( $this->shortcode, array( $this, 'shortcode' ) );

            // Register styles and scripts
            add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'posts-data-table', false, dirname( plugin_basename( __FILE__  ) ) . '/languages' );
    }

	public function register_styles() {
        wp_enqueue_style( 'jquery-data-tables', plugins_url( 'assets/css/datatables.min.css', __FILE__ ), array(), '1.10.12' );
		wp_enqueue_style( 'posts-data-table', plugins_url( 'assets/css/posts-data-table.min.css', __FILE__ ), array( 'jquery-data-tables' ), POSTS_DATA_TABLE_VERSION );
        //wp_enqueue_style( 'posts-data-table', plugins_url( 'assets/css/posts-data-table.css', __FILE__ ), array( 'jquery-data-tables' ), POSTS_DATA_TABLE_VERSION );
	}

    public function register_scripts() {
        wp_enqueue_script( 'jquery-data-tables', plugins_url( 'assets/js/datatables.min.js', __FILE__ ), array( 'jquery' ), '1.10.12', true );
        wp_enqueue_script( 'posts-data-table', plugins_url( 'assets/js/posts-data-table.min.js', __FILE__ ), array( 'jquery-data-tables' ), POSTS_DATA_TABLE_VERSION, true );
        //wp_enqueue_script( 'posts-data-table', plugins_url( 'assets/js/posts-data-table.js', __FILE__ ), array( 'jquery-data-tables' ), POSTS_DATA_TABLE_VERSION, true );

        $locale = get_locale();
        $supported_locales = $this->get_supported_locales();

        // Add language file to script if locale is supported (English file is not added as this is the default language)
        if ( array_key_exists( $locale, $supported_locales ) ) {
            wp_localize_script( 'posts-data-table', 'posts_data_table', array(
                'langurl' => $supported_locales[$locale]
            ) );
        }
    }

    public function add_plugin_settings_link( $links ) {
        $links[] = sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://barn2.co.uk/wordpress-products/posts-table-pro/'), __( 'Pro Version', 'posts-data-table' ) );
        return $links;
    }

    /**
     * Handles our posts data table shortcode.
     *
     * @param array $atts The attributes passed in to the shortcode
     * @param string $content The content passed to the shortcode (not used)
     * @return string The shortcode output
     */
    public function shortcode( $atts, $content = '' ) {
        $atts = shortcode_atts( $this->shortcode_defaults, $atts, $this->shortcode );
        return $this->get_posts_data_table( $atts );
    }

    /**
     * Returns an array of locales supported by the plugin.
     * The array returned uses the locale as the array key mapped to the URL of the corresponding translation file.
     *
     * @return array The supported locales
     */
    private function get_supported_locales() {
        $lang_file_base_url = plugins_url( 'languages/data-tables/', __FILE__ );

        return array(
            'es_ES' => $lang_file_base_url . 'Spanish.json',
            'fr_FR' => $lang_file_base_url . 'French.json',
            'fr_BE' => $lang_file_base_url . 'French.json',
            'fr_CA' => $lang_file_base_url . 'French.json',
            'de_DE' => $lang_file_base_url . 'German.json',
            'de_CH' => $lang_file_base_url . 'German.json',
            'el' => $lang_file_base_url . 'Greek.json',
            'el_EL' => $lang_file_base_url . 'Greek.json',
        );
    }

    /**
     * Retrieves a data table containing a list of posts based on the specified arguments.
     *
     * @param array $args An array of options used to display the posts table
     * @return string The posts table HTML output
     */
    private function get_posts_data_table( $args ) {

        if ( empty( $args['columns'] ) ) {
            $args['columns'] = $this->shortcode_defaults['columns'];
        }

        $args['rows_per_page'] = filter_var( $args['rows_per_page'], FILTER_VALIDATE_INT );
        if ( ($args['rows_per_page'] < 1) || !$args['rows_per_page'] ) {
            $args['rows_per_page'] = false;
        }

        if ( !in_array( $args['sort_by'], array('id', 'title', 'category', 'date', 'author', 'content') ) ) {
            $args['sort_by'] = $this->shortcode_defaults['sort_by'];
        }

        if ( !in_array( $args['sort_order'], array('asc', 'desc') ) ) {
            $args['sort_order'] = $this->shortcode_defaults['sort_order'];
        }

        // Set default sort direction
        if ( !$args['sort_order'] ) {
            if ( $args['sort_by'] === 'date' ) {
                $args['sort_order'] = 'desc';
            } else {
                $args['sort_order'] = 'asc';
            }
        }

        $args['search_on_click'] = filter_var( $args['search_on_click'], FILTER_VALIDATE_BOOLEAN );
        $args['wrap'] = filter_var( $args['wrap'], FILTER_VALIDATE_BOOLEAN );
        $args['content_length'] = filter_var( $args['content_length'], FILTER_VALIDATE_INT );
        $args['scroll_offset'] = filter_var( $args['scroll_offset'], FILTER_VALIDATE_INT );

        $date_format = 'Y/m/d';
        $output = $table_head = $table_body = $body_row_fmt = '';

        // Start building the args needed for our posts query
        $post_args = array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'suppress_filters' => false // Ensure WPML filters run on this query
        );

        if ( $args['category'] ) {
            $category = get_category_by_slug( $args['category'] );

            if ( $category ) {
                $post_args['category_name'] = $category->slug;
            }
        }

        // Get all published posts in the current language
        $all_posts_curr_lang = get_posts( $post_args );

        if ( is_array( $all_posts_curr_lang ) && $all_posts_curr_lang ) {  // if we have posts

            /**
             * Define an array of all possible columns and their default heading, priority, and column width.
             *
             * Priority values are used to determine visiblity at small screen sizes (1 = highest priority, 6 = lowest priority).
             * Column widths are automatically calculated by DataTables, but can be overridden by using filter 'posts_data_table_column_defaults'.
             */
            $column_defaults = array(
                'id' => array(
                    'heading' => __('編號'/*'ID'*/, 'posts-data-table'),
                    'priority' => 3,
                    'width' => ''
                ),
                'title' => array(
                    'heading' => __('標題'/*'Title'*/, 'posts-data-table'),
                    'priority' => 1,
                    'width' => ''
                ),
                'category' => array(
                    'heading' => __('類別'/*'Categories'*/, 'posts-data-table'),
                    'priority' => 6,
                    'width' => ''
                ),
                'date' => array(
                    'heading' => __('日期'/*'Date'*/, 'posts-data-table'),
                    'priority' => 2,
                    'width' => ''
                ),
                'author' => array(
                    'heading' => __('作者'/*'Author'*/, 'posts-data-table'),
                    'priority' => 4,
                    'width' => ''
                ),
                'content' => array(
                    'heading' => __('內容'/*'Content'*/, 'posts-data-table'),
                    'priority' => 5,
                    'width' => ''
                ),
            );

            $all_columns = array_keys( $column_defaults );

            // Allow users to override defaults
            $column_defaults = apply_filters( 'posts_data_table_column_defaults', $column_defaults );
            $column_defaults = apply_filters( 'posts_data_table_column_defaults_' . $this->table_count, $column_defaults );

            // Get the columns to be used in this table
            $columns = array_map( 'trim', explode( ',', strtolower( $args['columns'] ) ) );

            // If none of the user-specfied columns are valid, use the default columns instead
            if ( !array_intersect( $all_columns, $columns ) ) {
                $columns = explode( ',', $this->shortcode_defaults['columns'] );
            }

            // Build table header
            $heading_fmt = '<th data-name="%1$s" data-priority="%2$u" data-width="%3$s">%4$s</th>';
            $cell_fmt = '<td>%s</td>';

            foreach( $columns as $column ) {

                if ( array_key_exists( $column, $column_defaults ) ) { // Double-check column name is valid

                    // Add heading to table
                    $table_head .= sprintf( $heading_fmt, $column, $column_defaults[$column]['priority'], $column_defaults[$column]['width'], $column_defaults[$column]['heading'] );

                    // Add placeholder to table body format string so that content for this column is included in table output
                    $body_row_fmt .= sprintf($cell_fmt, '{' . $column . '}');
                }
            }

            $sort_column = $args['sort_by'];
            $sort_index = array_search( $sort_column, $columns );

            if ( $sort_index === false && array_key_exists( $sort_column, $column_defaults ) ) {
                // Sort column is not in list of displayed columns so we'll add it as a hidden column at end of table
                $table_head .= sprintf( '<th data-name="%1$s" data-visible="false">%2$s</th>', $sort_column, $column_defaults[$sort_column]['heading'] );

                // Make sure data for this column is included in table content
                $body_row_fmt .= sprintf($cell_fmt, $sort_column);

                // Set the sort column index to be this hidden column
                $sort_index = count($columns);
            }

            $table_head = sprintf( '<thead><tr>%s</tr></thead>', $table_head );
            // end table header

            // Build table body
            $body_row_fmt = '<tr>' . $body_row_fmt . '</tr>';

            // Loop through posts and add a row for each
            foreach ( (array) $all_posts_curr_lang as $_post ) {
                setup_postdata( $_post );

                // Format title
                $title = sprintf( '<a href="%1$s">%2$s</a>', get_permalink($_post), get_the_title( $_post ) );

                // Format author
                $author = sprintf(
                    '<a href="%1$s" title="%2$s" rel="author">%3$s</a>',
                    esc_url( get_author_posts_url( $_post->post_author ) ),
                    esc_attr( sprintf( __( 'Posts by %s' ), get_the_author() ) ),
                    get_the_author()
                );

                $post_data_trans = array(
                    '{id}' => $_post->ID,
                    '{title}' => $title,
                    '{category}' => get_the_category_list( ', ', '', $_post->ID ),
                    '{date}' => get_the_date( $date_format, $_post ),
                    '{author}' => $author,
                    '{content}' => $this->get_post_content( $args['content_length'] )
                );

                $table_body .= strtr( $body_row_fmt, $post_data_trans );

            } // foreach post

            wp_reset_postdata();

            $table_body = sprintf( '<tbody>%s</tbody>', $table_body );
            // end table body

            $paging_attr = 'false';
            if ( ( $args['rows_per_page'] > 1 ) && ( $args['rows_per_page'] < count($all_posts_curr_lang) ) ) {
                $paging_attr = 'true';
            }

            $order_attr = ( $sort_index === false ) ? '' : sprintf( '[[%u, "%s"]]', $sort_index, $args['sort_order'] );
            $offset_attr = ( $args['scroll_offset'] === false ) ? 'false' : $args['scroll_offset'];

            $table_class = 'posts-data-table';
            if ( !$args['wrap'] ) {
                $table_class .= ' nowrap';
            }

            $output = sprintf(
                '<table '
                    . 'id="posts-table-%1$u" '
                    . 'class="%2$s" '
                    . 'data-page-length="%3$u" '
                    . 'data-paging="%4$s" '
                    . 'data-order=\'%5$s\' '
                    . 'data-click-filter="%6$s" '
                    . 'data-scroll-offset="%7$s" '
                    . 'cellspacing="0" width="100%%">'
                    . '%8$s%9$s' .
                '</table>',
                $this->table_count,
                esc_attr( $table_class ),
                esc_attr( $args['rows_per_page'] ),
                esc_attr( $paging_attr ),
                esc_attr( $order_attr ),
                ( $args['search_on_click'] ? 'true' : 'false' ),
                esc_attr( $offset_attr ),
                $table_head,
                $table_body
            );

            $this->table_count++;
        } // if posts found

        return $output;
    } // get_comments_list

    /**
     * Retrieve the post content, truncated to the number of words specified by $num_words.
     *
     * Must be called with the Loop or a secondary loop after a call to setup_postdata().
     *
     * @param int $num_words The number of words to trim the content to
     * @return string The (truncated) post content
     */
    private function get_post_content( $num_words = 15 ) {
        $text = get_the_content('');
		$text = strip_shortcodes( $text );
        $text = apply_filters( 'the_content', $text );
        $text = wp_trim_words( $text, $num_words, ' &hellip;' );
        return $text;
    }

} // end class

$posts_data_table = new Posts_Data_Table_Plugin();