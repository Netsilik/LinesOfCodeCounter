<?php
namespace Netsilik\Util;

use Netsilik\Lib\Cli;

class Loc extends Cli
{
	/**
	 *  @var array $_optionFlags
	 */
	protected $_optionFlags = [
		'name'  => ['all', 'fileMasks', 'help', 'ignoreDirs', 'recursive', 'version'],
		'short' => ['a', 'f:', null, 'i:', 'r', null],
		'long'  => ['all', 'file-mask:', 'help', 'ignore-dir:', 'recursive', 'version'],
	];
	
	/**
	 * @var array $_out
	 */
	private $_out;
	
	/**
	 *
	 */
	public function __construct()
	{
		cli_set_process_title('Lines of Code counter');
		$this->_out = ['Lines of Code counter, by Jacco-V.'.PHP_EOL];
	}
	
	/**
	 * @param int $argc
	 * @param array $argv
	 * 
	 * @return string
	 */
	public function main($argc, array $argv)
	{
		list($operator, $options) = $this->_parseArguments($argc, $argv, $this->_optionFlags);
		
		echo 'Directory: '.print_r($operator, true)."\n";
		echo 'Options: '.print_r($options, true)."\n";
		
		if ($argc == 1 || $operator === null) {
			$this->_out[] = '  Missing directory operand.';
			$this->_out[] = '  Try \'loc --help\' for more information.';
		} elseif (in_array('--version', $argv)) {
			$this->_out[] = '  loc v'.VERSION;
			$this->_out[] = '  Copyright (C) Netsilik.';
			$this->_out[] = '  License EUPL-1.2: European Union Public Licence, v. 1.2 <https://joinup.ec.europa.eu/community/eupl/og_page/eupl>.';
			$this->_out[] = '  This is free software: you are free to change and redistribute it.';
			$this->_out[] = '  There is NO WARRANTY, to the extent permitted by law.';
		} elseif (in_array('--help', $argv)) {
			$this->_out[] = '  Usage: loc [OPTION]... DIRECTORY...';
			$this->_out[] = '  Count the lines of code in the files in the specified DIRECTORY(ies).';
			$this->_out[] = '';
			$this->_out[] = '  Mandatory arguments to long options are mandatory for short options too.';
			$this->_out[] = '    -f, --file-mask              Process only files that match the file mask';
			$this->_out[] = '        --help                   Display this help and exit';
			$this->_out[] = '    -i, --ignore-dir=DIRECTORY   Ignore all files in the directory DIRECTORY';
			$this->_out[] = '    -r, --recursive              Recursively process filse in sub-directories';
			$this->_out[] = '        --version                Output version information and exit';
		} else {
			$this->_processDir($operator, $options);
		}
		$this->_out[] = '';
		
		echo implode(PHP_EOL, $this->_out);
	}

	/**
	 * @param string $dir
	 * @param array $options
	 * 
	 * @return void
	 */
	private function _processDir($dir, $options)
	{
		if (substr($dir, -1) == DIRECTORY_SEPARATOR) {
			$dir = substr($dir, 0, -1);
		}
			
		list($matchedFiles, $totalFiles, $directories, $totalLines, $emptyLines, $codeLines, $commentLines, $totalComments, $fileTypeCount) = $this->_readFileInDir($dir, $options);
		
		
		if (count($options['fileMasks']) > 0) {
			$maxLength = strlen(number_format(max($totalLines, $emptyLines, $codeLines, $commentLines, $totalComments)));
		
			$this->_out[] = "Parsed {$matchedFiles} ".($matchedFiles == 1 ? 'file' : 'files').($options['all'] ? ' (including hidden files)' : '')." out of a total of {$totalFiles} ".($totalFiles == 1 ? 'file' : 'files')." in {$directories} ".($directories == 1 ? 'directory' : 'directories').', counted:';
			$this->_out[] = '  '.str_pad(number_format($totalLines), $maxLength, ' ', STR_PAD_LEFT).' total lines,';
			$this->_out[] = '  '.str_pad(number_format($emptyLines), $maxLength, ' ', STR_PAD_LEFT).' empty lines,';
			$this->_out[] = '  '.str_pad(number_format($codeLines), $maxLength, ' ', STR_PAD_LEFT).' lines of code,';
			$this->_out[] = '  '.str_pad(number_format($commentLines), $maxLength, ' ', STR_PAD_LEFT).' comment lines,';
			$this->_out[] = '  '.str_pad(number_format($totalComments), $maxLength, ' ', STR_PAD_LEFT).' comments in total.';
			
		} else {
			$this->_out[] = "Found a total of {$totalFiles} ".($totalFiles == 1 ? 'file' : 'files').($options['all'] ? ' (including hidden files)' : '')." in {$directories} ".($directories == 1 ? 'directory' : 'directories').', counting:';
			
			$max = 0;
			foreach ($fileTypeCount as $c) {
				$max = max($max, $c);
			}
			
			$maxLength = strlen(number_format($max));
			$this->_outExt = [];
			foreach ($fileTypeCount as $t => $c) {
				$this->_outExt[] = '  '.str_pad(number_format($c), $maxLength, ' ', STR_PAD_LEFT).' '.$t.' '.($c == 1 ? 'file' : 'files');
			}
			
			$this->_out[] = implode(','.PHP_EOL, $this->_outExt);
		}
	}

