<?php
	define('DIR', DIRECTORY_SEPARATOR);
	define('MAIN_DIR', dirname(__FILE__, 2));
	error_reporting(E_ALL | E_STRICT);
	ini_set("display_errors", true);

	function autoload($class): void {
		$filepath = str_replace(
			array("_", "\\", "\0"),
			array(DIR, DIR, ""),
			ltrim($class, "\\")) . '.php';

		$namespace = 'cebe' . DIR . 'markdown';
		$filepath = str_replace($namespace, '', $filepath);
		$fullpath = MAIN_DIR . DIR . 'src' . DIR . $filepath;

		if (is_file($fullpath)) {
			require $fullpath;
			return;
		}
	}

	spl_autoload_register("autoload");
	ob_start();
	header("Content-Type: text/html; charset=UTF-8");

	function scan_data(): array {
		$dirs = new DirectoryIterator(dirname(__FILE__) . DIR . 'data');
		$data = array();

		foreach ($dirs as $dir) {
			if (!$dir->isDot() && $dir->isDir()) {
				$parser = $dir->getFilename();
				$tests = new DirectoryIterator($dir->getPathname());
				foreach ($tests as $test) {
					if ($test->isFile()) {
						$filepath = $test->getPathname();
						$extension = $test->getExtension();
						$basename = $test->getBasename('.' . $extension);

						switch ($extension) {
							case 'md':
								$contents = @file_get_contents($filepath);
								if ($contents !== false) {
									$data[$parser][$basename]['source'] = $contents;
								}
								break;

							case 'html':
								$contents = @file_get_contents($filepath);
								if ($contents !== false) {
									$data[$parser][$basename]['expect'] = $contents;
								}
								break;
						}
					}
				}
			}
		}

		return $data;
	}

	function run_tests($data): array {
		foreach ($data as $parser => $tests) {
			$instance = new ('\cebe\markdown\\' . $parser)();
			$instance->html5 = false;

			foreach ($tests as $number => $values) {
				if (!array_key_exists('source', $values) ||
					!array_key_exists('expect', $values)) {
					continue;
				}

				$source = $values['source'];
				$expect = $values['expect'];
				$result = $instance->parse($source);
				$passed = strcmp($expect, $result) === 0;

				$data[$parser][$number]['result'] = $result;
				$data[$parser][$number]['passed'] = $passed;
			}
		}

		return $data;
	}

	function display_results($data): void {
		foreach ($data as $parser => $tests) {
			echo '<table>' . "\n";
			echo '<thead>' . "\n";
			echo '<tr>' . "\n";
			echo '<th colspan="4">' . $parser . '</th>';
			echo '</tr>' . "\n";
			echo '<tr>' . "\n";
			echo '<th>#</th>' . "\n";
			echo '<th>Source</th>' . "\n";
			echo '<th>Expect</th>' . "\n";
			echo '<th>Result</th>' . "\n";
			echo '</tr>' . "\n";
			echo '</thead>' . "\n";
			echo '<tbody>' . "\n";

			foreach ($tests as $number => $values) {
				if (!array_key_exists('source', $values) ||
					!array_key_exists('expect', $values) ||
					!array_key_exists('result', $values) ||
					!array_key_exists('passed', $values)) {
					continue;
				}

				$source = $values['source'];
				$expect = $values['expect'];
				$result = $values['result'];
				$passed = $values['passed'];

				$class = $passed ? 'pass' : 'fail';
				$number = htmlspecialchars($number, ENT_HTML5, "UTF-8", true);
				$source = htmlspecialchars($source, ENT_HTML5, "UTF-8", true);
				$expect = htmlspecialchars($expect, ENT_HTML5, "UTF-8", true);
				$result = htmlspecialchars($result, ENT_HTML5, "UTF-8", true);

				echo '<tr class="' . $class . '">' . "\n";
				echo '<td>' . "\n" . $number . '</td>' . "\n";
				echo '<td><pre>' . "\n" . $source . '</pre></td>' . "\n";
				echo '<td><pre>' . "\n" . $expect . '</pre></td>' . "\n";
				echo '<td><pre>' . "\n" . $result . '</pre></td>' . "\n";
				echo '</tr>' . "\n";
			}

			echo '</tbody>' . "\n";
			echo '</table>' . "\n";
		}
	}

	#---------------------------------------------
	# Output Starts
	#---------------------------------------------
?>
<!DOCTYPE html>
<html>
	<head>
		<style type="text/css">
			body {
				font-family: sans-serif;
				tab-size: 4;
			}
			table {
			    width: 100%;
			    border-collapse: collapse;
			}
			th {
				background-color: #dfdfdf;
				vertical-align: middle;
			    padding: 0.5rem;
			    border: 1px solid #000000;
			}
			td {
				width: calc(33.3% - 4ch);
				font-family: monospace;
				vertical-align: top;
				padding: 0.5rem;
			    border: 1px solid #000000;
			}
			td:first-child {
				width: 4ch;
			}
			tr {
				background-color: #efefef;
			}
			tr.pass {
				background-color: #ebfae4;
			}
			tr.fail {
				background-color: #faebe4;
			}
			pre {
				white-space: break-spaces;
			}
		</style>
	</head>
	<body><?php

	display_results(run_tests(scan_data()));

	?></body>
</html>
