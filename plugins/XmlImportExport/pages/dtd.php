<?php
/**
 * MantisBT - A PHP based bugtracking system
 *
 * MantisBT is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MantisBT is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright 2024  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 */

/**
 * Allow download of the Mantis DTD for XML export
 */

$t_dtd_file = __DIR__ . "/../mantis.dtd";

if( !is_readable( $t_dtd_file ) ) {
	http_response_code( HTTP_STATUS_NOT_FOUND );
	die();
}

header( 'Content-Type: application/xml-dtd' );
header( 'Content-Disposition: filename="mantis.dtd"' );
header( 'Content-Length: ' . filesize( $t_dtd_file ) );

readfile( $t_dtd_file );
