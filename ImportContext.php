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
use Klipper\Component\Resource\ResourceStatutes;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use function Symfony\Component\String\b;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ImportContext implements ImportContextInterface
{
    private const DEFAULT_ROW_HEIGHT = 15;

    private FormFactoryInterface $formFactory;

    private TranslatorInterface $translator;

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
        FormFactoryInterface $formFactory,
        TranslatorInterface $translator,
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
        $this->formFactory = $formFactory;
        $this->translator = $translator;
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

    public function getFormFactory(): FormFactoryInterface
    {
        return $this->formFactory;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->propertyAccessor;
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

    public function setResult(ResourceInterface $resource, int $rowIndex): void
    {
        $sheet = $this->getActiveSheet();

        if ($resource->isValid()) {
            $this->import->setSuccessCount($this->import->getSuccessCount() + 1);
        } else {
            $this->import->setErrorCount($this->import->getErrorCount() + 1);
        }

        // Inject Id
        $id = $this->propertyAccessor->getValue($resource->getRealData(), $this->metadataTarget->getFieldIdentifier());
        $sheet->setCellValueByColumnAndRow($this->getFieldIdentifierIndex(), $rowIndex, $id);

        // Inject status
        $sheet->setCellValueByColumnAndRow($this->getImportStatusIndex(), $rowIndex, $resource->getStatus());

        // Inject message
        $message = $resource->isValid() ? null : $this->buildErrors($resource);
        $sheet->setCellValueByColumnAndRow($this->getImportMessageIndex(), $rowIndex, $message);

        $rowDim = $sheet->getRowDimension($rowIndex);
        $finalVal = $sheet->getCellByColumnAndRow($this->getImportMessageIndex(), $rowIndex)->getValue();
        $lineHeight = self::DEFAULT_ROW_HEIGHT;

        if (\is_string($finalVal)) {
            $lineHeight = self::DEFAULT_ROW_HEIGHT * \count(b($finalVal)->split("\n"));
        }

        if ($lineHeight > $rowDim->getRowHeight()) {
            $rowDim->setRowHeight($lineHeight);
        }
    }

    public function setResultError(int $rowIndex, $message): void
    {
        $sheet = $this->getActiveSheet();
        $statusColIndex = $this->getImportStatusIndex();
        $messageColIndex = $this->getImportMessageIndex();

        $sheet->setCellValueByColumnAndRow($statusColIndex, $rowIndex, ResourceStatutes::ERROR);
        $sheet->setCellValueByColumnAndRow($messageColIndex, $rowIndex, $message);

        $this->import->setErrorCount($this->import->getErrorCount() + 1);
    }

    public function saveImport(): ResourceInterface
    {
        return $this->domainImport->update($this->import);
    }

    /**
     * Build the errors message.
     *
     * @param ResourceInterface $resource The resource
     * @param int               $indent   The indentation
     */
    private function buildErrors(ResourceInterface $resource, int $indent = 0): string
    {
        $formErrors = $resource->getFormErrors();
        $rootErrors = $resource->getErrors();

        $titleField = $this->translator->trans('klipper_import.title_field');
        $indentStr = sprintf("%{$indent}s", '');
        $message = PHP_EOL.$indentStr.$this->translator->trans('klipper_import.title_errors');

        /** @var FormError $formError */
        foreach ($formErrors as $formError) {
            $message .= PHP_EOL.$indentStr.'  - ';

            if (null !== $formError->getOrigin()->getParent()) {
                $message .= $titleField.' "'.$formError->getOrigin()->getName().'": ';
            }

            $message .= $formError->getMessage();
        }

        if ($rootErrors->count() > 0) {
            /** @var ConstraintViolationInterface $rootError */
            foreach ($rootErrors as $rootError) {
                $message .= PHP_EOL.'  '.$rootError->getMessage();
            }
        } elseif (0 === \count($formErrors)) {
            $message .= PHP_EOL.'  '.$this->translator->trans('klipper_import.error_without_message');
        }

        return trim($message);
    }
}
