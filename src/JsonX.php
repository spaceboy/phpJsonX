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
    private static string $jsonx = '';

    /**
     * @var bool
     */
    private static bool $associative = true;

    /**
     * @var int<1, max>
     */
    private static int $depth = 512;

    /**
     * @var int
     */
    private static int $flags = 0;

    /**
     * Loads JSONX string.
     *
     * @param string $jsonx
     * @return void
     */
    public static function fromString(
        string $jsonx
    ): void
    {
        self::$jsonx = $jsonx;
    }

    /**
     * Loads JSONX source string from file.
     *
     * @param string $fileName
     *
     * @return void
     * @throws JsonXException
     */
    public static function fromFile(
        string $fileName
    ): void
    {
        if (
            !file_exists($fileName)
            || !is_file($fileName)
            || !is_readable($fileName)
        )
        {
            throw new JsonXException("File not exists, is not file or is not readable ({$fileName}).", self::ERROR_FILE);
        }
        self::$jsonx = (file_get_contents($fileName) ?: '');
    }

    /**
     * Returns given JSONX source converted to JSON string;
     * when called without argument, returns actual (loaded by fromFile or fromString methods) JSONX converted to JSON.
     *
     * @param string $source
     *
     * @return string
     * @throws JsonXException
     */
    public static function toJson(
        string $source = null
    ): string
    {
        return preg_replace(
            '/\s+#(?=([^\"\\\\]*(\\\\.|\"([^\"\\\\]*\\\\.)*[^\"\\\\]*\"))*[^\"]*$).*$/m',
            '',
            ($source ?? self::$jsonx)
        );
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
    public static function decode(
        string $source = null
    )
    {
        if (
            $source === null
            && !self::$jsonx
        )
        {
            throw new JsonXException(
                'Source for decoding is empty.',
                self::ERROR_DECODE
            );
        }
        $decode = json_decode(
            self::toJson($source ?? self::$jsonx),
            self::$associative,
            self::$depth,
            self::$flags
        );
        if ($decode === null) {
            throw new JsonXException(
                sprintf(
                    'Source cannot be decoded or the encoded data is deeper than the nesting limit (%s).',
                    self::$depth
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
    public static function decodeFile(
        string $fileName
    )
    {
        self::fromFile($fileName);
        return self::decode();
    }

    /**
     * Translates JSONX file to JSON.
     *
     * @param string $sourceFileName
     * @param ?string $targetFileName
     * @param bool $overwrite
     *
     * @return int<0, max>|false
     * @throws JsonXException
     */
    public static function translateFile(
        string $sourceFileName,
        ?string $targetFileName = null,
        bool $overwrite = true
    ): mixed
    {
        if (
            !file_exists($sourceFileName)
            || !is_file($sourceFileName)
            || !is_readable($sourceFileName)
        )
        {
            throw new JsonXException("Source file not exists, is not file or is not readable ({$sourceFileName}).", self::ERROR_FILE);
        }
        if ($targetFileName === null)
        {
            $targetFileName = preg_replace('/\.[^\.]$/', '.' . self::FILE_EXT_JSON, $sourceFileName);
        }
        if (
            file_exists($targetFileName)
        )
        {
            if (!$overwrite)
            {
                throw new JsonXException("Target file already exists ({$targetFileName}).", self::ERROR_FILE);
            }
            if (
                !is_file($targetFileName)
                || !is_writable($targetFileName)
            )
            {
                throw new JsonXException("Target file already exists and is not file or is not writable ({$targetFileName}).", self::ERROR_FILE);
            }
        }
        return file_put_contents($targetFileName, self::toJson(file_get_contents($sourceFileName) ?: ''));
    }

    /**
     * Sets assoc argument for PHP json_decode used in decode() method.
     *
     * @param bool $associative
     * @return void
     */
    public static function setAssociative(
        bool $associative
    ): void
    {
        self::$associative = $associative;
    }

    /**
     * Sets assoc argument for PHP json_decode used in decode() method.
     *
     * @param int<1, max> $depth
     * @return void
     */
    public static function setDepth(
        int $depth
    ): void
    {
        self::$depth = $depth;
    }

    /**
     * Sets assoc argument for PHP json_decode used in decode() method.
     *
     * @param int $flags
     * @return void
     */
    public static function setFlags(
        int $flags
    ): void
    {
        self::$flags = $flags;
    }
}
