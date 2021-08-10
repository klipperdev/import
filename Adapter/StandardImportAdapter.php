<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Import\Adapter;

use Klipper\Component\Import\ImportContextInterface;
use Klipper\Component\Resource\ResourceInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class StandardImportAdapter implements ImportAdapterInterface
{
    public function validate(ImportContextInterface $context): bool
    {
        return true;
    }

    public function import(ImportContextInterface $context): bool
    {
        $translator = $context->getTranslator();
        $sheet = $context->getActiveSheet();
        $domainTarget = $context->getDomainTarget();
        $metaTarget = $context->getMetadataTarget();
        $fieldIdentifier = $metaTarget->getFieldIdentifier();
        $mappingColumns = $context->getMappingColumns();
        $locale = $context->getLocale();
        $prevLocale = \Locale::getDefault();
        $rowIterator = $sheet->getRowIterator(2);
        $batchSize = 20;
        $i = 0;
        $countRow = 0;
        $finalRes = true;

        $this->setLocale($translator, $locale);

        foreach ($rowIterator as $row) {
            $rowIndex = $row->getRowIndex();
            $fieldIdentifierValue = null;

            if (\array_key_exists($fieldIdentifier, $mappingColumns)) {
                $idVal = $sheet->getCellByColumnAndRow($mappingColumns[$fieldIdentifier], $rowIndex)->getValue();

                if (!empty($idVal)) {
                    $fieldIdentifierValue = $idVal;
                }
            }

            $data = $this->buildData($context, $row);

            if ($this->isEmptyData($data)) {
                $context->getImport()->setTotalCount($countRow);
                $context->saveWriter();
                $context->saveImport();

                break;
            }
            $object = $this->findObject($context, $fieldIdentifierValue, $data);

            if (null === $object) {
                $context->setResultError(
                    $rowIndex,
                    $translator->trans('domain.object_does_not_exist', [], 'KlipperResource')
                );
            } else {
                $form = $this->createForm($context, $object);
                $form->submit($data, false);
                $res = $domainTarget->upsert($form);

                $this->hookAfterUpsert($context, $res);

                $finalRes = $finalRes && $res->isValid();
                $context->setResult($res, $rowIndex);
            }

            if (0 === ($i % $batchSize)) {
                $context->saveWriter();
                $context->saveImport();
            }

            ++$i;
            ++$countRow;
        }

        $sheet->getColumnDimensionByColumn($context->getFieldIdentifierIndex())->setAutoSize(true);
        $sheet->getColumnDimensionByColumn($context->getImportStatusIndex())->setAutoSize(true);
        $sheet->getColumnDimensionByColumn($context->getImportMessageIndex())->setAutoSize(true);
        $context->saveWriter();
        $this->setLocale($translator, $prevLocale);

        return $finalRes;
    }

    protected function buildData(ImportContextInterface $context, Row $row): array
    {
        $sheet = $context->getActiveSheet();
        $rowIndex = $row->getRowIndex();
        $data = [];

        foreach ($context->getMappingFields() as $field => $colIndex) {
            $data[$field] = $sheet->getCellByColumnAndRow($colIndex, $rowIndex)->getValue();
        }

        foreach ($context->getMappingAssociations() as $association => $colIndex) {
            $data[$association] = $sheet->getCellByColumnAndRow($colIndex, $rowIndex)->getValue();
        }

        return $data;
    }

    protected function findObject(ImportContextInterface $context, $id, array $data): ?object
    {
        $domainTarget = $context->getDomainTarget();

        if (null !== $id) {
            return $domainTarget->getRepository()->find($id);
        }

        return $domainTarget->newInstance();
    }

    protected function createForm(ImportContextInterface $context, object $object): FormInterface
    {
        $metaTarget = $context->getMetadataTarget();
        $formFactory = $context->getFormFactory();
        $formType = $metaTarget->getFormType();
        $formOptions = array_merge($metaTarget->getFormOptions(), ['csrf_protection' => false]);

        return $formFactory->create($formType, $object, $formOptions);
    }

    protected function hookAfterUpsert(ImportContextInterface $context, ResourceInterface $result): void
    {
        // Override this method
    }

    protected function setLocale(TranslatorInterface $translator, string $locale): void
    {
        \Locale::setDefault($locale);

        if ($translator instanceof LocaleAwareInterface) {
            $translator->setLocale($locale);
        }
    }

    protected function isEmptyData(array $data): bool
    {
        foreach ($data as $value) {
            if (null !== $value && '' !== $value) {
                return false;
            }
        }

        return true;
    }
}
