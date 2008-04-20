<?php
/**
 * Zend Framework Coding Standard
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_CodingStandard
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: $
 */
if (class_exists('PHP_CodeSniffer_Standards_CodingStandard', true) === false) {
    throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_CodingStandard not found');
}

/**
 * Zend Framework Coding Standard.
 *
 * @category   Zend
 * @package    Zend_CodingStandard
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: $
 */
class PHP_CodeSniffer_Standards_Zend_ZendCodingStandard extends PHP_CodeSniffer_Standards_CodingStandard
{
/**
 * Processed Sniffs
 * ================
 * ARRAY SNIFFS
 * ------------
 * ArrayBracketSpacingSniffs Ensure that there are no spaces around square brackets
 *
 * CLASSES SNIFFS
 * --------------
 * ClassFileNameSniff          Tests that the file name and the name of the class contained within
 *                             the file match
 * LowercaseClassKeywordsSniff Ensures all class keywords are lowercase
 * SelfMemberReferenceSniff    Tests self member references
 *
 * COMMENTING SNIFFS
 * -----------------
 * BlockCommentSniff            Verifies that block comments are used appropriately
 * ClassCommentSniff            Parses and verifies the class doc comment
 * DocCommentAlignmentSniff     Tests that the stars in a doc comment align correctly
 * EmptyCatchCommentSniff       Checks for empty Catch clause, these must have at least one comment
 * FunctionCommentSniff         Parses and verifies the doc comments for functions
 * FunctionCommentThrowTagSniff Verifies that a @throws tag exists for a function that throws
 *                              exceptions, verifies the number of @throws tags and the number of
 *                              throw tokens matches, verifies the exception type
 * InlineCommentSniff           Checks that no perl-style comments (#) are used
 * PostStatementCommentSniff    Checks to ensure that there are no comments after statements
 * VariableCommentSniff         Parses and verifies the variable doc comment
 *
 * CONTROLSTRUCTURE SNIFFS
 * -----------------------
 * ElseIfDeclarationSniff      Verifies that there are not elseif statements. The else and the if
 *                             should be separated by a space
 * ForLoopDeclarationSniff     Verifies that there is a space between each condition of for loops
 * ForEachLoopDeclarationSniff Verifies that there is a space between each condition of foreach loops
 * LowercaseDeclarationSniff   Ensures all control structure keywords are lowercase
 * SwitchDeclarationSniff      Ensures all the breaks and cases are aligned correctly according to
 *                             their parent switch's alignment and enforces other switch formatting
 *
 * FILE SNIFFS
 * -----------
 * ClosingTagSniff    Checks that the file does not include a closing tag
 *                    Wether at file end nor inline for output purposes
 * IncludingFileSniff Checks that the include_once is used in conditional situations, and
 *                    require_once is used elsewhere. Also checks that brackets do not surround
 *                    the file being included. And checks that require_once and include_once are
 *                    used instead of require and include
 * LineLengthSniff    Checks all lines in the file, and throws warnings if they are over 100
 *                    characters in length and errors if they are over 120
 * LineEndingsSniff   Checks for Unix (\n) linetermination, disallowing Windows (\r\n) or Max (\r)
 *
 * FORMATTING SNIFFS
 * -----------------
 * MultipleStatementAlignSniff Checks alignment of assignments. If there are multiple adjacent
 *                             assignments, it will check that the equals signs of each assignment
 *                             are aligned. It will display a warning to advise that the signs
 *                             should be aligned
 * OperatorBracketSniff        Tests that all arithmetic operations are bracketed
 * OutputBufferingIndentSniff  Checks the indenting used when an ob_start() call occurs
 * SpaceAfterCastSniff         Ensures there is a single space after cast tokens
 *
 * FUNCTION SNIFFS
 * ---------------
 * GlobalFunctionSniff       Tests for functions outside of classes
 * LowercaseKeywordsSniff    Ensures all class keywords are lowercase
 * OpeningFunctionBraceSniff Checks that the opening brace of a function is on the line after the
 *                           function declaration
 * ValidDefaultValueSniff    A Sniff to ensure that parameters defined for a function that have a
 *                           default value come at the end of the function signature
 *
 * METRICS
 * -------
 * NestingLevelSniff Checks the nesting level for methods
 *
 * NAMINGCONVENTIONS SNIFFS
 * ------------------------
 * UpperCaseConstantNameSniff Ensures that constant names are all uppercase
 * ValidClassNameSniff        Ensures class and interface names start with a capital letter
 *                            and use _ separators
 *
 * OBJECT SNIFFS
 * -------------
 * ObjectInstantiationSniff Ensures objects are assigned to a variable when instantiated
 *
 * OPERATOR SNIFFS
 * ---------------
 * ComparisonOperatorUsageSniff Enforce the use of IDENTICAL type operators rather than EQUAL
 *                              operators. The use of === true is enforced over implicit true
 *                              statements, It also enforces the use of === false over ! operators.
 * IncrementDecrementUsageSniff Tests that the ++ operators are used when possible
 * ValidLogicalOperatorsSniff   Checks to ensure that the logical operators 'and' and 'or' are used
 *                              instead of the && and || operators
 *
 * PHP SNIFFS
 * ----------
 * CommentedOutCodeSniff            Warn about commented out code
 * DisallowCountInLoopsSniff        Disallows the use of count in loop conditions
 * DisallowInlineIfSniff            Stops inline IF statements from being used
 * DisallowMultipleAssignmentsSniff Ensures that there is only one value assignment on a line, and
 *                                  that it is the first thing on the line
 * DisallowObEndFlushSniff          Disallow ob_end_flush, use ob_get_contents() and ob_end_clean() instead
 * DisallowShortOpenTagSniff        Makes sure that shorthand PHP open tags are not used ("<?"), but allows open
 *                                  tag with echo ("<?="). short_open_tag must be set to true for this test to work
 * EvalSniff                        The use of eval() is discouraged
 * ForbiddenFunctionsSniff          Discourages the use of alias functions that are kept in PHP for compatibility
 *                                  with older versions. Can be used to forbid the use of any function
 * GlobalKeywordSniff               Stops the usage of the "global" keyword
 * HeredocSniff                     Heredocs are discuraged
 * InnerFunctionsSniff              Ensures that functions within functions are never used
 * LowerCaseConstantSniff           Checks that all uses of 'true', 'false' and 'null' are lowercase
 * LowercasePHPFunctionsSniff       Ensures all calls to inbuilt PHP functions are lowercase
 * NonExecutableCodeSniff           Warns about code that can never been executed. This happens when a function
 *                                  returns before the code, or a break ends execution of a statement etc
 * ReturnFunctionValueSniff         Warns when function values are returned directly
 *
 * SCOPE SNIFFS
 * ------------
 * MemberVarScopeSniff  Verifies that class variables have scope modifiers
 * MethodScopeSniff     Verifies that class members have scope modifiers
 * StaticThisUsageSniff Checks for usage of "$this" in static methods, which will cause runtime errors
 *
 * STRING SNIFFS
 * -------------
 * EchoedStringsSniff Makes sure that any strings that are "echoed" are not enclosed in brackets like a function call
 *
 * WHITESPACE SNIFFS
 * -----------------
 * CastSpacingSniff               Ensure cast statements dont contain whitespace
 * ConcatenationSpacingSniff      Makes sure there are no spaces between the concatenation operator (.) and
 *                                the strings being concatenated
 * DisallowTabSniff               Checks if tabs are used and errors if any are found
 * DoubleQuoteUsageSniff          Makes sure that any use of Double Quotes ("") are warranted
 * FunctionOpeningBraceSpaceSniff Checks that there is no empty line after the opening brace of a function
 * FunctionSpacingSniff           Checks the separation between methods in a class or interface
 * LanguageConstructSpacingSniff  Ensures all language constructs (without brackets) contain a
 *                                single space between themselves and their content
 * ScopeClosingBraceSniff         Checks that the closing braces of scopes are aligned correctly
 * SemicolonSpacingSniff          Ensure there is no whitespace before a semicolon
 */
}//end class