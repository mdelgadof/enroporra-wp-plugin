<?php

function competition_post_type() {

    $labels = array(
        'name'                  => __('Torneos','enroporra'),
        'singular_name'         => __('Torneo','enroporra'),
        'menu_name'             => __('Torneos','enroporra'),
        'name_admin_bar'        => __('Torneos','enroporra'),
        'archives'              => __('Archivo de torneos','enroporra'),
        'attributes'            => __('Campos de torneo','enroporra'),
        'parent_item_colon'     => '',
        'all_items'             => __('Todos los torneos','enroporra'),
        'add_new_item'          => __('Añadir torneo','enroporra'),
        'add_new'               => __('Nuevo torneo','enroporra'),
        'new_item'              => __('Nuevo torneo','enroporra'),
        'edit_item'             => __('Editar torneo','enroporra'),
        'update_item'           => __('Actualizar torneo','enroporra'),
        'view_item'             => __('Ver torneo','enroporra'),
        'view_items'            => __('Ver torneos','enroporra'),
        'search_items'          => __('Buscar torneo','enroporra'),
        'not_found'             => __('No se han encontrado torneos','enroporra'),
        'not_found_in_trash'    => __('No se han encontrado torneos en la papelera','enroporra'),
        'featured_image'        => __('Imagen destacada','enroporra'),
        'set_featured_image'    => __('Añadir imagen destacada','enroporra'),
        'remove_featured_image' => __('Eliminar imagen destacada','enroporra'),
        'use_featured_image'    => __('Usar como imagen destacada','enroporra'),
        'insert_into_item'      => __('Insertar en el torneo','enroporra'),
        'uploaded_to_this_item' => __('Añadido a este torneo','enroporra'),
        'items_list'            => __('Listado de torneos','enroporra'),
        'items_list_navigation' => __( 'Items list navigation'),
        'filter_items_list'     => __( 'Filter items list'),
    );
    $args = array(
        'label'                 => __('Torneo','enroporra'),
        'description'           => __('Datos de un torneo.','enroporra'),
        'labels'                => $labels,
        'supports'              => array( 'title', 'thumbnail', 'custom-fields', ),
        'hierarchical'          => false,
        'show_in_menu'          => true,
        'menu_position'         => 1,
        'menu_icon'             => 'dashicons-awards',
        'show_in_admin_bar'     => true,
        'can_export'            => true,
        'query_var'             => true,
        'capability_type'       => 'page',
        // PRIVATE CUSTOM POST TYPES
        'public' => false,  // it's not public, it shouldn't have it's own permalink, and so on
        'publicly_queryable' => true,  // you should be able to query it
        'show_ui' => true,  // you should be able to edit it in wp-admin
        'exclude_from_search' => true,  // you should exclude it from search results
        'show_in_nav_menus' => false,  // you shouldn't be able to add it to menus
        'has_archive' => false,  // it shouldn't have archive page
        'rewrite' => false,  // it shouldn't have rewrite rules
    );
    register_post_type( 'competition', $args );
}
add_action( 'init', 'competition_post_type', 0 );

