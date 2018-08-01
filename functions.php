<?php

if ( ! class_exists( 'Timber' ) ) {
	add_action( 'admin_notices', function() {
		echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php') ) . '</a></p></div>';
	});

	add_filter('template_include', function($template) {
		return get_stylesheet_directory() . '/static/no-timber.html';
	});

	return;
}

Timber::$dirname = array('templates', 'views');

class StarterSite extends TimberSite {

	function __construct() {
		add_theme_support( 'post-formats' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );
		add_filter( 'timber_context', array( $this, 'add_to_context' ) );
		add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		parent::__construct();
	}

	function register_post_types() {
		//this is where you can register custom post types
	}

	function register_taxonomies() {
		//this is where you can register custom taxonomies
	}

	function add_to_context( $context ) {
		$context['menu'] = new TimberMenu();
		$context['site'] = $this;
		return $context;
	}

	function add_to_twig( $twig ) {
		/* this is where you can add your own functions to twig */
		$twig->addExtension( new Twig_Extension_StringLoader() );
		return $twig;
	}

}

new StarterSite();

function _THEME_NAME_assets() {
	wp_enqueue_script('THEME-NAME-js', get_stylesheet_directory_uri() . '/compiled/js/main.js');
}
add_action( 'wp_enqueue_scripts', '_THEME_NAME_assets' );

