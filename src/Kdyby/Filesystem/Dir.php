<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Filesystem;

use Kdyby;
use Nette;
use Symfony\Component\Filesystem\Exception\IOException as SfException;
use Symfony\Component\Filesystem\Filesystem;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method \Kdyby\Filesystem\Dir copy($originFile, $targetFile, $override = FALSE)
 * @method \Kdyby\Filesystem\Dir mkdir($dir, $mode = 0777)
 * @method \Kdyby\Filesystem\Dir touch($file, $time = NULL, $atime = NULL)
 * @method \Kdyby\Filesystem\Dir remove($file)
 * @method \Kdyby\Filesystem\Dir chmod($file, $mode, $umask = 0000, $recursive = FALSE)
 * @method \Kdyby\Filesystem\Dir chown($file, $user, $recursive = FALSE)
 * @method \Kdyby\Filesystem\Dir chgrp($file, $group, $recursive = FALSE)
 * @method \Kdyby\Filesystem\Dir rename($origin, $target)
 * @method \Kdyby\Filesystem\Dir symlink($originDir, $targetDir, $copyOnWindows = FALSE)
 * @method \Kdyby\Filesystem\Dir mirror($originDir, $targetDir, \Traversable $iterator = NULL, $options = array())
 */
class Dir extends Nette\Object implements \IteratorAggregate
{

	/**
	 * @var string
	 */
	private $dir;

	/**
	 * @var \Symfony\Component\Filesystem\Filesystem
	 */
	private $io;

	/**
	 * @var array
	 */
	private static $fileArg = array(
		'copy' => 1,
		'mkdir' => 0,
		'touch' => 0,
		'remove' => 0,
		'chmod' => 0,
		'chown' => 0,
		'chgrp' => 0,
		'rename' => 1,
		'symlink' => 1,
		'mirror' => 1,
	);



	/**
	 * @param string $dir
	 * @param int $mode
	 * @param \Symfony\Component\Filesystem\Filesystem $io
	 */
	public function __construct($dir, $mode = 0777, Filesystem $io = NULL)
	{
		$this->dir = rtrim($dir, DIRECTORY_SEPARATOR);
		$this->io = $io ?: new Filesystem;
		$this->ensureWritable($mode);
	}



	/**
	 * @param int $mode
	 * @throws IOException
	 * @return Dir
	 */
	protected function ensureWritable($mode = 0777)
	{
		try {
			$this->io->mkdir($this->dir, $mode);
			$this->io->chmod($this->dir, $mode, 0000, TRUE);

		} catch (SfException $e) {
			throw new IOException("Please make directory '{$this->dir}' writable, it cannot be done automatically", 0, $e);
		}

		return $this;
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
		try {
			$this->io->mkdir(dirname($path = $this->dir . DIRECTORY_SEPARATOR . $file));

		} catch (SfException $e) {
			throw new IOException($e->getMessage(), 0, $e);
		}

		if (!@file_put_contents($path, $contents)) {
			throw new IOException("Cannot write to file '$path'" . (($err = error_get_last()) ? ': ' . $err['message'] : NULL));
		}
	}



	/**
	 * @param \Nette\Http\FileUpload $file
	 * @param string $filename
	 * @throws IOException
	 * @return string
	 */
	public function writeUploaded(Nette\Http\FileUpload $file, $filename = NULL)
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
	 * @throws IOException
	 */
	public function purge()
	{
		foreach ($this->getIterator(TRUE)->childFirst() as $file) {
			/** @var \SplFileInfo $file */
			if ($file->isDir()) {
				if (!@rmdir($file->getPathname())) {
					throw new IOException("Cannot delete directory {$file->getPathname()}");
				}

			} elseif (!@unlink($file->getPathname())) {
				throw new IOException("Cannot delete file {$file->getPathname()}");
			}
		}
	}



	/**
	 * @param string $mask
	 * @param bool $recursive
	 * @return \Nette\Utils\Finder|\SplFileInfo[]
	 */
	public function find($mask, $recursive = FALSE)
	{
		$masks = is_array($mask) ? $mask : func_get_args();
		if (is_bool(end($masks))) {
			$recursive = array_pop($masks);
		}

		return Nette\Utils\Finder::find($masks)->{$recursive ? 'from' : 'in'}($this->dir);
	}



	/**
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 * @throws IOException
	 */
	public function __call($name, $args)
	{
		if (isset(self::$fileArg[$name])) {
			$dir = $this->dir;
			$args[self::$fileArg[$name]] = is_array($args[self::$fileArg[$name]])
				? array_map(function ($arg) use ($dir) { return $dir . DIRECTORY_SEPARATOR . $arg; }, $args[self::$fileArg[$name]])
				: $this->dir . DIRECTORY_SEPARATOR . $args[self::$fileArg[$name]];

			try {
				call_user_func_array(array($this->io, $name), $args);
				return $this;

			} catch (SfException $e) {
				throw new IOException($e->getMessage(), 0, $e);
			}
		}

		return parent::__call($name, $args);
	}



	/**
	 * @param bool $recursive
	 * @return \Nette\Utils\Finder|\SplFileInfo[]|\Traversable
	 */
	public function getIterator($recursive = FALSE)
	{
		return $this->find('*', $recursive);
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->dir;
	}

}
