<?php

/**
 * Test: Kdyby\Fs\Dir.
 *
 * @testCase KdybyTests\Fs\DirTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Fs
 */

namespace KdybyTests\Fs;

use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class DirTest extends Tester\TestCase
{

	public function testFunctionality()
	{
		Assert::false(file_exists(TEMP_DIR . '/test'));
		$dir = new Kdyby\Fs\Dir(TEMP_DIR . '/test');
		Assert::false(file_exists(TEMP_DIR . '/test'));
		$dir->ensureWritable();
		Assert::true(file_exists(TEMP_DIR . '/test'));
		Assert::true(is_writable(TEMP_DIR . '/test'));
		$dir->write('the-cake.txt', "is a lie");
		Assert::same("is a lie", file_get_contents(TEMP_DIR . '/test/the-cake.txt'));
	}

}

\run(new DirTest());