	/**
	 * @param string $dir
	 * @param array $options
	 * 
	 * @return array
	 */
	private function _readFileInDir($dir, array $options = [])
	{
		if (false === ($dp = @opendir($dir))) {
			$this->_out = "ERROR: Could not open directory '{$dir}'";
			return false;
		}
		
		$matchedFiles = 0;
		$totalFiles = 0;
		$directories = 1;
		
		$totalLines = 0;
		$emptyLines = 0;
		$codeLines = 0;
		$commentLines = 0;
		$totalComments = 0;
		
		$fileTypeCount = array();
		
		while (false !== ($node = @readdir($dp))) {
			if ($node == '.' || $node == '..' || ($node{0} == '.' && !$options['all'])) {
				continue;
			}
			
			$dirNode = $dir.DIRECTORY_SEPARATOR.$node;
			
			if (is_dir($dirNode)) {
				echo PHP_EOL.'dirNode: '.ltrim($dirNode, '.')." ($node)".PHP_EOL;
			
				
				$dirMatched = false;
				foreach ($options['ignoreDirs'] as $ignoreDir) {
					$trimmed = rtrim($ignoreDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
					if ($ignoreDir{0} == DIRECTORY_SEPARATOR) {
						$regex = '|^'.preg_quote($trimmed, '|').'|';
					} else {
						$regex = '|'.preg_quote(DIRECTORY_SEPARATOR.$trimmed, '|').'|';
					}
					
					
					echo " > preg_match('{$regex}', '".ltrim($dirNode, '.').DIRECTORY_SEPARATOR."') == ".preg_match($regex, ltrim($dirNode, '.').DIRECTORY_SEPARATOR).PHP_EOL;
					
					if (1 === preg_match($regex, ltrim($dirNode, '.').DIRECTORY_SEPARATOR)) {
						$dirMatched = true;
						break;
					}
				}
				
				echo ' -> ';
				
				if ($options['recursive'] && !$dirMatched) {
					
					echo 'processing'.PHP_EOL;
					
					list($mf, $tf, $d, $tl, $el, $loc, $cl, $tc, $ftc) = $this->_readFileInDir($dirNode, $options, false);
					
					$matchedFiles += $mf;
					$totalFiles += $tf;
					$directories += $d;
					
					$totalLines += $tl;
					$emptyLines += $el;
					$codeLines += $loc;
					$commentLines += $cl;
					$totalComments += $tc;
					
					foreach ($ftc as $k => $c) {
						if (isset($fileTypeCount[$k])) {
							$fileTypeCount[$k] += $c;
						} else {
							$fileTypeCount[$k] = $c;
						}
					}
				}
				
				else { echo 'ignoring'.PHP_EOL; } // Debug only
				
			} elseif (is_file($dirNode)) {
				
				$totalFiles++;
				
				if (false !== ($dotPos = strrpos($node, '.')) && $dotPos > 0) {
					$fileExt = substr($node, $dotPos + 1);
				} else {
					$fileExt = $node;
				}

				$fileMatched = false;
				foreach ($options['fileMasks'] as $fileMask) {
					if (fnmatch($fileMask, $node)) {
						$fileMatched = true;
						break;
					}
				}
				
				if ($fileMatched) {
					$matchedFiles++;
					
					list($tl, $el, $loc, $cl, $tc) = parseFile($dirNode);
					$totalLines += $tl;
					$emptyLines += $el;
					$codeLines += $loc;
					$commentLines += $cl;
					$totalComments += $tc;
				} elseif (isset($fileTypeCount[$fileExt])) {
					$fileTypeCount[$fileExt]++;
				} else {
					$fileTypeCount[$fileExt] = 1;
				}
			} else {
				$this->_out = "WARNING: Unknown node type '{$dirNode}'".PHP_EOL;
			}
		}
		closedir($dp);
		
		return array($matchedFiles, $totalFiles, $directories, $totalLines, $emptyLines, $codeLines, $commentLines, $totalComments, $fileTypeCount);
	}

	/**
	 * @param string $filname
	 * 
	 * @return array
	 */
	private function parseFile($filename)
	{
		$lines = explode("\n", file_get_contents($filename)); // ignoring the fact that the file may have windows line endings, because it doesn't make a difference for our purpose.
		
		$totalLines = 0;
		$emptyLines = 0;
		$codeLines = 0;
		$commentLines = 0;
		$totalComments = 0;
		
		
		$s = $d = false; // single and double quoted strings
		$bc = false; // block comments
		
		foreach ($lines as $line) {
			$totalLines++;
			
			$line = trim($line);
			
			if (strlen($line) == 0) {
				$emptyLines++;
				continue;
			}
			
			$comment = 0;
			$code = 0;
			
			$charCount = strlen($line);
			for ($c = 0; $c < $charCount; $c++) {
				
				if ($bc) {
					$comment = 1;
				}
				
				switch( $line{$c} ) {
					case '*':
						if ($bc && $c+1 < $charCount && $line{$c+1} == '/') {
							$bc = false;
							$c++;
							break;
						}
					case '/':				
						if (!$s && !$d && $c+1 < $charCount) {
							if ($line{$c+1} == '*') {
								$bc = true;
								$comment = 1;
								$c++;
							} elseif (!$bc && $line{$c+1} == '/') {
								$comment = 1;
								break(2);
							}
						}
					case '\\':
						if (!$bc) { $c++; }
					case '\'':
						if (!$bc && !$d) { $s = !$s; }
					case '"':
						if (!$bc && !$s) { $d = !$d; }
					default:
						if (!$bc) { $code = 1; }
				}
							
				if ($comment == 1 && $code == 1) {
					$break; // line is both a comment and code; nothing more to count
				}
			}
			
		//	echo 'line '.str_pad($totalLines, 2, ' ', STR_PAD_LEFT)." [{$code}, {$comment}, ".($comment > $code ? 1 : 0).']='.($bc?1:0).': {$line}'.PHP_EOL;
			
			$codeLines += $code;
			$commentLines += ($comment > $code) ? 1 : 0;
			$totalComments += $comment;
		}
		
		return array($totalLines, $emptyLines, $codeLines, $commentLines, $totalComments);
	}
}