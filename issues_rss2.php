<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * GET PARAMETERS FOR THIS PAGE
 *
 * project_id: 0 - all projects, otherwise project id.
 * filter_id: The filter id to use for generating the rss.
 * sort: This parameter is ignore if filter_id is supplied and is not equal to 0.
 *		"update": issues ordered descending by last updated date.
 *       "submit": issues ordered descending by submit date (default).
 *
 * @package MantisBT
 * @copyright Copyright 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright 2002  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses core.php
 * @uses access_api.php
 * @uses bug_api.php
 * @uses category_api.php
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses filter_api.php
 * @uses gpc_api.php
 * @uses lang_api.php
 * @uses project_api.php
 * @uses rss_api.php
 * @uses string_api.php
 * @uses user_api.php
 * @uses utility_api.php
 */

# Prevent output of HTML in the content if errors occur
//define( 'DISABLE_INLINE_ERROR_REPORTING', true );

require_once( 'core.php' );
require_api( 'access_api.php' );
require_api( 'bug_api.php' );
require_api( 'category_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'filter_api.php' );
require_api( 'gpc_api.php' );
require_api( 'lang_api.php' );
require_api( 'project_api.php' );
require_api( 'rss_api.php' );
require_api( 'string_api.php' );
require_api( 'user_api.php' );
require_api( 'utility_api.php' );

$f_project_id = gpc_get_int( 'project_id', ALL_PROJECTS );
$f_filter_id = gpc_get_int( 'filter_id', 0 );
$f_sort = gpc_get_string( 'sort', 'submit' );
$f_username = gpc_get_string( 'username', null );
$f_key = gpc_get_string( 'key', null );

# make sure RSS syndication is enabled.
if( OFF == config_get( 'rss_enabled' ) ) {
	access_denied();
}

# authenticate the user
if( $f_username !== null ) {
	if( !rss_login( $f_username, $f_key ) ) {
		access_denied();
	}
} else {
	if( !auth_anonymous_enabled() ) {
		access_denied();
	}
}

# Make sure that the current user has access to the selected project (if not ALL PROJECTS).
if( $f_project_id != ALL_PROJECTS ) {
	access_ensure_project_level( config_get( 'view_bug_threshold', null, null, $f_project_id ), $f_project_id );
}

if( $f_sort === 'update' ) {
	$c_sort_field = 'last_updated';
} else {
	$c_sort_field = 'date_submitted';
}

if( $f_filter_id == 0 ) {
	$t_custom_filter = filter_get_default();
	$t_custom_filter['sort'] = $c_sort_field;
} else {
	# null will be returned if the user doesn't have access to the filter.
	$t_custom_filter = filter_get( $f_filter_id, null );
	if( null === $t_custom_filter ) {
		access_denied();
	}
}

# Override current user
if( $f_username !== null ) {
	$t_user_id = user_get_id_by_name( $f_username );
} else {
	$t_user_id = user_get_id_by_name( auth_anonymous_account() );
}
current_user_set( $t_user_id );

$t_path = config_get_global( 'path' );

# construct rss file

$t_title = config_get( 'window_title' );
$t_image_link = $t_path . config_get_global( 'logo_image' );

# only rss 2.0
$t_category = project_get_name( $f_project_id );
if( $f_project_id !== 0 ) {
	$t_title .= ' - ' . $t_category;
}

$t_title .= ' - ' . lang_get( 'issues' );

if( $f_username !== null ) {
	$t_title .= ' - (' . $f_username . ')';
}

if( $f_filter_id !== 0 ) {
	$t_title .= ' (' . filter_get_field( $f_filter_id, 'name' ) . ')';
}

$t_feed = new FeedWriter\RSS2();
$t_feed
	->setTitle( $t_title )
	->setDescription( $t_title )
	->setLink( $t_path )
	->setImage( $t_image_link, $t_title, $t_path )
	->setChannelElement( 'language', lang_get( 'phpmailer_language' ) )
	->setDate( time() )
	;

# Retrieve issues
$t_page_number = 1;
$t_issues_per_page = 25;
$t_page_count = 0;
$t_issues_count = 0;

$t_issues = filter_get_bug_rows(
	$t_page_number,
	$t_issues_per_page,
	$t_page_count,
	$t_issues_count,
	$t_custom_filter,
	$f_project_id,
	$t_user_id
);

# Loop through results
foreach( $t_issues as $t_bug ) {
	$t_title = bug_format_id( $t_bug->id ) . ': ' . $t_bug->summary;
	$t_link = $t_path . 'view.php?id=' . $t_bug->id;

	if( $t_bug->view_state == VS_PRIVATE ) {
		$t_title .= ' [' . lang_get( 'private' ) . ']';
	}

	$t_description = string_rss_links( $t_bug->description );

	# subject is category.
	$t_subject = category_full_name( $t_bug->category_id, false );

	# author of item
	$t_author = '';
	if( access_has_global_level( config_get( 'show_user_email_threshold' ) ) ) {
		$t_author_name = user_get_name( $t_bug->reporter_id );
		$t_author_email = user_get_field( $t_bug->reporter_id, 'email' );

		if( !is_blank( $t_author_email ) ) {
			if( !is_blank( $t_author_name ) ) {
				$t_author = $t_author_name . ' <' . $t_author_email . '>';
			} else {
				$t_author = $t_author_email;
			}
		}
	}

	$t_comments = $t_link . '#bugnotes';

	$t_item = $t_feed->createNewItem()
		->setId( $t_bug->id )
		->setTitle( $t_title )
		->setDescription( string_rss_links( $t_bug->description ) )
		->setAuthor( $t_author )
		->setDate( $t_bug->last_updated )
		->setLink( $t_link )
		->addElement( 'category',  category_full_name( $t_bug->category_id, false ) )
		->addElement( 'comments',  $t_comments );
	$t_feed->addItem($t_item);
}

$t_feed->printFeed(true);
