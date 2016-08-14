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
 * Sniff: Mantis.NamingConventions.ValidVariableName
 *
 * CodeSniffer rule for MantisBT coding guidelines.
 *
 * @package    MantisBT_build
 * @subpackage CodeSniffer
 * @copyright  Copyright 2016  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link       http://www.mantisbt.org
 */

if( class_exists( 'PHP_CodeSniffer_Standards_AbstractVariableSniff', true ) === false ) {
	 throw new PHP_CodeSniffer_Exception( 'Class PHP_CodeSniffer_Standards_AbstractVariableSniff not found' );
}

/**
 * Checks the naming of variables and member variables.
 *
 * Loosely based on PHP_CodeSniffer 2.5.0 rule:
 * Squiz_Sniffs_NamingConventions_ValidVariableNameSniff
 */
class Mantis_Sniffs_NamingConventions_ValidVariableNameSniff extends PHP_CodeSniffer_Standards_AbstractVariableSniff {

	/**
	 * @var array $phpReservedVars List of PHP reserved variable names
	 */
	protected $phpReservedVars = array(
		'_SERVER',
		'_GET',
		'_POST',
		'_REQUEST',
		'_SESSION',
		'_ENV',
		'_COOKIE',
		'_FILES',
		'GLOBALS',
		'http_response_header',
		'HTTP_RAW_POST_DATA',
		'php_errormsg',
		'this',
	);

	/**
	 * @var array $staticVars List of declared static variables
	 */
	protected $staticVars = array();

	/**
	 * Make sure that variables are properly prefixed.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param integer              $stackPtr  The position of the current token in the
	 *                                        stack passed in $tokens.
	 *
	 * @return void
	 */
	protected function processVariable( PHP_CodeSniffer_File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();
		$varName = ltrim( $tokens[$stackPtr]['content'], '$' );

		# Ignore static class variables
		if( $tokens[$stackPtr - 1]['code'] == T_DOUBLE_COLON ) {
			return;
		}

		# Retrieve the variable's modifier if present
		$varModifier = $phpcsFile->findPrevious(
			array( T_GLOBAL, T_STATIC ),
			$stackPtr,
			$phpcsFile->findStartOfStatement($stackPtr),
			false,
			null,
			true
		);

		$this->checkVariable(
			$phpcsFile,
			$stackPtr,
			$varName,
			$tokens[$varModifier]['code']
		);
	}

	/**
	 * Processes class member variables.
	 *
	 * There are currently no naming conventions for class member variables in
	 * MantisBT, so this does not do anything.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param integer              $stackPtr  The position of the current token in the
	 *                                        stack passed in $tokens.
	 *
	 * @return void
	 */
	protected function processMemberVar( PHP_CodeSniffer_File $phpcsFile, $stackPtr ) {
		return;
	}


	/**
	 * Processes the variable found within a double quoted string.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param integer              $stackPtr  The position of the double quoted
	 *                                        string.
	 *
	 * @return void
	 */
	protected function processVariableInString( PHP_CodeSniffer_File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		$result = preg_match_all(
			'|[^\\\]\${?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)|',
			$tokens[$stackPtr]['content'],
			$matches
		);

		if( $result ) {
			foreach( $matches[1] as $varName ) {
				$this->checkVariable( $phpcsFile, $stackPtr, $varName );
			}
		}
	}

	/**
	 * Check if variable is a PHP reserved name.
	 * @param string $var Variable name.
	 * @return bool True if reserved, false otherwise
	 */
	protected function isReservedVar( $var ) {
		return in_array( $var, $this->phpReservedVars );
	}

	/**
	 * Performs the actual check for variable name validity.
	 *
	 * Except for reserved variables, ADODB globals and single-char variables,
	 * all variables must have a 1-char prefix [cfgptuv].
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param integer              $stackPtr  Current token's position in
	 *                                        the stack.
	 * @param string               $varName   Variable name to check.
	 * @param integer              $qualifier Qualifying token's type (code):
	 *                                        either T_GLOBAL or T_STATIC;
	 *                                        defaults to null, causing
	 *                                        additional checks for global and
	 *                                        static variables to be skipped.
	 *
	 * @return null
	 */
	protected function checkVariable( PHP_CodeSniffer_File $phpcsFile, $stackPtr, $varName, $qualifier = null ) {
		# If it's a PHP reserved variable, then it's ok
		if( $this->isReservedVar( $varName ) ) {
			return null;
		}

		# Exclude ADOdb global variables
		if( substr( $varName, 0, 5 ) == 'ADODB' ) {
			return null;
		}

		# Single-char variables (e.g. for loops counters) are OK
		if( strlen( $varName ) == 1 ) {
			return null;
		}

		# Only accept lowercase letters, numbers and underscores
		if( !preg_match( '/[a-z_][a-z0-9_]*/', $varName ) ) {
			$phpcsFile->addError(
				'Only lowercase letters, numbers and underscores are allowed for variable "%s"',
				$stackPtr,
				'UseLowerCase',
				array( $varName )
			);

		}

		# Check if we have a prefix
		if( substr( $varName, 1, 1 ) !== '_' ) {
			$phpcsFile->addError(
				'Missing prefix for variable "%s"',
				$stackPtr,
				'MissingPrefix',
				array( $varName )
			);
		} else {
			$prefix = substr( $varName, 0, 1 );

			if( $qualifier != null ) {
				$tokens = $phpcsFile->getTokens();
				$level = $tokens[$stackPtr]['level'];

				# Process global and static variable declarations
				if( $qualifier === T_GLOBAL && $level == 0 && $prefix != 'g' ) {
					# Global vars must have 'g_' prefix if not declared within
					# a function
					$phpcsFile->addWarning(
						'Global variable "%s" should be prefixed with "g_"',
						$stackPtr,
						'WrongPrefixGlobal',
						array( $varName )
					);
				} elseif( $qualifier === T_STATIC ) {
					if( $prefix != 's' ) {
						$phpcsFile->addError(
							'Static variable "%s" should be prefixed with "s_"',
							$stackPtr,
							'WrongPrefixStatic',
							array( $varName )
						);
					} else {
						# Store valid static variable name for next check
						$this->staticVars[] = $varName;
					}
				}
			}

			switch( $prefix ) {
				case 's':
					# Check that static variable has been declared
					if( $qualifier != null && !in_array( $varName, $this->staticVars ) ) {
						$phpcsFile->addError(
							'Static Variable "%s" has not been declared',
							$stackPtr,
							'StaticNotDeclared',
							array( $varName )
						);
					}
					break;

				# Other allowed variable prefixes
				case 'c': # Clean
				case 'f': # Form
				case 'g': # Global
				case 'p': # Function parameters
				case 't': # Local
				case 'u': # User variables
				case 'v': # Extracted variables
					break;

				default:
					$phpcsFile->addError(
						'Invalid prefix "%s_" for variable "%s"',
						$stackPtr,
						'InvalidPrefix',
						array( $prefix, $varName )
					);
					break;
			}
		}
		return null;
	}

}