function _THEME_NAME_file_types($mimes = array()) {
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_action('upload_mimes', '_THEME_NAME_file_types');

function _THEME_NAME_copyright_years($starting_year) {
	if(!is_numeric($starting_year)) {
		return false;
	}

	$output = '';
	$output .= $starting_year;
	$this_year = (int)date('Y');

	if($this_year > $starting_year) {
		$output .= ' – ' . $this_year;
	}

	return $output;
}

function _THEME_NAME_prepare_acf_json($groupID, $actuallyPrepare = true) {
	if($actuallyPrepare) {
		return json_decode(file_get_contents(get_stylesheet_directory() . '/acf-json/' . $groupID . '.json'), true);
	}

	return $groupID;
}

function _THEME_NAME_get_field_group_data($target = false, $excludeTarget = false, $prepareJSON = true) {
	$ids = array(
	//	'shared fields' => 'group_5b1aa8e4ec4ca',
	//	'generic layouts' => 'group_5b05a0dca0937',
	//	'page content' => 'group_5b2951e460316',
	);

	if($target) {
		$target = strtolower($target);

		if(empty($ids[$target])) {
			throw new Exception('Tried to target an undefined ACF Field Group.');
		}

		if(!$excludeTarget) {
			return _THEME_NAME_prepare_acf_json($ids[$target], $prepareJSON );
		}

		unset($ids[$target]);
	}

	return array_map(
		function($el) use ($prepareJSON) {
			return _THEME_NAME_prepare_acf_json($el, $prepareJSON);
		},
		array_values($ids)
	);
}

function _THEME_NAME_wrap_if($text, $condition, $prefix, $suffix) {
	if($condition) {
		return $prefix . $text . $suffix;
	}

	return $text;
}

function _THEME_NAME_get_latest_blog_posts($num = 1) {
	$posts = Timber::get_posts(
		array(
			'post_type' => 'post',
			'posts_per_page' => $num,
			'order' => 'DESC',
			'orderby' => 'date'
		)
	);

	if($num === 1 && count($posts) === 1) {
		return $posts[0];
	}

	return $posts;
}

function _THEME_NAME_render_menu_recursively($menu_items) {
	$o = '<ul>';

	foreach($menu_items as $item) {
		$host = parse_url($item->link)['host'];

		$test_regexes = array(
			'/\.sft$/', // FIXME! Add your local development TLD below this, or change this one if Lanny Heidbreder isn't involved!
		//	'/example\.com$/', // FIXME! Make this the live site domain!
			'/hardypress\.com$/',
			'/^localhost/',
		);

		$internal = array_reduce(
			$test_regexes,
			function($cumulative, $current) use ($host) {
				return $cumulative || preg_match($current, $host);
			},
			false
		);

		$target = '';

		if(!$internal) {
			$item->title .= ' →';
			$target = ' target="_blank"';
		}

		$o .= sprintf(
			'<li class="%s"><a href="%s"%s><span>%s</span></a>',
			implode(' ', $item->classes),
			$item->link,
			$target,
			$item->title
		);
		if(!empty($item->children)) {
			$o .= _THEME_NAME_render_menu_recursively($item->children);
		}
		$o .= '</li>';
	}

	return $o . '</ul>';
}

/**
 * Generate classes for your ACF layout sections
 *
 * @param [type] $layout
 * @param bool $get_array
 *
 * @return [type]
 */
function _THEME_NAME_layout_classes($layout, $get_array = FALSE) {
	$classes = array(str_replace('_' , '-', $layout['acf_fc_layout']));

	switch($layout['acf_fc_layout']) {
		// Layout-specific class logic goes here
		default: {
			break;
		}
	}

	if($get_array) {
		return $classes;
	}

	return implode($classes, ' ');
}

/**
 * Directly load a section whose entire contents are in a Twig file, with
 * no ACF fields involved.
 *
 * @param [type] $section
 *
 * @return [type]
 */
function _THEME_NAME_load_custom_section($section) {
	$context = array(
		'layout' => array(
			'acf_fc_layout' => $section,
		),
		'set' => 'custom'
	);
	Timber::render( 'partials/fc-handler.twig', $context );
}

add_filter( 'timber/twig', function( \Twig_Environment $twig ) {
    $twig->addFilter( new Twig_SimpleFilter('wrap_if', '_THEMEANME_wrap_if') );
    $twig->addFilter( new Twig_SimpleFilter('unique', 'array_unique') );
    $twig->addFilter( new Twig_SimpleFilter('render_menu', '_THEME_NAME_render_menu_recursively') );
    $twig->addFilter( new Twig_SimpleFilter('classes', '_THEME_NAME_layout_classes') );
    $twig->addFunction( new Twig_SimpleFunction('get_latest_blog_posts', '_THEME_NAME_get_latest_blog_posts') );
    $twig->addFunction( new Twig_SimpleFunction('section', '_THEME_NAME_load_custom_section') );
    return $twig;
} );

function THEME_NAME_duplicate_post_as_draft() {
	global $wpdb;
	if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'THEME_NAME_duplicate_post_as_draft' == $_REQUEST['action'] ) ) ) {
		wp_die('No post to duplicate has been supplied!');
	}

	/*
	 * Nonce verification
	 */
	if ( !isset( $_GET['duplicate_nonce'] ) || !wp_verify_nonce( $_GET['duplicate_nonce'], basename( __FILE__ ) ) )
		return;

	/*
	 * get the original post id
	 */
	$post_id = (isset($_GET['post']) ? absint( $_GET['post'] ) : absint( $_POST['post'] ) );
	/*
	 * and all the original post data then
	 */
	$post = get_post( $post_id );

	/*
	 * if you don't want current user to be the new post author,
	 * then change next couple of lines to this: $new_post_author = $post->post_author;
	 */
	$current_user = wp_get_current_user();
	$new_post_author = $current_user->ID;

	/*
	 * if post data exists, create the post duplicate
	 */
	if (isset( $post ) && $post != null) {

		/*
		 * new post data array
		 */
		$args = array(
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'post_author'    => $new_post_author,
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_name'      => $post->post_name,
			'post_parent'    => $post->post_parent,
			'post_password'  => $post->post_password,
			'post_status'    => 'draft',
			'post_title'     => $post->post_title,
			'post_type'      => $post->post_type,
			'to_ping'        => $post->to_ping,
			'menu_order'     => $post->menu_order
		);

		/*
		 * insert the post by wp_insert_post() function
		 */
		$new_post_id = wp_insert_post( $args );

		/*
		 * get all current post terms ad set them to the new post draft
		 */
		$taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
		foreach ($taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
			wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
		}

		/*
		 * duplicate all post meta just in two SQL queries
		 */
		$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
		if (count($post_meta_infos)!=0) {
			$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
			foreach ($post_meta_infos as $meta_info) {
				$meta_key = $meta_info->meta_key;
				if( $meta_key == '_wp_old_slug' ) continue;
				$meta_value = addslashes($meta_info->meta_value);
				$sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
			}
			$sql_query.= implode(" UNION ALL ", $sql_query_sel);
			$wpdb->query($sql_query);
		}


		/*
		 * finally, redirect to the edit post screen for the new draft
		 */
		wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
		exit;
	} else {
		wp_die('Post creation failed, could not find original post: ' . $post_id);
	}
}
add_action( 'admin_action_THEME_NAME_duplicate_post_as_draft', 'THEME_NAME_duplicate_post_as_draft' );

/*
 * Add the duplicate link to action list for post_row_actions
 */
function THEME_NAME_duplicate_post_link( $actions, $post ) {
	if (current_user_can('edit_posts')) {
		$actions['duplicate'] = '<a href="' . wp_nonce_url('admin.php?action=THEME_NAME_duplicate_post_as_draft&post=' . $post->ID, basename(__FILE__), 'duplicate_nonce' ) . '" title="Duplicate this item" rel="permalink">Duplicate</a>';
	}
	return $actions;
}

add_filter( 'page_row_actions', 'THEME_NAME_duplicate_post_link', 10, 2 );
add_filter( 'post_row_actions', 'THEME_NAME_duplicate_post_link', 10, 2 );

add_post_type_support( 'page', 'excerpt' );