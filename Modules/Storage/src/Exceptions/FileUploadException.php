<?php

declare(strict_types=1);

namespace Maatify\Storage\Exceptions;

final class FileUploadException extends StorageException
{
    public static function fromErrorCode(int $code): self
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];

        $message = $messages[$code] ?? sprintf('Unknown upload error code: %d.', $code);

        return new self($message, $code);
    }

    public static function unreadableStream(): self
    {
        return new self('Could not read uploaded file stream.');
    }
}
