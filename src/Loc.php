<?php
namespace Netsilik\Util;

use Netsilik\Lib\Cli;

class Loc extends Cli
{
	/**
	 * string VERSION
	 */
	const VERSION = '1.1.0';
	
	/**
	 * @var array $_optionFlags
	 */
	protected $_optionFlags = [
		'name'  => ['all', 'fileMasks', 'help', 'ignoreDirs', 'recursive', 'version'],
		'short' => ['a', 'f:', null, 'i:', 'r', null],
		'long'  => ['all', 'file-mask:', 'help', 'ignore-dir:', 'recursive', 'version'],
	];
	
	private $_exitCode = 0;
	
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
	 * @return int
	 */
	public function main($argc, array $argv)
	{
		list($operators, $options) = $this->_parseArguments($argc, $argv, $this->_optionFlags);
		
		if (in_array('--help', $argv)) {
			$this->_out[] = '  Usage: loc [OPTION]... DIRECTORY...';
			$this->_out[] = '  Count the lines of code in the files in the specified DIRECTORY(ies).';
			$this->_out[] = '';
			$this->_out[] = '  Mandatory arguments for long options are mandatory for short options too.';
			$this->_out[] = '    -a, --all                    Include hidden files and directories';
			$this->_out[] = '    -f, --file-mask=MASK         Process only files that match the file mask';
			$this->_out[] = '        --help                   Display this help and exit';
			$this->_out[] = '    -i, --ignore-dir=DIRECTORY   Ignore all files in the directory DIRECTORY';
			$this->_out[] = '    -r, --recursive              Recursively process filse in sub-directories';
			$this->_out[] = '        --version                Output version information and exit';
		} elseif (in_array('--version', $argv)) {
			$this->_out[] = '  loc v'.self::VERSION;
			$this->_out[] = '  Copyright (C) Netsilik.';
			$this->_out[] = '  License EUPL-1.2: European Union Public Licence, v. 1.2 <https://joinup.ec.europa.eu/community/eupl/og_page/eupl>.';
			$this->_out[] = '  This is free software: you are free to change and redistribute it.';
			$this->_out[] = '  There is NO WARRANTY, to the extent permitted by law.';
		} elseif ($argc <= 1 || count($operators) === 0) {
			$this->_out[] = '  Missing directory operand.';
			$this->_out[] = '  Try \'loc --help\' for more information.';
			$this->_exitCode = 1;
		} else {
			foreach ($operators as $index => $operator) {
				$operators[$index] = rtrim($operator, DIRECTORY_SEPARATOR);
			}
			$this->_processDirectories($operators, $options);
		}
		$this->_out[] = '';
		
		return implode(PHP_EOL, $this->_out);
	}
	
	/**
	 * @return int
	 */
	public function getExitCode()
	{
		return $this->_exitCode;
	}

	/**
	 * @param string $dir
	 * @param array $options
	 * 
	 * @return void
	 */
	private function _processDirectories($directories, $options)
	{
		$counted = [
			'matchedFiles' => 0,
			'totalFiles' => 0,
			'hiddenFiles' => 0,
			'directories' => 1,
			
			'totalLines' => 0,
			'emptyLines' => 0,
			'codeLines' => 0,
			'commentLines' => 0,
			'totalComments' => 0,
		
			'fileTypeCount' => [],
		];
		
		$counted = $this->_readFileInDir($directories, $options, $counted);
		
		if (count($options['fileMasks']) > 0) {
			$maxLength = strlen(number_format(max($counted['totalLines'], $counted['emptyLines'], $counted['codeLines'], $counted['commentLines'], $counted['totalComments'])));
			
			$this->_out[] = "Parsed {$counted['matchedFiles']} (" . implode(', ', $options['fileMasks']) .') '.($counted['matchedFiles'] == 1 ? 'file' : 'files').($counted['hiddenFiles'] > 0 ? ' (including '.$counted['hiddenFiles'].' hidden '.($counted['hiddenFiles'] == 1 ? 'file' : 'files').')' : '')." out of a total of {$counted['totalFiles']} ".($counted['totalFiles'] == 1 ? 'file' : 'files').", in {$counted['directories']} ".($counted['directories'] == 1 ? 'directory' : 'directories').' and counted:';
			$this->_out[] = '  '.str_pad(number_format($counted['totalLines']), $maxLength, ' ', STR_PAD_LEFT).' total lines,';
			$this->_out[] = '  '.str_pad(number_format($counted['emptyLines']), $maxLength, ' ', STR_PAD_LEFT).' empty lines,';
			$this->_out[] = '  '.str_pad(number_format($counted['codeLines']), $maxLength, ' ', STR_PAD_LEFT).' lines of code,';
			$this->_out[] = '  '.str_pad(number_format($counted['commentLines']), $maxLength, ' ', STR_PAD_LEFT).' comment lines,';
			$this->_out[] = '  '.str_pad(number_format($counted['totalComments']), $maxLength, ' ', STR_PAD_LEFT).' comments in total.';
			
		} else {
			$this->_out[] = "Found a total of {$counted['totalFiles']} ".($counted['totalFiles'] == 1 ? 'file' : 'files').($options['all'] ? ' (including hidden files)' : '')." in {$counted['directories']} ".($counted['directories'] == 1 ? 'directory' : 'directories').', counting:';
			
			$max = 0;
			foreach ($counted['fileTypeCount'] as $c) {
				$max = max($max, $c);
			}
			
			$maxLength = strlen(number_format($max));
			$out = [];
			foreach ($counted['fileTypeCount'] as $t => $c) {
				$out[] = '  '.str_pad(number_format($c), $maxLength, ' ', STR_PAD_LEFT).' '.$t.' '.($c == 1 ? 'file' : 'files');
			}
			
			$this->_out[] = implode(','.PHP_EOL, $out);
		}
	}

