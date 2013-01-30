<?php
# MantisBT - a php based bugtracking system

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
	 * @package MantisBT
	 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	 * @copyright Copyright (C) 2002 - 2011  MantisBT Team - mantisbt-dev@lists.sourceforge.net
	 * @link http://www.mantisbt.org
	 */

	 /**
	  * MantisBT Core API's
	  */
	require_once( 'core.php' );
	require_once( 'category_api.php' );

	form_security_validate( 'manage_proj_cat_default' );
	auth_reauthenticate();

	$f_category_id		= gpc_get_int( 'id' );
	$f_project_id		= gpc_get_int( 'project_id' );
	$f_set_default		= gpc_get_bool( 'default' );

	access_ensure_project_level( config_get( 'manage_project_threshold' ), $f_project_id );

	$t_row = category_get_row( $f_category_id );

	if( ALL_PROJECTS == $f_project_id ) {
		config_set( 'default_category_for_moves', $f_category_id );
		$t_redirect_url = 'manage_proj_page.php';
	}
	else {
		project_set_default_category( $f_project_id, $f_category_id );
		$t_redirect_url = 'manage_proj_edit_page.php?project_id=' . $f_project_id;
	}

	form_security_purge( 'manage_proj_cat_default' );

	html_page_top( null, $t_redirect_url );
?>
<br />
<div align="center">
<?php
	echo lang_get( 'operation_successful' ) . '<br />';
	print_bracket_link( $t_redirect_url, lang_get( 'proceed' ) );
?>
</div>

<?php
	html_page_bottom();
