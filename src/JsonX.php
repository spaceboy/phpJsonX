<?php declare(strict_types = 1);

namespace Spaceboy\JsonX;

use Spaceboy\JsonX\Exceptions\JsonXException;
use function json_decode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_file;
use function is_readable;
use function is_writable;
use function realpath;

/**
 * Class JsonX converts JSONX to JSON format.
 *
 * @package Spaceboy\JsonX
 * @author  spaceboy
 */
final class JsonX {
    /**
     * JSON default file extenstion.
     *
     * @var string
     */
    public const FILE_EXT_JSON = 'json';

    /**
     * Exception code error types.
     *
     * @var int
     */
    public const
        ERROR_FILE = 1,
        ERROR_DECODE = 2;

    /**
     * String containing source JSONX.
     *
     * @var string
     */
    private string $source = '';

    /**
     * Source file name.
     *
     * @var string|null
     */
    private ?string $sourceFileName = null;

    /**
     * Flag "overwrite" for {@see JsonX::writeJson()} method.
     *
     * @var bool
     */
    private bool $overwrite = false;

    /**
     * Flag "associative" for PHP json_decode.
     *
     * @var bool
     */
    private bool $associative = true;

    /**
     * @var int<1, max>
     */
    private int $depth = 512;

    /**
     * @var int
     */
    private int $flags = 0;

    /**
     * Loads JSONX string.
     *
     * @param string $source
     * @return self
     */
    public function fromString(
        string $source
    ): self
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Loads JSONX source string from file.
     *
     * @param string $fileName
     *
     * @return self
     * @throws JsonXException
     */
    public function fromFile(
        string $fileName
    ): self
    {
        if (
            !file_exists($fileName)
            || !is_file($fileName)
            || !is_readable($fileName)
        ) {
            throw new JsonXException("File not exists, is not file or is not readable ({$fileName}).", self::ERROR_FILE);
        }
        $this->source = (file_get_contents($fileName) ?: '');
        $this->sourceFileName = (realpath($fileName) ?: null);
        return $this;
    }

    /**
     * Returns given JSONX source converted to JSON string;
     * when called without argument, returns actual (loaded by fromFile or fromString methods) JSONX converted to JSON.
     *
     * @return string
     * @throws JsonXException
     */
    public function toJson(): string
    {
        $this->removeComments();
        $this->removeTrailingCommas();
        return $this->source = trim($this->source);
    }

    /**
     * Returns decoded JSON from given JSON/JSONX string;
     * when called without argument, returns decoded actual (loaded by fromFile or fromString methods) JSON/JSONX string.
     *
     * @param string $source
     *
     * @return mixed
     * @throws JsonXException
     */
    public function decode(
        string $source = null
    ): mixed
    {
        if ($source !== null)
        {
            $this->source = $source;
        }
        if (!$this->source) {
            throw new JsonXException('Source for decoding is empty.', self::ERROR_DECODE);
        }
        $decode = json_decode(
            $this->toJson(),
            $this->associative,
            $this->depth,
            $this->flags
        );
        if ($decode === null) {
            throw new JsonXException(
                sprintf(
                    'Source cannot be decoded or the encoded data is deeper than the nesting limit (%s).',
                    $this->depth
                ),
                self::ERROR_DECODE
            );
        }
        return $decode;
    }

    /**
     * Decodes JSON/JSONX content of given file.
     *
     * @param string $fileName
     * @return mixed
     * @throws JsonXException
     */
    public function decodeFile(
        string $fileName
    ): mixed
    {
        $this->fromFile($fileName);
        return $this->decode();
    }

    /**
     * Writes JSON to file.
     *
     * @param string|null $targetFileName
     *
     * @return mixed
     * @throws JsonXException
     */
    public function writeJson(
        ?string $targetFileName = null
    ): mixed
    {
        // Convert source to JSON:
        $this->toJson();

        // Handle output file:
        if ($targetFileName === null) {
            if ($this->sourceFileName === null) {
                throw new JsonXException("Target file undefined.", self::ERROR_FILE);
            }
            $targetFileName = preg_replace('/\.[^\.]$/', '.' . self::FILE_EXT_JSON, $this->sourceFileName);
        }
        // Since now, $targetFileName cannot be null:
        /** @var string $targetFileName */
        if (file_exists($targetFileName)) {
            if (!$this->overwrite) {
                throw new JsonXException("Target file already exists ({$targetFileName}).", self::ERROR_FILE);
            }
            if (
                !is_file($targetFileName)
                || !is_writable($targetFileName)
            ) {
                throw new JsonXException("Target file already exists and is not file or is not writable ({$targetFileName}).", self::ERROR_FILE);
            }
        }

        // Write output:
        return file_put_contents($targetFileName, $this->source);
    }

    /**
     * Translates JSONX file to JSON.
     *
     * @param string $sourceFileName
     * @param ?string $targetFileName
     * @param bool $overwrite
     *
     * @return mixed
     * @throws JsonXException
     */
    public function translateFile(
        string $sourceFileName,
        ?string $targetFileName = null,
        bool $overwrite = true
    ): mixed
    {
        // Read input:
        $this->fromFile($sourceFileName);

        // Convert JSONX to JSON & write output file:
        return $this->writeJson($targetFileName);
    }

    /**
     * Sets "associative" argument for PHP json_decode used in decode() method.
     *
     * @param bool $associative
     * @return self
     */
    public function setAssociative(
        bool $associative
    ): self
    {
        $this->associative = $associative;
        return $this;
    }

    /**
     * Sets "depth" argument for PHP json_decode used in decode() method.
     *
     * @param int<1, max> $depth
     * @return self
     */
    public function setDepth(
        int $depth
    ): self
    {
        $this->depth = $depth;
        return $this;
    }

    /**
     * Sets "flags" argument for PHP json_decode used in decode() method.
     *
     * @param int $flags
     * @return self
     */
    public function setFlags(
        int $flags
    ): self
    {
        $this->flags = $flags;
        return $this;
    }

    /**
     * Sets "overwrite" flag.
     *
     * @param bool $overwrite
     * @return self
     */
    public function setOverwrite(
        bool $overwrite = true
    ): self
    {
        $this->overwrite = $overwrite;
        return $this;
    }

    /**
     * Removes comments from source.
     *
     * @return void
     */
    private function removeComments(): void
    {
        $this->source = preg_replace(
            '/\s*#(?=([^\"\\\\]*(\\\\.|\"([^\"\\\\]*\\\\.)*[^\"\\\\]*\"))*[^\"]*$).*$/m',
            '',
            $this->source
        ) ?? '';
        $this->source = preg_replace(
            '/\s*\/\/(?=([^\"\\\\]*(\\\\.|\"([^\"\\\\]*\\\\.)*[^\"\\\\]*\"))*[^\"]*$).*$/m',
            '',
            $this->source
        ) ?? '';
    }

    /**
     * Removes trailing commas from source.
     *
     * @return void
     */
    private function removeTrailingCommas(): void
    {
        $this->source = preg_replace(
            '/\,(?!\s*?[\{\[\"\'\w])/m',
            '',
            $this->source
        ) ?? '';
    }
}
