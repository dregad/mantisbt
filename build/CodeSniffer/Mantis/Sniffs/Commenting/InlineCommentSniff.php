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
 * Sniff: Mantis.Commenting.InlineComment
 *
 * CodeSniffer rule for MantisBT coding guidelines.
 *
 * @package    MantisBT_build
 * @subpackage CodeSniffer
 * @copyright  Copyright 2016  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link       http://www.mantisbt.org
 */

/**
 * Checks inline comments type, spacing and formatting.
 *
 * Based on PHP_CodeSniffer 2.5.0 rule:
 * Squiz_Sniffs_Commenting_InlineCommentSniff
 */
class Mantis_Sniffs_Commenting_InlineCommentSniff implements PHP_CodeSniffer_Sniff
{

	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = array(
		'PHP',
		'JS',
	);


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register()
	{
		return array(
			T_COMMENT,
			T_DOC_COMMENT_OPEN_TAG,
		);

	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param int                  $stackPtr  The position of the current token in the
	 *                                        stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		// If this is a function/class/interface doc block comment, skip it.
		// We are only interested in inline doc block comments, which are
		// not allowed.
		if ($tokens[$stackPtr]['code'] === T_DOC_COMMENT_OPEN_TAG) {
			$nextToken = $phpcsFile->findNext(
				PHP_CodeSniffer_Tokens::$emptyTokens,
				($stackPtr + 1),
				null,
				true
			);

			$ignore = array(
				T_CLASS,
				T_INTERFACE,
				T_TRAIT,
				T_FUNCTION,
				T_CLOSURE,
				T_PUBLIC,
				T_PRIVATE,
				T_PROTECTED,
				T_FINAL,
				T_STATIC,
				T_ABSTRACT,
				T_CONST,
				T_PROPERTY,
			);

			if (in_array($tokens[$nextToken]['code'], $ignore) === true) {
				return;
			}

			if ($phpcsFile->tokenizerType === 'JS') {
				// We allow block comments if a function or object
				// is being assigned to a variable.
				$ignore    = PHP_CodeSniffer_Tokens::$emptyTokens;
				$ignore[]  = T_EQUAL;
				$ignore[]  = T_STRING;
				$ignore[]  = T_OBJECT_OPERATOR;
				$nextToken = $phpcsFile->findNext($ignore, ($nextToken + 1), null, true);
				if ($tokens[$nextToken]['code'] === T_FUNCTION
					|| $tokens[$nextToken]['code'] === T_CLOSURE
					|| $tokens[$nextToken]['code'] === T_OBJECT
					|| $tokens[$nextToken]['code'] === T_PROTOTYPE
				) {
					return;
				}
			}

			$prevToken = $phpcsFile->findPrevious(
				PHP_CodeSniffer_Tokens::$emptyTokens,
				($stackPtr - 1),
				null,
				true
			);

			if ($tokens[$prevToken]['code'] === T_OPEN_TAG) {
				return;
			}

			if ($tokens[$stackPtr]['content'] === '/**') {
				$error = 'Inline doc block comments are not allowed; use "/* Comment */" or "# Comment" instead';
				$phpcsFile->addError($error, $stackPtr, 'DocBlock');
			}
		}//end if

		if ($tokens[$stackPtr]['content']{0} === '/' && $tokens[$stackPtr]['content']{1} === '/') {
			$error = '//-style comments are not allowed; use "# Comment" instead';
			$fix   = $phpcsFile->addFixableError($error, $stackPtr, 'WrongStyle');
			if ($fix === true) {
				$comment = ltrim($tokens[$stackPtr]['content'], "/ \t");
				$phpcsFile->fixer->replaceToken($stackPtr, "# $comment");
			}
		}

		// We don't want end of block comments. If the last comment is a closing
		// curly brace.
		$previousContent = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
		if ($tokens[$previousContent]['line'] === $tokens[$stackPtr]['line']) {
			if ($tokens[$previousContent]['code'] === T_CLOSE_CURLY_BRACKET) {
				return;
			}

			// Special case for JS files.
			if ($tokens[$previousContent]['code'] === T_COMMA
				|| $tokens[$previousContent]['code'] === T_SEMICOLON
			) {
				$lastContent = $phpcsFile->findPrevious(T_WHITESPACE, ($previousContent - 1), null, true);
				if ($tokens[$lastContent]['code'] === T_CLOSE_CURLY_BRACKET) {
					return;
				}
			}
		}

		$comment = rtrim($tokens[$stackPtr]['content']);

		// Only want inline comments.
		// Check for empty string to avoid system notice when we have an
		// empty line inside of a block comment
		if ($comment && $comment{0} !== '#') {
			return;
		}

		if (trim(substr($comment, 1)) !== '') {
			$spaceCount = 0;
			$tabFound   = false;

			$commentLength = strlen($comment);
			for ($i = 1; $i < $commentLength; $i++) {
				if ($comment[$i] === "\t") {
					$tabFound = true;
					break;
				}

				if ($comment[$i] !== ' ') {
					break;
				}

				$spaceCount++;
			}

			$fix = false;
			if ($tabFound === true) {
				$error = 'Tab found before comment text; expected "# %s" but found "%s"';
				$data  = array(
					ltrim(substr($comment, 1)),
					$comment,
				);
				$fix   = $phpcsFile->addFixableError($error, $stackPtr, 'TabBefore', $data);
			} else if ($spaceCount === 0) {
				$error = 'No space found before comment text; expected "# %s" but found "%s"';
				$data  = array(
					substr($comment, 1),
					$comment,
				);
				$fix   = $phpcsFile->addFixableError($error, $stackPtr, 'NoSpaceBefore', $data);
			} else if ($spaceCount > 1) {
				$error = 'Expected 1 space before comment text but found %s; use block comment if you need indentation';
				$data  = array(
					$spaceCount,
					substr($comment, (2 + $spaceCount)),
					$comment,
				);
				$fix   = $phpcsFile->addFixableError($error, $stackPtr, 'SpacingBefore', $data);
			}//end if

			if ($fix === true) {
				$newComment = '# '.ltrim($tokens[$stackPtr]['content'], "#\t ");
				$phpcsFile->fixer->replaceToken($stackPtr, $newComment);
			}
		}//end if

		// The below section determines if a comment block is correctly capitalised,
		// and ends in a full-stop. It will find the last comment in a block, and
		// work its way up.
		$nextComment = $phpcsFile->findNext(array(T_COMMENT), ($stackPtr + 1), null, false);
		if (($nextComment !== false)
			&& (($tokens[$nextComment]['line']) === ($tokens[$stackPtr]['line'] + 1))
		) {
			return;
		}

		$topComment  = $stackPtr;
		$lastComment = $stackPtr;
		while (($topComment = $phpcsFile->findPrevious(array(T_COMMENT), ($lastComment - 1), null, false)) !== false) {
			if ($tokens[$topComment]['line'] !== ($tokens[$lastComment]['line'] - 1)) {
				break;
			}

			$lastComment = $topComment;
		}

		$topComment  = $lastComment;
		$commentText = '';

		for ($i = $topComment; $i <= $stackPtr; $i++) {
			if ($tokens[$i]['code'] === T_COMMENT) {
				$commentText .= trim(substr($tokens[$i]['content'], 2));
			}
		}

		if ($commentText === '') {
			$error = 'Blank comments are not allowed';
			$fix   = $phpcsFile->addFixableError($error, $stackPtr, 'Empty');
			if ($fix === true) {
				$phpcsFile->fixer->replaceToken($stackPtr, '');
			}

			return;
		}

		// Finally, the line below the last comment cannot be empty if this inline
		// comment is on a line by itself.
		if ($tokens[$previousContent]['line'] < $tokens[$stackPtr]['line']) {
			$start = false;
			for ($i = ($stackPtr + 1); $i < $phpcsFile->numTokens; $i++) {
				if ($tokens[$i]['line'] === ($tokens[$stackPtr]['line'] + 1)) {
					if ($tokens[$i]['code'] !== T_WHITESPACE) {
						return;
					}
				} else if ($tokens[$i]['line'] > ($tokens[$stackPtr]['line'] + 1)) {
					break;
				}
			}

			$error = 'There must be no blank line following an inline comment';
			$fix   = $phpcsFile->addFixableError($error, $stackPtr, 'SpacingAfter');
			if ($fix === true) {
				$next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
				$phpcsFile->fixer->beginChangeset();
				for ($i = ($stackPtr + 1); $i < $next; $i++) {
					if ($tokens[$i]['line'] === $tokens[$next]['line']) {
						break;
					}

					$phpcsFile->fixer->replaceToken($i, '');
				}

				$phpcsFile->fixer->endChangeset();
			}
		}//end if

	}//end process()


}//end class
