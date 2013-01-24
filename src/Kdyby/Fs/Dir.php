<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Fs;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Dir extends Nette\Object
{

	/**
	 * @var string
	 */
	private $dir;

	/**
	 * @var int
	 */
	private $chmod;



	/**
	 * @param string $dir
	 * @param int $chmod
	 */
	public function __construct($dir, $chmod = 0777)
	{
		$this->dir = rtrim($dir, DIRECTORY_SEPARATOR);
		$this->chmod = $chmod;
	}



	/**
	 * @throws IOException
	 */
	public function ensureWritable()
	{
		if (!is_dir($this->dir)) {
			$old = umask(0);
			@mkdir($this->dir, $this->chmod, TRUE);
			umask($old);
		}

		if (!is_writable($this->dir) && !@chmod($this->dir, $this->chmod)) {
			throw new IOException("Please make directory '{$this->dir}' writable, it cannot be made automatically");
		}
	}



	/**
	 * @param string $dir
	 * @return Dir
	 */
	public function createSubDir($dir)
	{
		$this->ensureWritable();
		$dir = new Dir($this->dir . DIRECTORY_SEPARATOR . $dir);
		$dir->ensureWritable();
		return $dir;
	}



	/**
	 * @param string $file
	 * @return string
	 */
	public function read($file)
	{
		return file_get_contents($this->dir . DIRECTORY_SEPARATOR . $file);
	}



	/**
	 * @param string $file
	 * @param mixed $contents
	 * @throws IOException
	 */
	public function write($file, $contents)
	{
		$this->ensureWritable();
		if (!@file_put_contents($path = $this->dir . DIRECTORY_SEPARATOR . $file, $contents)) {
			throw new IOException("Cannot write to file '$path'");
		}
	}



	/**
	 * @param \Nette\Http\FileUpload $file
	 * @param string $filename
	 * @throws IOException
	 * @return string
	 */
	public function upload(Nette\Http\FileUpload $file, $filename = NULL)
	{
		if (!$file->isOk()) {
			throw new IOException("Cannot save corrupted file.");
		}

		do {
			$name = Nette\Utils\Strings::random(10) . '.' . ($filename ?: $file->getSanitizedName());
		} while (file_exists($path = $this->dir . DIRECTORY_SEPARATOR . $name));

		$file->move($path);

		return basename($path);
	}



	/**
	 * @param string $mask
	 * @return \Nette\Utils\Finder|\SplFileInfo[]
	 */
	public function find($mask)
	{
		return Nette\Utils\Finder::find(func_get_args())->from($this->dir);
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->dir;
	}

}
