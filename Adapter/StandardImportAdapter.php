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
use Klipper\Component\Resource\Domain\DomainInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class StandardImportAdapter implements ImportAdapterInterface
{
    public function import(ImportContextInterface $context): bool
    {
        $translator = $context->getTranslator();
        $formFactory = $context->getFormFactory();
        $sheet = $context->getActiveSheet();
        $domainTarget = $context->getDomainTarget();
        $metaTarget = $context->getMetadataTarget();
        $fieldIdentifier = $metaTarget->getFieldIdentifier();
        $mappingColumns = $context->getMappingColumns();
        $locale = $context->getLocale();
        $prevLocale = \Locale::getDefault();
        $rowIterator = $sheet->getRowIterator(2);
        $formType = $metaTarget->getFormType();
        $formOptions = array_merge($metaTarget->getFormOptions(), ['csrf_protection' => false]);
        $batchSize = 20;
        $i = 0;
        $finalRes = true;

        $this->setLocale($translator, $locale);

        foreach ($rowIterator as $row) {
            $rowIndex = $row->getRowIndex();
            $data = [];
            $fieldIdentifierValue = null;

            if (\array_key_exists($fieldIdentifier, $mappingColumns)) {
                $idVal = $sheet->getCellByColumnAndRow($mappingColumns[$fieldIdentifier], $rowIndex)->getValue();

                if (!empty($idVal)) {
                    $fieldIdentifierValue = $idVal;
                }
            }

            foreach ($context->getMappingFields() as $field => $colIndex) {
                $data[$field] = $sheet->getCellByColumnAndRow($colIndex, $rowIndex)->getValue();
            }

            foreach ($context->getMappingAssociations() as $association => $colIndex) {
                $data[$association] = $sheet->getCellByColumnAndRow($colIndex, $rowIndex)->getValue();
            }

            $object = $this->findObject($domainTarget, $fieldIdentifierValue);

            if (null === $object) {
                $context->setResultError(
                    $rowIndex,
                    $translator->trans('domain.object_does_not_exist', [], 'KlipperResource')
                );
            } else {
                $form = $formFactory->create($formType, $object, $formOptions);
                $form->submit($data, false);
                $res = $domainTarget->upsert($form);

                $finalRes = $finalRes && $res->isValid();
                $context->setResult($res, $rowIndex);
            }

            if (0 === ($i % $batchSize)) {
                $context->saveWriter();
                $context->saveImport();
            }

            ++$i;
        }

        $sheet->getColumnDimensionByColumn($context->getFieldIdentifierIndex())->setAutoSize(true);
        $sheet->getColumnDimensionByColumn($context->getImportStatusIndex())->setAutoSize(true);
        $sheet->getColumnDimensionByColumn($context->getImportMessageIndex())->setAutoSize(true);
        $context->saveWriter();
        $this->setLocale($translator, $prevLocale);

        return $finalRes;
    }

    public function validate(ImportContextInterface $context): bool
    {
        return true;
    }

    private function findObject(DomainInterface $domainTarget, $id): ?object
    {
        if (null !== $id) {
            return $domainTarget->getRepository()->find($id);
        }

        return $domainTarget->newInstance();
    }

    private function setLocale(TranslatorInterface $translator, string $locale): void
    {
        \Locale::setDefault($locale);

        if ($translator instanceof LocaleAwareInterface) {
            $translator->setLocale($locale);
        }
    }
}