function fixture_post_type() {

    $labels = array(
        'name'                  => __('Partidos','enroporra'),
        'singular_name'         => __('Partido','enroporra'),
        'menu_name'             => __('Partidos','enroporra'),
        'name_admin_bar'        => __('Partidos','enroporra'),
        'archives'              => __('Archivo de partidos','enroporra'),
        'attributes'            => __('Campos de partido','enroporra'),
        'parent_item_colon'     => '',
        'all_items'             => __('Todos los partidos','enroporra'),
        'add_new_item'          => __('Añadir partido','enroporra'),
        'add_new'               => __('Nuevo partido','enroporra'),
        'new_item'              => __('Nuevo partido','enroporra'),
        'edit_item'             => __('Editar partido','enroporra'),
        'update_item'           => __('Actualizar partido','enroporra'),
        'view_item'             => __('Ver partido','enroporra'),
        'view_items'            => __('Ver partidos','enroporra'),
        'search_items'          => __('Buscar partido','enroporra'),
        'not_found'             => __('No se han encontrado partidos','enroporra'),
        'not_found_in_trash'    => __('No se han encontrado partidos en la papelera','enroporra'),
        'featured_image'        => __('Imagen destacada','enroporra'),
        'set_featured_image'    => __('Añadir imagen destacada','enroporra'),
        'remove_featured_image' => __('Eliminar imagen destacada','enroporra'),
        'use_featured_image'    => __('Usar como imagen destacada','enroporra'),
        'insert_into_item'      => __('Insertar en el torneo','enroporra'),
        'uploaded_to_this_item' => __('Añadido a este torneo','enroporra'),
        'items_list'            => __('Listado de torneos','enroporra'),
        'items_list_navigation' => __( 'Items list navigation'),
        'filter_items_list'     => __( 'Filter items list'),
    );
    $args = array(
        'label'                 => __('Partido','enroporra'),
        'description'           => __('Datos de un partido.','enroporra'),
        'labels'                => $labels,
        'supports'              => array( 'thumbnail', 'custom-fields', ),
        'hierarchical'          => false,
        'show_in_menu'          => true,
        'menu_position'         => 2,
        'menu_icon'             => 'dashicons-welcome-view-site',
        'show_in_admin_bar'     => true,
        'can_export'            => true,
        'query_var'             => true,
        'capability_type'       => 'page',
        // PRIVATE CUSTOM POST TYPES
        'public' => false,  // it's not public, it shouldn't have it's own permalink, and so on
        'publicly_queryable' => true,  // you should be able to query it
        'show_ui' => true,  // you should be able to edit it in wp-admin
        'exclude_from_search' => true,  // you should exclude it from search results
        'show_in_nav_menus' => false,  // you shouldn't be able to add it to menus
        'has_archive' => false,  // it shouldn't have archive page
        'rewrite' => false,  // it shouldn't have rewrite rules
    );
    register_post_type( 'fixture', $args );
}
add_action( 'init', 'fixture_post_type', 0 );

function team_post_type() {

    $labels = array(
        'name'                  => __('Equipos','enroporra'),
        'singular_name'         => __('Equipo','enroporra'),
        'menu_name'             => __('Equipos','enroporra'),
        'name_admin_bar'        => __('Equipos','enroporra'),
        'archives'              => __('Archivo de equipos','enroporra'),
        'attributes'            => __('Campos de equipo','enroporra'),
        'parent_item_colon'     => '',
        'all_items'             => __('Todos los equipos','enroporra'),
        'add_new_item'          => __('Añadir equipo','enroporra'),
        'add_new'               => __('Nuevo equipo','enroporra'),
        'new_item'              => __('Nuevo equipo','enroporra'),
        'edit_item'             => __('Editar equipo','enroporra'),
        'update_item'           => __('Actualizar equipo','enroporra'),
        'view_item'             => __('Ver equipo','enroporra'),
        'view_items'            => __('Ver equipos','enroporra'),
        'search_items'          => __('Buscar equipo','enroporra'),
        'not_found'             => __('No se han encontrado equipos','enroporra'),
        'not_found_in_trash'    => __('No se han encontrado equipos en la papelera','enroporra'),
        'featured_image'        => __('Imagen destacada','enroporra'),
        'set_featured_image'    => __('Añadir imagen destacada','enroporra'),
        'remove_featured_image' => __('Eliminar imagen destacada','enroporra'),
        'use_featured_image'    => __('Usar como imagen destacada','enroporra'),
        'insert_into_item'      => __('Insertar en el equipo','enroporra'),
        'uploaded_to_this_item' => __('Añadido a este equipo','enroporra'),
        'items_list'            => __('Listado de equipos','enroporra'),
        'items_list_navigation' => __( 'Items list navigation'),
        'filter_items_list'     => __( 'Filter items list'),
    );
    $args = array(
        'label'                 => __('Equipo','enroporra'),
        'description'           => __('Datos de un equipo.','enroporra'),
        'labels'                => $labels,
        'supports'              => array( 'title', 'thumbnail', 'custom-fields', ),
        'hierarchical'          => false,
        'show_in_menu'          => true,
        'menu_position'         => 3,
        'menu_icon'             => 'dashicons-shield',
        'show_in_admin_bar'     => true,
        'can_export'            => true,
        'query_var'             => true,
        'capability_type'       => 'page',
        // PRIVATE CUSTOM POST TYPES
        'public' => false,  // it's not public, it shouldn't have it's own permalink, and so on
        'publicly_queryable' => true,  // you should be able to query it
        'show_ui' => true,  // you should be able to edit it in wp-admin
        'exclude_from_search' => true,  // you should exclude it from search results
        'show_in_nav_menus' => false,  // you shouldn't be able to add it to menus
        'has_archive' => false,  // it shouldn't have archive page
        'rewrite' => false,  // it shouldn't have rewrite rules
    );
    register_post_type( 'team', $args );
}
add_action( 'init', 'team_post_type', 0 );

