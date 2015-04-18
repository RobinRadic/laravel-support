<?php
/**
 * Part of the Laradic packages.
 * MIT License and copyright information bundled with this package in the LICENSE file.
 *
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
namespace Laradic\Support;

use Laradic\Support\Filesystem;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Class TemplateParser
 *
 * @package     Laradic\Support
 */
class TemplateParser
{

    /**
     * @var string
     */
    protected $openDelimiter = '{';

    /**
     * @var string
     */
    protected $closeDelimiter = '}';

    /**
     * @var array
     */
    protected $values = array();

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var string
     */
    protected $sourceDir;

    /**
     * Instanciates the class
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param null                              $sourceDir
     */
    public function __construct(Filesystem $files, $sourceDir)
    {
        $this->files     = $files;
        $this->sourceDir = $sourceDir;
        $this->values    = [
            'date.year'  => date("Y"),
            'date.month' => date("m"),
            'date.day'   => date("d"),
        ];
    }

    /**
     * Copies a parsed stub file to the directory
     *
     * @param string|string[] $file   The source file path(s)
     * @param null|string     $to     The absolute or relative path to write the file to
     * @param array           $values The template variables
     */
    public function copy($file, $to = null, array $values = [])
    {
        if ( is_array($file) )
        {
            foreach ($file as $_file)
            {
                $this->copy($_file, $to, $values);
            }
        }
        else
        {
            $srcFile = $file;
            if ( String::startsWith($srcFile, '.') === true )
            {
                $srcFile = '_' . $srcFile;
            }
            $content = $this->files->get(Path::join($this->sourceDir, $srcFile));


            $to = is_null($to) ? $file : $to;
            $to = Path::isAbsolute($to) ? $to : Path::join(getcwd(), $to);

            $this->files->put(Path::join($to, $srcFile), $this->parse($content, $values));
            #VarDumper::dump(['copyto' => Path::join($to, $srcFile)]);
        }
    }

    public function copyDirectory($to, $from = null, array $values = [])
    {
        $from = is_null($from) ? $this->sourceDir : $from;
        $files = $this->files->rglob(Path::join($from, '*'));
        $dirs = $this->files->rglob(Path::join($from, '*'), GLOB_ONLYDIR);
        $this->files->makeDirectory(realpath($to), 0755, true);
       # VarDumper::dump(compact('to', 'from', 'dirs', 'files'));
        foreach($dirs as $dir)
        {
            $toPath = Path::join($to, Str::remove(realpath($dir), realpath($from) . '/'));
            $this->files->makeDirectory($toPath, 0755, true);
            #VarDumper::dump("Created dir $toPath [$dir]" . Str::remove(realpath($dir), realpath($from) . '/'));
        }

        foreach($files as $file)
        {
            $filePath = realpath($file);
            $fromPath = Str::remove($file, $from . '/');
            #$toPath = Path::join($to, Str::remove($filePath, realpath($from) . '/'));
            if($this->files->isDirectory($file))
            {
                continue;
            }
            else
            {
                $this->copy($fromPath, $to, $values);
                #VarDumper::dump("Copied $fromPath to $to");
            }
        }
    }

    /**
     * Parses a string and replaces the keys with values
     *
     * @param       $str
     * @param array $values
     * @return mixed
     */
    public function parse($str, array $values = [])
    {
        $keys = array();

        $values = array_merge($this->values, $values);

        foreach ($values as $key => $value)
        {
            $keys[] = $this->openDelimiter . $key . $this->closeDelimiter;
        }

        return str_replace($keys, $values, $str);
    }

    /**
     * Merges an array with values
     *
     * @param array $values
     * @param bool  $merge
     */
    public function setVar(array $values, $merge = true)
    {
        if ( ! $merge || empty($this->values) )
        {
            $this->values = $values;
        }
        else
        {
            $this->values = array_merge($this->values, $values);
        }
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Get the value of openDelimiter
     *
     * @return string
     */
    public function getOpenDelimiter()
    {
        return $this->openDelimiter;
    }

    /**
     * Sets the value of openDelimiter
     *
     * @param string $openDelimiter
     */
    public function setOpenDelimiter($openDelimiter)
    {
        $this->openDelimiter = $openDelimiter;

        return $this;
    }

    /**
     * Get the value of closeDelimiter
     *
     * @return string
     */
    public function getCloseDelimiter()
    {
        return $this->closeDelimiter;
    }

    /**
     * Sets the value of closeDelimiter
     *
     * @param string $closeDelimiter
     */
    public function setCloseDelimiter($closeDelimiter)
    {
        $this->closeDelimiter = $closeDelimiter;

        return $this;
    }

    /**
     * Get the value of files
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Sets the value of files
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     */
    public function setFiles($files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * Get the value of sourceDir
     *
     * @return string
     */
    public function getSourceDir()
    {
        return $this->sourceDir;
    }

    /**
     * Sets the value of sourceDir
     *
     * @param string $sourceDir
     */
    public function setSourceDir($sourceDir)
    {
        $this->sourceDir = $sourceDir;

        return $this;
    }
}
