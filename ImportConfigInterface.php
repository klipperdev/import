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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface ImportConfigInterface
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

    public function getWriter(): IWriter;

    public function getFile(): string;
}
