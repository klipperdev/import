<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Import;

use Klipper\Component\Content\ContentManagerInterface;
use Klipper\Component\Import\Model\ImportInterface;
use Klipper\Component\Metadata\MetadataManagerInterface;
use Klipper\Component\Metadata\ObjectMetadataInterface;
use Klipper\Component\Resource\Domain\DomainInterface;
use Klipper\Component\Resource\Domain\DomainManagerInterface;
use Klipper\Component\Resource\ResourceInterface;
use Klipper\Component\Resource\ResourceListInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface ImportContextInterface
{
    public function getDomainManager(): DomainManagerInterface;

    public function getContentManager(): ContentManagerInterface;

    public function getMetadataManager(): MetadataManagerInterface;

    public function getDomainImport(): DomainInterface;

    public function getDomainTarget(): DomainInterface;

    public function getImport(): ImportInterface;

    public function getMetadataTarget(): ObjectMetadataInterface;

    public function getMappingColumns(): array;

    public function getMappingFields(): array;

    public function getMappingAssociations(): array;

    public function getSpreadsheet(): Spreadsheet;

    public function getActiveSheet(): Worksheet;

    public function getWriter(): IWriter;

    public function getFile(): string;

    public function getLocale(): string;

    public function saveWriter(): void;

    public function getFieldIdentifierIndex(): int;

    public function getImportStatusIndex(): int;

    public function getImportMessageIndex(): int;

    public function setResult(ResourceListInterface $resourceList, int $rowIndex): void;

    public function saveImport(): ResourceInterface;
}
