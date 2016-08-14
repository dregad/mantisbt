<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.	If not, see <http://www.gnu.org/licenses/>.

/**
 * Sniff: Mantis.ControlStructures.ControlSignature
 *
 * CodeSniffer rule for MantisBT coding guidelines.
 *
 * @package    MantisBT_build
 * @subpackage CodeSniffer
 * @copyright  Copyright 2016  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link       http://www.mantisbt.org
 */


if (class_exists('PHP_CodeSniffer_Standards_AbstractPatternSniff', true) === false) {
	throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_AbstractPatternSniff not found');
}

/**
 * Verifies that control statements conform to their coding standards.
 *
 * Unfortunately this sniff detects but does not allow automatic fixing of
 * offending statements.
 */
class Mantis_Sniffs_ControlStructures_ControlSignatureSniff extends PHP_CodeSniffer_Standards_AbstractPatternSniff {
	/**
	 * If true, comments will be ignored if they are found in the code.
	 *
	 * @var boolean
	 */
	public $ignoreComments = true;

	/**
	 * Returns the patterns that this test wishes to verify.
	 *
	 * @return array(string)
	 */
	protected function getPatterns() {
		return array(
			'do {EOL...} while(...);EOL',
			'while(...) {EOL',
			'switch(...) {EOL',
			'for(...) {EOL',
			'if(...) {EOL',
			'foreach(...) {EOL',
			'} else if(...) {EOL',
			'} elseif(...) {EOL',
			'} else {EOL',
			'do {EOL',
		);
	}
}
