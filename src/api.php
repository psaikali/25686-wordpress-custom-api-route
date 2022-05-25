<?php

namespace Mosaika\Custom_API_Route\API;

defined( 'ABSPATH' ) || exit;

/**
 * On enregistre nos routes d'API personnalisées.
 *
 * @return void
 */
function register_routes() {
	// Enregistrement de <site.com>wp-json/custom-api-route/create.
	register_rest_route(
		'custom-api-route',
		'/create/',
		[
			'methods'             => 'POST',
			'callback'            => __NAMESPACE__ . '\\process_creation',
			'permission_callback' => '__return_true',
			'args' => [
				'title' => [
					'sanitize_callback' => function( $value ) {
						return sanitize_text_field( $value );
					},
				],
				'content' => [
					'sanitize_callback' => function( $value ) {
						return wp_kses_post( $value );
					},
				],
			],
		]
	);

	// Enregistrement de <site.com>wp-json/custom-api-route/delete/<id>.
	register_rest_route(
		'custom-api-route',
		'/delete/(?P<post_id>\d+)',
		[
			'methods'             => 'DELETE',
			'callback'            => __NAMESPACE__ . '\\process_deletion',
			'permission_callback' => function( \WP_REST_Request $request ) {
				if ( get_post_meta( (int) $request->get_param( 'post_id' ), 'token', true ) !== $request->get_header( 'X-WP-TOKEN' ) ) {
					return new \WP_Error( 'post_deletion_denied', 'You cannot delete this post.', [ 'status' => 403 ] );
				}

				return true;
			},
			'args' => [
				'post_id' => [
					'validate_callback' => function ( $value, \WP_REST_Request $request, $key ) {
						if ( ! is_numeric( $value ) ) {
							return new \WP_Error( 'post_id_invalid_format', 'Post ID should only contain digits.', [ 'status' => 400 ] );
						}

						if ( get_post_type( (int) $value ) !== 'post' ) {
							return new \WP_Error( 'post_id_invalid_value', 'Post not found.', [ 'status' => 404 ] );
						}

						return true;
					},
				]
			],
		]
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );

//===================================================
//                                                   
//   ####  #####    #####    ###    ######  #####  
//  ##     ##  ##   ##      ## ##     ##    ##     
//  ##     #####    #####  ##   ##    ##    #####  
//  ##     ##  ##   ##     #######    ##    ##     
//   ####  ##   ##  #####  ##   ##    ##    #####  
//                                                   
//===================================================

/**
 * Valide et crée un post.
 *
 * @param \WP_REST_Request $request
 * @return void
 */
function process_creation( \WP_REST_Request $request ) {
	// Validation du titre.
	if (
		empty( $request->get_param( 'title' ) )
		|| mb_strlen( $request->get_param( 'title' ) ) < 10
		|| mb_strlen( $request->get_param( 'title' ) ) > 50
	) {
		return new \WP_Error( 'invalid_title', 'Please provide a title with a length between 10 and 50 chars.', [ 'status' => 400 ] );
	}

	// Vérification si un doublon existe ou non.
	if ( ! empty( get_page_by_title( $request->get_param( 'title' ), OBJECT, 'post' ) ) ) {
		return new \WP_Error( 'duplicate_post', 'A post with this title has already been created.', [ 'status' => 400 ] );
	}

	// Validation du contenu.
	if (
		empty( $request->get_param( 'content' ) )
		|| mb_strlen( $request->get_param( 'content' ) ) < 20
	) {
		return new \WP_Error( 'invalid_content', 'Please provide a content with at least 20 chars.', [ 'status' => 400 ] );
	}

	// Tout est OK ? Création du post.
	$post_id = wp_insert_post( [
		'post_type'    => 'post', // Idéalement, créer un CPT spécifique.
		'post_title'   => sanitize_text_field( $request->get_param( 'title' ) ),
		'post_content' => wp_kses_post( $request->get_param( 'content' ) ),
		'post_status'  => 'private',
	] );

	if ( $post_id === 0 ) {
		return new \WP_Error( 'post_creation_failed', 'An error occured when trying to create your post.', [ 'status' => 400 ] );
	}

	// Enregistrement d'un token de sécurité en métadonnée.
	$token = wp_generate_password( rand( 12, 24 ), false, false );
	update_post_meta( $post_id, 'token', $token );

	// Envoi de la réponse avec l'ID du post créé et son token de sécurité utile pour sa suppression.
	return new \WP_REST_Response(
		[
			'success' => true,
			'post' => [
				'id'    => $post_id,
				'token' => $token,
			]
		],
		201
	);
}

//=================================================
//                                                 
//  ####    #####  ##      #####  ######  #####  
//  ##  ##  ##     ##      ##       ##    ##     
//  ##  ##  #####  ##      #####    ##    #####  
//  ##  ##  ##     ##      ##       ##    ##     
//  ####    #####  ######  #####    ##    #####  
//                                                 
//=================================================

/**
 * Supprime un post.
 *
 * @param \WP_REST_Request $request
 * @return void
 */
function process_deletion( \WP_REST_Request $request ) {
	// Suppression du post.
	$deleted_post = wp_delete_post( $request->get_param( 'post_id' ), true );

	return [
		'success' => ( $deleted_post instanceof \WP_Post ),
		'post'    => $deleted_post,
	];
}
