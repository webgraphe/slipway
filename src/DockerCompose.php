<?php

namespace Webgraphe\Slipway;

use Webgraphe\Slipway\Exceptions\UsageException;

readonly class DockerCompose
{
    public function __construct(public string $name, public bool $force) {}

    /**
     * @throws SlipwayException
     */
    public static function fromGlobals(): self
    {
        $options = [];
        $flags = [];
        $arguments = [];
        foreach (array_slice($_SERVER['argv'] ?? [], 1) as $arg) {
            if (str_starts_with($arg, '--')) {
                if (!preg_match('/^--([^=]+)(=(.*))?$/', $arg, $matches)) {
                    throw new UsageException('Malformed option');
                }

                if (isset($matches[3])) {
                    $options[$matches[1]][] = $arg;
                } else {
                    $flags[$matches[1]] = $matches[1];
                }
            } else {
                $arguments[] = $arg;
            }
        }

        if (!isset($arguments[0])) {
            throw new UsageException("Missing project name");
        }

        return new self($arguments[0], (bool)($flags['force'] ?? null));
    }

    /**
     * @throws SlipwayException
     */
    private static function assertExists(string $source): void
    {
        if (!file_exists($source)) {
            throw new SlipwayException("$source does not exist");
        }

        if (!is_readable($source)) {
            throw new SlipwayException("$source is not readable");
        }
    }

    /**
     * @throws SlipwayException
     */
    public function export(string $destinationDirectory): void
    {
        $flags = $this->force ? 'forced' : 'use --force to overwrite';
        echo "Exporting project $this->name Docker Compose file to $destinationDirectory ($flags)\n";
        $this->exportDockerComposeFile($destinationDirectory);
        $this->deepCopy(__DIR__ . '/../resources/.docker', "$destinationDirectory/.docker");
    }

    /**
     * @throws SlipwayException
     */
    private function exportDockerComposeFile(string $destinationDirectory): void
    {
        $destination = "$destinationDirectory/docker-compose.yml";
        $verb = 'Writing';
        if (file_exists($destination)) {
            if (!$this->force) {
                echo "$destination already exists" . PHP_EOL;

                return;
            }

            $verb = 'Replacing';
        }

        $dockerCompose = file_get_contents(__DIR__ . '/../resources/docker-compose.yml');
        $dockerCompose = str_replace('%%NAME%%', $this->name, $dockerCompose);
        echo "$verb $destination" . PHP_EOL;
        if (!file_put_contents($destination, $dockerCompose)) {
            throw new SlipwayException("Could not write to $destination");
        }
    }

    /**
     * @throws SlipwayException
     */
    private function deepCopy(string $source, string $destination): void
    {
        self::assertExists($source);
        $source = realpath($source);
        if (is_dir($source)) {
            if (file_exists($destination)) {
                if (!is_dir($destination)) {
                    throw new SlipwayException("Excepted $destination to be a directory");
                }
                echo "Directory $destination already exists" . PHP_EOL;
            } else {
                echo "Creating directory $destination" . PHP_EOL;
                if (!mkdir($destination)) {
                    throw new SlipwayException("Failed to create directory $destination");
                }
            }

            $files = [];
            $dir = opendir($source);
            while ($file = readdir($dir)) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $files[] = $file;
            }
            closedir($dir);
            foreach ($files as $file) {
                $this->deepCopy("$source/$file", "$destination/$file");
            }
        } elseif (is_file($source)) {
            if ($this->force || !file_exists($destination)) {
                echo "Copying $source to $destination" . PHP_EOL;
                if (!copy($source, $destination)) {
                    throw new SlipwayException("Failed to copy $source to $destination");
                }
            } else {
                echo "$destination already exists" . PHP_EOL;
            }
        }
    }
}
