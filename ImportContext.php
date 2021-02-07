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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ImportContext implements ImportContextInterface
{
    private DomainManagerInterface $domainManager;

    private ContentManagerInterface $contentManager;

    private MetadataManagerInterface $metadataManager;

    private DomainInterface $domainImport;

    private DomainInterface $domainTarget;

    private ObjectMetadataInterface $metadataTarget;

    private ImportInterface $import;

    private array $mappingColumns;

    private array $mappingFields;

    private array $mappingAssociations;

    private Spreadsheet $spreadsheet;

    private Worksheet $activeSheet;

    private IWriter $writer;

    private string $file;

    public function __construct(
        DomainManagerInterface $domainManager,
        ContentManagerInterface $contentManager,
        MetadataManagerInterface $metadataManager,
        DomainInterface $domainImport,
        DomainInterface $domainTarget,
        ObjectMetadataInterface $metadataTarget,
        ImportInterface $import,
        array $mappingColumns,
        array $mappingFields,
        array $mappingAssociations,
        Spreadsheet $spreadsheet,
        Worksheet $activeSheet,
        IWriter $writer,
        string $file
    ) {
        $this->domainManager = $domainManager;
        $this->contentManager = $contentManager;
        $this->metadataManager = $metadataManager;
        $this->domainImport = $domainImport;
        $this->domainTarget = $domainTarget;
        $this->metadataTarget = $metadataTarget;
        $this->import = $import;
        $this->mappingColumns = $mappingColumns;
        $this->mappingFields = $mappingFields;
        $this->mappingAssociations = $mappingAssociations;
        $this->spreadsheet = $spreadsheet;
        $this->activeSheet = $activeSheet;
        $this->writer = $writer;
        $this->file = $file;
    }

    public function getDomainManager(): DomainManagerInterface
    {
        return $this->domainManager;
    }

    public function getContentManager(): ContentManagerInterface
    {
        return $this->contentManager;
    }

    public function getMetadataManager(): MetadataManagerInterface
    {
        return $this->metadataManager;
    }

    public function getDomainImport(): DomainInterface
    {
        return $this->domainImport;
    }

    public function getDomainTarget(): DomainInterface
    {
        return $this->domainTarget;
    }

    public function getMetadataTarget(): ObjectMetadataInterface
    {
        return $this->metadataTarget;
    }

    public function getImport(): ImportInterface
    {
        return $this->import;
    }

    public function getMappingColumns(): array
    {
        return $this->mappingColumns;
    }

    public function getMappingFields(): array
    {
        return $this->mappingFields;
    }

    public function getMappingAssociations(): array
    {
        return $this->mappingAssociations;
    }

    public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadsheet;
    }

    public function getActiveSheet(): Worksheet
    {
        return $this->activeSheet;
    }

    public function getWriter(): IWriter
    {
        return $this->writer;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function saveWriter(): void
    {
        $this->writer->save($this->getFile());
    }

    public function getFieldIdentifierIndex(): int
    {
        $fieldIdentifier = $this->metadataTarget->getFieldIdentifier();

        return $this->mappingColumns[$fieldIdentifier] ?? \count($this->mappingColumns) + 1;
    }

    public function getImportStatusIndex(): int
    {
        return $this->mappingColumns['@import_status'] ?? \count($this->mappingColumns) + 2;
    }

    public function getImportMessageIndex(): int
    {
        return $this->mappingColumns['@import_message'] ?? \count($this->mappingColumns) + 2;
    }
}
