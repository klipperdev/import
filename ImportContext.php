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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

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

    private PropertyAccessorInterface $propertyAccessor;

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
        string $file,
        ?PropertyAccessorInterface $propertyAccessor = null
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
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
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

    public function getLocale(): string
    {
        return $this->import->getLocale() ?? 'en';
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

    public function setResult(ResourceListInterface $resourceList, int $rowIndex): void
    {
        $resources = $resourceList->getResources();
        $currentRowIndex = $rowIndex;
        $sheet = $this->getActiveSheet();
        $fieldIdentifier = $this->metadataTarget->getFieldIdentifier();
        $idColIndex = $this->getFieldIdentifierIndex();
        $statusColIndex = $this->getImportStatusIndex();
        $messageColIndex = $this->getImportMessageIndex();

        foreach ($resources as $resource) {
            $hasError = $resource->getErrors()->count() > 0;

            if ($hasError) {
                $this->import->setErrorCount($this->import->getErrorCount() + 1);
            } else {
                $this->import->setSuccessCount($this->import->getSuccessCount() + 1);
            }

            // Inject Id
            $id = $this->propertyAccessor->getValue($resource->getRealData(), $fieldIdentifier);
            $sheet->setCellValueByColumnAndRow($idColIndex, $currentRowIndex, $id);

            // Inject status
            $sheet->setCellValueByColumnAndRow($statusColIndex, $currentRowIndex, $resource->getStatus());

            // Inject message
            $message = $hasError ? $this->buildErrors($resource->getErrors()) : null;
            $sheet->setCellValueByColumnAndRow($messageColIndex, $currentRowIndex, $message);

            ++$currentRowIndex;
        }
    }

    public function saveImport(): ResourceInterface
    {
        return $this->domainImport->update($this->import);
    }

    /**
     * Build the errors message.
     *
     * @param ConstraintViolationListInterface $violations The constraint violation
     * @param int                              $indent     The indentation
     */
    private function buildErrors(ConstraintViolationListInterface $violations, int $indent = 0): string
    {
        $indentStr = sprintf("%{$indent}s", ' ');
        $message = PHP_EOL.$indentStr.'Errors:';

        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $message .= PHP_EOL.$indentStr.'  - ';

            if (null !== $violation->getPropertyPath()) {
                $message .= 'Field "'.$violation->getPropertyPath().'": ';
            }

            $message .= $violation->getMessage();
        }

        return $message;
    }
}
