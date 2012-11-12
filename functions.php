<?php

/**
 * 	Custom Post Type - BucketList Item
 */
add_action( 'init', 'create_post_type' );

function create_post_type() {
	register_post_type( 'bucketlistitem', array(
		'labels' => array(
			'name' => __( 'Bucketlist Items' ),
			'singular_name' => __( 'Bucketlist Item' )
		),
		'public' => true,
		'has_archive' => true
	) );
}

/**
 * 	Ajax handler for Backbone.js
 */
add_action( 'wp_ajax_backbone', 'bucketlist_ajax_handler' );
add_action( 'wp_ajax_nopriv_backbone', 'bucketlist_ajax_handler' );

function bucketlist_ajax_handler() {

	$supported_methods = array(
		//'init',
		'read',
		'create',
		'update',
		'delete'
	);

	// Die unless we have a proper backbone method
	if ( in_array( trim( $_REQUEST['backbone_method'] ), $supported_methods ) )
		$method = trim( $_REQUEST['backbone_method'] );
	else
		die();

	$model = $_REQUEST['backbone_model'];

	switch ( $method ) {

		case 'create':

			if ( empty( $_REQUEST['content'] ) )
				die();

			$content = json_decode( stripcslashes( $_REQUEST['content'] ), true );

			// pick attributes from $content and prepare $args to create a model
			$args = array(
				'post_type' => $model,
				'post_title' => $content['title'],
				'post_status' => 'publish',
				'post_author' => 1
			);

			$id = wp_insert_post( $args );

			if ( $id ) {
				update_post_meta( $id, 'status', $content['status'] );
				echo json_encode( get_bucketlist_item( $id ) );
			}
			die();

		case 'read':

			if ( empty( $_REQUEST['content'] ) ) {
				// fetch all models
				echo json_encode( get_bucketlist( array( 'post_type' => $model ) ) );
			} else {
				// $content is the model ID, fetch that model
				$id = absint( $_REQUEST['content'] );
				if ( $id )
					echo json_encode( get_bucketlist_item( $id ) );
			}
			die();

		case 'update':

			if ( empty( $_REQUEST['content'] ) )
				die();

			$content = json_decode( stripcslashes( $_REQUEST['content'] ), true );

			// pick attributes from $content and prepare $args to create a model
			$args = array(
				'post_type' => $model,
				'post_title' => $content['title'],
				'post_status' => 'publish',
				'post_author' => 1,
				'ID' => $content['id'] // will cause the post to update instead
			);

			$id = wp_insert_post( $args );

			if ( $id ) {
				update_post_meta( $id, 'status', $content['status'] );
				echo json_encode( get_bucketlist_item( $id ) );
			}
			die();

		case 'delete':

			if ( empty( $_REQUEST['content'] ) )
				die();

			$content = json_decode( stripslashes( $_REQUEST['content'] ), true );

			wp_delete_post( $content['id'] );

			echo json_encode( array( 'deleted' => true ) );
			die();
	}
}

/**
 * 	Helper function
 */
function get_bucketlist_item( $id ) {
	$id = absint( $id );
	if ( $id ) {
		$post = get_post( $id );
		return array(
			'id' => $post->ID,
			'title' => $post->post_title,
			'status' => get_post_meta( $id, 'status', true )
		);
	} else {
		return false;
	}
}

function get_bucketlist( $args ) {

	$args['numberposts'] = -1;
	$args['orderby'] = 'ID';

	$posts = get_posts( $args );
	$collect = array();
	foreach ( $posts as $post ) {
		$collect[] = array(
			'id' => $post->ID,
			'title' => $post->post_title,
			'status' => get_post_meta( $post->ID, 'status', true )
		);
	}
	$collect = array_reverse( $collect );
	return $collect;
}