	/**
	 * @param array $directories
	 * @param array $options
	 * 
	 * @return array
	 */
	private function _readFileInDir(array $directories, array $options, $counted)
	{
		
		foreach ($directories as $directory) {
			
			if (false === ($dp = @opendir($directory))) {
				echo "ERROR: Could not open directory '{$directory}'".PHP_EOL;
				exit(1);
			}
			
			while (false !== ($node = @readdir($dp))) {
				if ($node == '.' || $node == '..' || ($node{0} == '.' && !$options['all'])) {
					continue;
				}
				
				
				$pathNode = $directory.DIRECTORY_SEPARATOR.$node;
				if (is_dir($pathNode)) {
					if ($options['recursive']) {
						$counted = $this->_handleDir($options, $pathNode, $counted);
					}
				} elseif (is_file($pathNode)) {
					$counted = $this->_handleFile($options, $pathNode, $counted);
				} else {
					$this->_out[] = "WARNING: Unknown node type '{$pathNode}'".PHP_EOL;
				}
			}
			closedir($dp);
		}
		
		
		return $counted;
	}

	private function _handleDir(array $options, $directory, $counted)
	{
		
		$processDir = true;
		foreach ($options['ignoreDirs'] as $ignoreDir) {
			$trimmed = rtrim($ignoreDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
			if ($ignoreDir{0} == DIRECTORY_SEPARATOR) {
				$regex = '/^'.preg_quote($trimmed).'/';
			} else {
				$regex = '/'.preg_quote(DIRECTORY_SEPARATOR.$trimmed).'/';
			}
			
			if (1 === preg_match($regex, ltrim($directory, '.').DIRECTORY_SEPARATOR)) {
				$processDir = false;
				break;
			}
		}
		
		if ($processDir) {
			$counted = $this->_readFileInDir([$directory], $options, $counted);
			$counted['directories']++;
		}
		
		return $counted;
	}
	
	private function _handleFile($options, $file, $counted)
	{
		$counted['totalFiles']++;
		
		$basename = basename($file);
		if (false !== ($dotPos = strrpos($basename, '.')) && $dotPos > 0) {
			$fileExt = substr($basename, $dotPos + 1);
		} else {
			$fileExt = $basename;
		}
		
		$fileMatched = false;
		foreach ($options['fileMasks'] as $fileMask) {
			if (fnmatch($fileMask, $basename)) {
				$fileMatched = true;
				break;
			}
		}
		
		if ($fileMatched) {
			$counted['matchedFiles']++;
			if ($basename{0} == '.') {
				$counted['hiddenFiles']++;
			}
			
			list($tl, $el, $loc, $cl, $tc) = $this->_parseFile($file);
			$counted['totalLines'] += $tl;
			$counted['emptyLines'] += $el;
			$counted['codeLines'] += $loc;
			$counted['commentLines'] += $cl;
			$counted['totalComments'] += $tc;
		} else {
			if (isset($fileTypeCount[$fileExt])) {
				$counted['fileTypeCount'][$fileExt]++;
			} else {
				$counted['fileTypeCount'][$fileExt] = 1;
			}
		}
		return $counted;
	}
	
	/**
	 * @param string $filname
	 * 
	 * @return array
	 */
	private function _parseFile($filename)
	{
		$lines = explode("\n", file_get_contents($filename));
		
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
			
			$code = 0;
			$comment = 0;
			
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
						if (!$bc) {
							$c++;
							break;
						}
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
			
			$codeLines += $code;
			$commentLines += ($comment > $code) ? 1 : 0;
			$totalComments += $comment;
		}
		
		return array($totalLines, $emptyLines, $codeLines, $commentLines, $totalComments);
	}
}
