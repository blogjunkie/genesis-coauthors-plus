<?php
    /*
    Plugin Name: Genesis Co-Authors Plus
    Plugin URI: http://www.jeangalea.com
    Description: Enables full support for the Co-Authors Plus plugin in Genesis
    Version: 1.3
    Author: Jean Galea
    Author URI: http://www.jeangalea.com
    License: GPLv3
    */

    /*
	Based on the excellent partial integration work of Bill Erickson:
	http://www.billerickson.net/wordpress-post-multiple-authors/
    */

    /*  
    Copyright 2012-2014 Jean Galea (email : info@jeangalea.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.
    
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
    */

/** Add guest author without user profile functionality via the following functions */


/**
 * Adding checkboxes to the Guest Author's add new / edit screen
 * 
 * @author David Wang
 */
add_filter( 'coauthors_guest_author_fields', 'gcap_add_enable_author_box_option', 10, 2 );

function gcap_add_enable_author_box_option($fields, $groups) {

    if ( in_array( 'all', $groups ) || in_array( 'name', $groups ) ) {
        $fields[] = array(
            'key' => 'enable_author_box',
            'label' => 'Display Author Box at the end of author\'s posts?',
            'input' => 'checkbox',
            'type' => 'checkbox',
            'group' => 'name',
        );
		$fields[] = array(
            'key' => 'enable_author_box_archive',
            'label' => 'Display Author Box at the top of author\'s archive page?',
            'input' => 'checkbox',
            'type' => 'checkbox',
            'group' => 'name',
        );
    }

    return $fields;
}


/**
 * Post Authors Post Link Shortcode
 * 
 * @param array $atts
 * @return string $authors
 */
function gcap_post_authors_post_link_shortcode( $atts ) {
 
	$atts = shortcode_atts( array( 
		'between'      => null,
		'between_last' => null,
		'before'       => null,
		'after'        => null
	), $atts );
 
	$authors = function_exists( 'coauthors_posts_links' ) ? coauthors_posts_links( $atts['between'], $atts['between_last'], $atts['before'], $atts['after'], false ) : $atts['before'] . get_author_posts_url() . $atts['after'];
	return $authors;
}
add_shortcode( 'post_authors_post_link', 'gcap_post_authors_post_link_shortcode' );


/**
 * List Authors in Genesis Post Info
 *
 * @param string $info
 * @return string $info
 */
function gcap_post_info( $post_info ) {
	$post_info = apply_filters( 'gcap_post_info', '[post_date] by [post_authors_post_link] [post_comments] [post_edit]' );
	return $post_info;
}
add_filter( 'genesis_post_info', 'gcap_post_info' );


/**
 * Remove Genesis Author Box and load our own
 *
 * @author Jean Galea
 */
function gcap_coauthors_init() {
	remove_action( 'genesis_after_entry', 'genesis_do_author_box_single', 8 );
	add_action( 'genesis_after_entry', 'gcap_author_box', 1 );
}
add_action( 'init', 'gcap_coauthors_init' );


/**
 * Load Author Boxes
 *
 * @author Jean Galea
 */
function gcap_author_box() {
 
	if ( ! is_single() )
		return;
 	
	if ( function_exists( 'get_coauthors' ) ) {
		
		$authors = get_coauthors();
		foreach( $authors as $author ) {
			gcap_do_author_box( 'single', $author );
		}
	} 

	else gcap_do_author_box( 'single', get_the_author_ID() );	
}


/** 
 * Display Author Box on author archive
 *
 * @author David Wang
 */
add_action( 'genesis_before_loop', 'gcap_do_author_box_archive', 15 );
function gcap_do_author_box_archive() {
	
	if ( ! is_author() )
		return;
	
	if ( function_exists( 'get_coauthors' ) ) {

		$authors = get_coauthors();
		
		if( ! $authors[0]->enable_author_box_archive || get_query_var( 'paged' ) >= 2 )
	        return;
        		
		gcap_do_author_box( 'archive', $authors[0] );
		
	}
    
}


/**
 * Display Author Box
 * Modified from Genesis to use data from get_coauthors() function
 *
 * @author Jean Galea
 */
