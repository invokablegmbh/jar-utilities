<?php

declare(strict_types=1);

namespace Jar\Utilities\Event;

use TYPO3\CMS\Core\Resource\File;

final class BuildFileArrayBySysFileReferenceEvent
{
    public function __construct(
        private array $data,
        private readonly File $file,
    )
    {
    }


    public function getFile(): File
    {
        return $this->file;
    }


    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

}