function player_post_type() {

	$labels = array(
		'name'                  => __('Jugadores','enroporra'),
		'singular_name'         => __('Jugador','enroporra'),
		'menu_name'             => __('Jugadores','enroporra'),
		'name_admin_bar'        => __('Jugadores','enroporra'),
		'archives'              => __('Archivo de jugadores','enroporra'),
		'attributes'            => __('Campos de jugador','enroporra'),
		'parent_item_colon'     => '',
		'all_items'             => __('Todos los jugadores','enroporra'),
		'add_new_item'          => __('Añadir jugador','enroporra'),
		'add_new'               => __('Nuevo jugador','enroporra'),
		'new_item'              => __('Nuevo jugador','enroporra'),
		'edit_item'             => __('Editar jugador','enroporra'),
		'update_item'           => __('Actualizar jugador','enroporra'),
		'view_item'             => __('Ver jugador','enroporra'),
		'view_items'            => __('Ver jugadores','enroporra'),
		'search_items'          => __('Buscar jugador','enroporra'),
		'not_found'             => __('No se han encontrado jugadores','enroporra'),
		'not_found_in_trash'    => __('No se han encontrado jugadores en la papelera','enroporra'),
		'featured_image'        => __('Imagen destacada','enroporra'),
		'set_featured_image'    => __('Añadir imagen destacada','enroporra'),
		'remove_featured_image' => __('Eliminar imagen destacada','enroporra'),
		'use_featured_image'    => __('Usar como imagen destacada','enroporra'),
		'insert_into_item'      => __('Insertar en el jugador','enroporra'),
		'uploaded_to_this_item' => __('Añadido a este jugador','enroporra'),
		'items_list'            => __('Listado de jugadores','enroporra'),
		'items_list_navigation' => __( 'Items list navigation'),
		'filter_items_list'     => __( 'Filter items list'),
	);
	$args = array(
		'label'                 => __('Jugador','enroporra'),
		'description'           => __('Datos de un jugador.','enroporra'),
		'labels'                => $labels,
		'supports'              => array( 'title', 'thumbnail', 'custom-fields', ),
		'hierarchical'          => false,
		'show_in_menu'          => true,
		'menu_position'         => 4,
		'menu_icon'             => 'dashicons-groups',
		'show_in_admin_bar'     => true,
		'can_export'            => true,
		'query_var'             => true,
		'capability_type'       => 'post',
		// PRIVATE CUSTOM POST TYPES
		'public' => false,  // it's not public, it shouldn't have it's own permalink, and so on
		'publicly_queryable' => true,  // you should be able to query it
		'show_ui' => true,  // you should be able to edit it in wp-admin
		'exclude_from_search' => true,  // you should exclude it from search results
		'show_in_nav_menus' => false,  // you shouldn't be able to add it to menus
		'has_archive' => false,  // it shouldn't have archive page
		'rewrite' => false,  // it shouldn't have rewrite rules
	);
	register_post_type( 'player', $args );
}
add_action( 'init', 'player_post_type', 0 );

function referee_post_type() {

	$labels = array(
		'name'                  => __('Árbitros','enroporra'),
		'singular_name'         => __('Árbitro','enroporra'),
		'menu_name'             => __('Árbitros','enroporra'),
		'name_admin_bar'        => __('Árbitros','enroporra'),
		'archives'              => __('Archivo de árbitros','enroporra'),
		'attributes'            => __('Campos de árbitro','enroporra'),
		'parent_item_colon'     => '',
		'all_items'             => __('Todos los árbitros','enroporra'),
		'add_new_item'          => __('Añadir árbitro','enroporra'),
		'add_new'               => __('Nuevo árbitro','enroporra'),
		'new_item'              => __('Nuevo árbitro','enroporra'),
		'edit_item'             => __('Editar árbitro','enroporra'),
		'update_item'           => __('Actualizar árbitro','enroporra'),
		'view_item'             => __('Ver árbitro','enroporra'),
		'view_items'            => __('Ver árbitros','enroporra'),
		'search_items'          => __('Buscar árbitro','enroporra'),
		'not_found'             => __('No se han encontrado árbitros','enroporra'),
		'not_found_in_trash'    => __('No se han encontrado árbitros en la papelera','enroporra'),
		'featured_image'        => __('Imagen destacada','enroporra'),
		'set_featured_image'    => __('Añadir imagen destacada','enroporra'),
		'remove_featured_image' => __('Eliminar imagen destacada','enroporra'),
		'use_featured_image'    => __('Usar como imagen destacada','enroporra'),
		'insert_into_item'      => __('Insertar en el árbitro','enroporra'),
		'uploaded_to_this_item' => __('Añadido a este árbitro','enroporra'),
		'items_list'            => __('Listado de árbitros','enroporra'),
		'items_list_navigation' => __( 'Items list navigation'),
		'filter_items_list'     => __( 'Filter items list'),
	);
	$args = array(
		'label'                 => __('Árbitro','enroporra'),
		'description'           => __('Datos de un árbitro.','enroporra'),
		'labels'                => $labels,
		'supports'              => array( 'title', 'thumbnail', 'custom-fields', ),
		'hierarchical'          => false,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'menu_icon'             => 'dashicons-businessman',
		'show_in_admin_bar'     => true,
		'can_export'            => true,
		'query_var'             => true,
		'capability_type'       => 'post',
		// PRIVATE CUSTOM POST TYPES
		'public' => false,  // it's not public, it shouldn't have it's own permalink, and so on
		'publicly_queryable' => true,  // you should be able to query it
		'show_ui' => true,  // you should be able to edit it in wp-admin
		'exclude_from_search' => true,  // you should exclude it from search results
		'show_in_nav_menus' => false,  // you shouldn't be able to add it to menus
		'has_archive' => false,  // it shouldn't have archive page
		'rewrite' => false,  // it shouldn't have rewrite rules
	);
	register_post_type( 'referee', $args );
}
add_action( 'init', 'referee_post_type', 0 );