function gcap_do_author_box( $context = '', $author, $echo = true ) {
 
	if( ! $author ) 
		return;
		
	// check only for single post pages, archive check is performed in gcap_do_author_box_archive()
    if( ! $author->enable_author_box && is_single() )
        return;

    if( has_post_thumbnail( $author->ID ) ) {
        $gravatar = get_the_post_thumbnail( $author->ID, array(70,70), array('class' => 'avatar' ) );
    } else {
	$gravatar_size = apply_filters( 'genesis_author_box_gravatar_size', 70, $context );
	$gravatar      = get_avatar( $author->user_email , $gravatar_size );
    }

	$description   = wpautop( $author->description );

	//* The author box markup, contextual
	if ( genesis_html5() ) {

		$title = apply_filters( 'genesis_author_box_title', sprintf( '%s <span itemprop="name">%s</span>', __( 'About', 'genesis' ), $author->display_name ), $context );

		$pattern  = sprintf( '<section %s>', genesis_attr( 'author-box' ) );
		$pattern .= '%s<h1 class="author-box-title">%s</h1>';
		$pattern .= '<div class="author-box-content" itemprop="description">%s</div>';
		$pattern .= '</section>';

	}
	else {

		$title = apply_filters( 'genesis_author_box_title', sprintf( '<strong>%s %s</strong>', __( 'About', 'genesis' ), $author->display_name ), $context );

		$pattern = 'single' === $context ? '<div class="author-box"><div>%s %s<br />%s</div></div>' : '<div class="author-box">%s<h1>%s</h1><div>%s</div></div>';

	}

	$output = apply_filters( 'genesis_author_box', sprintf( $pattern, $gravatar, $title, $description ), $context, $pattern, $gravatar, $title, $description );

	if ( $echo )
		echo $output;
	else
		return $output;
}


/**
 * Output Co-Authors Plus names on [post_author] shortcode
 */
add_filter( 'genesis_post_author_shortcode', 'gcap_coauthors_filter', 10, 1 );
function gcap_coauthors_filter( $output ) {
    if ( function_exists( 'coauthors' ) ) {
        return coauthors( null, null, '<span class="entry-author-name" itemprop="name">', '</span>', false );
    } else {
		return $output;
    }
}

/**
 * Output Co-Authors Plus names on [post_author_posts_link] shortcode
 */
add_filter( 'genesis_post_author_posts_link_shortcode', 'gcap_coauthors_posts_links_filter', 10, 1 );
function gcap_coauthors_posts_links_filter( $output ) {
    if ( function_exists( 'coauthors_posts_links' ) ) {
        return coauthors_posts_links( null, null, '<span class="entry-author-name" itemprop="name">', '</span>', false );
    } else {
        return $output;
    }
}

/**
 * Output Co-Authors Plus names on [post_author_link] shortcode
 */
//we need to remove post_author_link shortcode added by genesis because if no url is present on the user meta, it automatically calls the post_author shortcode
add_action( 'after_setup_theme', 'gcp_handle_shortcodes', 10 );
function gcp_handle_shortcodes() {

    remove_shortcode( 'post_author_link' );

    add_shortcode( 'post_author_link', 'gcap_post_author_link_handler' );

}

function gcap_post_author_link_handler() {

    /*
     * coauthors_links depends on the url meta of the author and not really on the 'website' field inside the co author admin area. So we need to create a custom one
     */
    if( function_exists( 'coauthors_links' ) ) {
        //return coauthors_links( null, null, '<span class="entry-author-name" itemprop="name">', '</span>', false );

        $authors = get_coauthors();
        $content = '';
        $counter = 1;

        foreach( $authors as $author ) {

            if( empty( $author->website ) ) {
                $content .= $author->display_name;
            } else {
                $content .= sprintf( '<a href="%s" title="%s" rel="external">%s</a>',
                    $author->website,
                    esc_attr( sprintf(__("Visit %s&#8217;s website"), $author->display_name) ),
                    $author->display_name
                );
            }

            $counter++;

            if( $counter == count( $authors ) )
                $content .= ' and ';
            else if( $counter > count( $authors ) )
                $content .= '';
            else
                $content .= ', ';
        }
        return $content;

    } else {
        return genesis_post_author_link_shortcode();
    }

}
