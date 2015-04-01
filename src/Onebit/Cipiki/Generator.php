<?php 

namespace Onebit\Cipiki;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \FilesystemIterator;
use \Twig_Loader_Filesystem;
use \Twig_Environment;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo\SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;
use \cebe\markdown\GithubMarkdown;

error_reporting(E_ALL);

class Generator 
{
    private $config;
    private $source_dir;
    private $target_dir;
    private $output;

    public function __construct($config, OutputInterface $output) 
    {
        $this->output = $output;
        $this->config = $config;
        $this->source_dir = $config['source_dir'];
        $this->target_dir = $config['target_dir'];
    }

    public function generate()
    {
        $this->clearTargetDir();
        $this->copyAssets();
        $this->convertAll();
    }

    private function writeLn($line)
    {
        $this->output->writeLn($line);
    }

    private function clearTargetDir()
    {
        $this->writeLn("Clearing target dir");
        $di = new RecursiveDirectoryIterator(
            $this->target_dir, 
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);

        foreach (new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST) as $value ) {
                $value->isFile() ? unlink( $value ) : rmdir( $value );
        }
    }

    private function copyAssets()
    {
        $this->writeLn("Copying assets");
        $di = new RecursiveDirectoryIterator(
            $this->source_dir . '/assets', 
            RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::CURRENT_AS_SELF
            );

        foreach (new RecursiveIteratorIterator($di) as $filename => $file) {
            $source_file = $file->getPathName();
            $target_file = $this->target_dir . '/' . $file->getSubPath();
            $target_file .= substr($target_file, -1) === '/' ? '' : '/';
            $target_file .= $file->getBasename();

            $this->prepareDir($target_file);
            copy($source_file, $target_file);
            $this->writeLn("<info>{$source_file}</info> -> <info>{$target_file}</info>");
        }
    }

    private function convertAll()
    {
        $this->writeLn("Generating site");
        $di = new RecursiveDirectoryIterator(
            $this->source_dir . '/contents', 
            RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::CURRENT_AS_SELF
            );

        foreach (new RecursiveIteratorIterator($di) as $filename => $file) {
            $ext = $file->getExtension();
            if (in_array($ext, ['md', 'markdown'])) {
                $source_file = $file->getPathName();
                $target_file = $this->target_dir . '/' . $file->getSubPath();
                $target_file .= substr($target_file, -1) === '/' ? '' : '/';
                $target_file .= $file->getBasename('.'.$ext) . '.html';

                $this->convert($source_file, $target_file);
                $this->writeLn("<info>{$source_file}</info> -> <info>{$target_file}</info>");
            }
        }
    }

    private function convert($source_file, $target_file)
    {
        $this->prepareDir($target_file);
        $parser = new GithubMarkdown();
        $content = $parser->parse(file_get_contents($source_file));
        $html = $this->render('main.html.twig', ['content' => $content]);
        file_put_contents($target_file, $html);
    }

    private function prepareDir($file_name)
    {
        if (!file_exists(dirname($file_name)))
            mkdir(dirname($file_name), 0755, true);
    }

    private function render($template, $params)
    {
        $loader = new Twig_Loader_Filesystem($this->source_dir . '/layouts');
        $twig = new Twig_Environment($loader);

        $params['site'] = $this->config['site'];
        return $twig->render($template, $params);
    }
}