function bet_post_type() {

	$labels = array(
		'name'                  => __('Apuestas','enroporra'),
		'singular_name'         => __('Apuesta','enroporra'),
		'menu_name'             => __('Apuestas','enroporra'),
		'name_admin_bar'        => __('Apuestas','enroporra'),
		'archives'              => __('Archivo de apuestas','enroporra'),
		'attributes'            => __('Campos de apuesta','enroporra'),
		'parent_item_colon'     => '',
		'all_items'             => __('Todas las apuestas','enroporra'),
		'add_new_item'          => __('Añadir apuesta','enroporra'),
		'add_new'               => __('Nueva apuesta','enroporra'),
		'new_item'              => __('Nueva apuesta','enroporra'),
		'edit_item'             => __('Editar apuesta','enroporra'),
		'update_item'           => __('Actualizar apuesta','enroporra'),
		'view_item'             => __('Ver apuesta','enroporra'),
		'view_items'            => __('Ver apuestas','enroporra'),
		'search_items'          => __('Buscar apuesta','enroporra'),
		'not_found'             => __('No se han encontrado apuestas','enroporra'),
		'not_found_in_trash'    => __('No se han encontrado apuestas en la papelera','enroporra'),
		'featured_image'        => __('Imagen destacada','enroporra'),
		'set_featured_image'    => __('Añadir imagen destacada','enroporra'),
		'remove_featured_image' => __('Eliminar imagen destacada','enroporra'),
		'use_featured_image'    => __('Usar como imagen destacada','enroporra'),
		'insert_into_item'      => __('Insertar en el apuesta','enroporra'),
		'uploaded_to_this_item' => __('Añadido a este apuesta','enroporra'),
		'items_list'            => __('Listado de apuestas','enroporra'),
		'items_list_navigation' => __( 'Items list navigation'),
		'filter_items_list'     => __( 'Filter items list'),
	);
	$args = array(
		'label'                 => __('Apuesta','enroporra'),
		'description'           => __('Datos de una apuesta.','enroporra'),
		'labels'                => $labels,
		'supports'              => array( 'title', 'thumbnail', 'custom-fields', ),
		'hierarchical'          => false,
		'show_in_menu'          => true,
		'menu_position'         => 6,
		'menu_icon'             => 'dashicons-forms',
		'show_in_admin_bar'     => true,
		'can_export'            => true,
		'query_var'             => true,
		'capability_type'       => 'post',
		// PRIVATE CUSTOM POST TYPES
		'public' => true,  // it's not public, it shouldn't have it's own permalink, and so on
		'publicly_queryable' => true,  // you should be able to query it
		'show_ui' => true,  // you should be able to edit it in wp-admin
		'exclude_from_search' => true,  // you should exclude it from search results
		'show_in_nav_menus' => false,  // you shouldn't be able to add it to menus
		'has_archive' => false,  // it shouldn't have archive page
		'rewrite' => array('slug' => 'apuesta'),
	);
	register_post_type( 'bet', $args );
}
add_action( 'init', 'bet_post_type', 0 );

add_theme_support('post-thumbnails');