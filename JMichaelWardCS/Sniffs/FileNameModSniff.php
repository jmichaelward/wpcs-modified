<?php
namespace JMichaelWardCS\JMichaelWardCS\Sniffs;

use WordPressCS\WordPress\Sniffs\Files\FileNameSniff;

class FileNameModSniff extends FileNameSniff {
	public $strict_class_file_names = false;
	/**
	 * Historical exceptions in WP core to the class name rule.
	 *
	 * @since 0.11.0
	 *
	 * @var array
	 */
	private $class_exceptions = array(
		'class.wp-dependencies.php' => true,
		'class.wp-scripts.php'      => true,
		'class.wp-styles.php'       => true,
	);

	/**
	 * Determine whether the active file is expecting a lowercase file name.
	 *
	 * @param $stackPointer
	 *
	 * @return bool
	 */
	private function expects_lowercase_filename( $stackPointer ) {
		return ! $this->phpcsFile->findNext( \T_CLASS, $stackPointer );
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 *
	 * @return int|void Integer stack pointer to skip forward or void to continue
	 *                  normal file processing.
	 */
	public function process_token( $stackPtr ) {
		// Usage of `strip_quotes` is to ensure `stdin_path` passed by IDEs does not include quotes.
		$file = $this->strip_quotes( $this->phpcsFile->getFileName() );
		if ( 'STDIN' === $file ) {
			return;
		}

		// Respect phpcs:disable comments as long as they are not accompanied by an enable (PHPCS 3.2+).
		if ( \defined( '\T_PHPCS_DISABLE' ) && \defined( '\T_PHPCS_ENABLE' ) ) {
			$i = -1;
			while ( $i = $this->phpcsFile->findNext( \T_PHPCS_DISABLE, ( $i + 1 ) ) ) {
				if ( empty( $this->tokens[ $i ]['sniffCodes'] )
				     || isset( $this->tokens[ $i ]['sniffCodes']['WordPress'] )
				     || isset( $this->tokens[ $i ]['sniffCodes']['WordPress.Files'] )
				     || isset( $this->tokens[ $i ]['sniffCodes']['WordPress.Files.FileName'] )
				) {
					do {
						$i = $this->phpcsFile->findNext( \T_PHPCS_ENABLE, ( $i + 1 ) );
					} while ( false !== $i
					          && ! empty( $this->tokens[ $i ]['sniffCodes'] )
					          && ! isset( $this->tokens[ $i ]['sniffCodes']['WordPress'] )
					          && ! isset( $this->tokens[ $i ]['sniffCodes']['WordPress.Files'] )
					          && ! isset( $this->tokens[ $i ]['sniffCodes']['WordPress.Files.FileName'] ) );

					if ( false === $i ) {
						// The entire (rest of the) file is disabled.
						return;
					}
				}
			}
		}

		$fileName = basename( $file );
		$expected = strtolower( str_replace( '_', '-', $fileName ) );

		/*
		 * Generic check for lowercase hyphenated file names.
		 */
		if (
			( $fileName !== $expected && ( false === $this->is_theme || 1 !== preg_match( self::THEME_EXCEPTIONS_REGEX, $fileName ) ) )
			&& $this->expects_lowercase_filename( $stackPtr )
		) {
			$this->phpcsFile->addError(
				'Filenames should be all lowercase with hyphens as word separators. Expected %s, but found %s.',
				0,
				'NotHyphenatedLowercase',
				array( $expected, $fileName )
			);
		}
		unset( $expected );

		/*
		 * Check non-class files in "wp-includes" with a "@subpackage Template" tag for a "-template" suffix.
		 */
		if ( false !== strpos( $file, \DIRECTORY_SEPARATOR . 'wp-includes' . \DIRECTORY_SEPARATOR ) ) {
			$subpackage_tag = $this->phpcsFile->findNext( \T_DOC_COMMENT_TAG, $stackPtr, null, false, '@subpackage' );
			if ( false !== $subpackage_tag ) {
				$subpackage = $this->phpcsFile->findNext( \T_DOC_COMMENT_STRING, $subpackage_tag );
				if ( false !== $subpackage ) {
					$fileName_end = substr( $fileName, -13 );
					$has_class    = $this->phpcsFile->findNext( \T_CLASS, $stackPtr );

					if ( ( 'Template' === trim( $this->tokens[ $subpackage ]['content'] )
					       && $this->tokens[ $subpackage_tag ]['line'] === $this->tokens[ $subpackage ]['line'] )
					     && ( ( ! \defined( '\PHP_CODESNIFFER_IN_TESTS' ) && '-template.php' !== $fileName_end )
					          || ( \defined( '\PHP_CODESNIFFER_IN_TESTS' ) && '-template.inc' !== $fileName_end ) )
					     && false === $has_class
					) {
						$this->phpcsFile->addError(
							'Files containing template tags should have "-template" appended to the end of the file name. Expected %s, but found %s.',
							0,
							'InvalidTemplateTagFileName',
							array(
								substr( $fileName, 0, -4 ) . '-template.php',
								$fileName,
							)
						);
					}
				}
			}
		}

		// Only run this sniff once per file, no need to run it again.
		return ( $this->phpcsFile->numTokens + 1 );
	}
}
