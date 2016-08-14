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
 * Sniff: Mantis.Arrays.ArrayDeclaration
 *
 * CodeSniffer rule for MantisBT coding guidelines.
 *
 * @package    MantisBT_build
 * @subpackage CodeSniffer
 * @copyright  Copyright 2016  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link       http://www.mantisbt.org
 */

if (class_exists('Squiz_Sniffs_Arrays_ArrayDeclarationSniff', true) === false) {
	throw new PHP_CodeSniffer_Exception('Class Squiz_Sniffs_Arrays_ArrayDeclarationSniff not found');
}

/**
 * A test to ensure that arrays conform to the array coding standard.
 *
 * Extends the original sniff to check for presence of a single space after
 * the opening, and before the closing parenthesis.
 */
class Mantis_Sniffs_Arrays_ArrayDeclarationSniff extends Squiz_Sniffs_Arrays_ArrayDeclarationSniff
{
	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
	{
		parent::process( $phpcsFile, $stackPtr );

		$tokens = $phpcsFile->getTokens();
		$arrayStart = $tokens[$stackPtr]['parenthesis_opener'];
		if( isset($tokens[$arrayStart]['parenthesis_closer'] ) === false ) {
			return;
		}
		$arrayEnd = $tokens[$arrayStart]['parenthesis_closer'];

		# Single-line arrays only
		if( $tokens[$arrayStart]['line'] === $tokens[$arrayEnd]['line'] ) {

			# Don't process empty arrays
			if( $tokens[$arrayStart + 1]['code'] !== ')' ) {
				return;
			}

			# Check space after opening parenthesis
			if( $tokens[$arrayStart + 1]['code'] !== T_WHITESPACE ) {
				$content = $tokens[($arrayStart + 1)]['content'];
				$error   = 'Expected 1 space after array opening bracket; 0 found';
				$data    = array( $content );
				$fix     = $phpcsFile->addFixableError( $error, $arrayStart, 'NoSpaceAfterOpenBracket', $data );
				if ($fix === true) {
					$phpcsFile->fixer->addContentBefore($arrayStart + 1, ' ');
				}
			} else {
				$spaceLength = $tokens[($arrayStart + 1)]['length'];
				if ($spaceLength !== 1) {
					$content = $tokens[($arrayStart + 1)]['content'];
					$error   = 'Expected 1 space after array opening bracket; %s found';
					$data    = array( $spaceLength );
					$fix     = $phpcsFile->addFixableError( $error, $arrayStart, 'SpaceAfterOpenBracket', $data );
					if ($fix === true) {
						$phpcsFile->fixer->replaceToken($arrayStart + 1, ' ');
					}
				}
			}

			# Check space before closing parenthesis
			if( $tokens[$arrayEnd - 1]['code'] !== T_WHITESPACE ) {
				$content = $tokens[($arrayEnd - 1)]['content'];
				$error   = 'Expected 1 space before array closing bracket; 0 found';
				$data    = array( $content );
				$fix     = $phpcsFile->addFixableError( $error, $arrayEnd, 'NoSpaceBeforeCloseBracket', $data );
				if ($fix === true) {
					$phpcsFile->fixer->addContentBefore($arrayEnd, ' ');
				}
			} else {
				$spaceLength = $tokens[($arrayEnd - 1)]['length'];
				if ($spaceLength !== 1) {
					$content = $tokens[($arrayEnd - 1)]['content'];
					$error   = 'Expected 1 space before array closing bracket; %s found';
					$data    = array( $spaceLength );
					$fix     = $phpcsFile->addFixableError( $error, $arrayEnd , 'SpaceBeforeCloseBracket', $data );
					if ($fix === true) {
						$phpcsFile->fixer->replaceToken($arrayEnd - 1, ' ');
					}
				}
			}

		}
	}
}
