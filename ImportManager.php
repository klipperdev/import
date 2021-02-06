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
use Klipper\Component\Import\Adapter\ImportAdapterInterface;
use Klipper\Component\Import\Exception\ImportNotFoundException;
use Klipper\Component\Import\Model\ImportInterface;
use Klipper\Component\Metadata\MetadataManagerInterface;
use Klipper\Component\Metadata\ObjectMetadataInterface;
use Klipper\Component\Resource\Domain\DomainInterface;
use Klipper\Component\Resource\Domain\DomainManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ImportManager implements ImportManagerInterface
{
    private DomainManagerInterface $domainManager;

    private ContentManagerInterface $contentManager;

    private MetadataManagerInterface $metadataManager;

    /**
     * @var ImportAdapterInterface[]
     */
    private array $adapters;

    /**
     * @param ImportAdapterInterface[] $adapters
     */
    public function __construct(
        DomainManagerInterface $domainManager,
        ContentManagerInterface $contentManager,
        MetadataManagerInterface $metadataManager,
        array $adapters
    ) {
        $this->domainManager = $domainManager;
        $this->contentManager = $contentManager;
        $this->metadataManager = $metadataManager;
        $this->adapters = $adapters;
    }

    public function reset(ImportInterface $import): bool
    {
        if (!\in_array($import->getStatus(), ['in_progress', 'success'], true)) {
            $import->setStatus('waiting');
            $import->setStatusCode(null);
            $import->setStartedAt(null);
            $import->setEndedAt(null);

            return true;
        }

        return false;
    }

    public function run($import): ImportInterface
    {
        set_time_limit(0);

        $id = $import instanceof ImportInterface ? $import->getId() : $import;
        $domainImport = $this->domainManager->get(ImportInterface::class);
        $repo = $domainImport->getRepository();
        /** @var null|ImportInterface $import */
        $import = $repo->find($id);
        $columns = [];
        $hasError = false;

        if (null === $import) {
            throw new ImportNotFoundException(sprintf('The import with id "%s" does not exist', $id));
        }

        if (!$this->metadataManager->hasByName((string) $import->getMetadata())) {
            throw new ImportNotFoundException(sprintf('The import with id "%s" has a non-existent metadata "%s"', $id, $import->getMetadata()));
        }

        $metadata = $this->metadataManager->getByName((string) $import->getMetadata());
        $domainTarget = $this->domainManager->get($metadata->getClass());

        if (!$hasError && !$this->prepareImport($domainImport, $import)) {
            $hasError = true;
        }

        if (!$hasError && !$this->duplicateFile($domainImport, $import)) {
            $hasError = true;
        }

        if (!$hasError && !$this->findColumns($import, $columns)) {
            $hasError = true;
        }

        if (!$hasError && !$this->importData($domainImport, $domainTarget, $metadata, $import, $columns)) {
            $hasError = true;
        }

        $this->finishImport($domainImport, $import, $hasError);

        return $import;
    }

    private function prepareImport(DomainInterface $domainImport, ImportInterface $import): bool
    {
        if ('in_progress' === $import->getStatus()) {
            return false;
        }

        $import->setStatus('in_progress');
        $import->setStatusCode(null);
        $import->setStartedAt(new \DateTime());
        $import->setEndedAt(null);

        return $domainImport->update($import)->isValid();
    }

    private function duplicateFile(DomainInterface $domainImport, ImportInterface $import): bool
    {
        if (null === $import->getResultFilePath()) {
            $originPath = $import->getFilePath();
            $ext = $import->getFileExtension();
            $resultPath = preg_replace('/^(.*)\.'.$ext.'$/', '$1_result.'.$ext, $originPath);
            $import->setResultFilePath($resultPath);

            try {
                $this->contentManager->copy('import', $originPath, $resultPath);
            } catch (\Throwable $e) {
                $import->setStatusCode(ImportErrorCodes::ERROR_COPY_FILE);
                $domainImport->update($import);

                return false;
            }

            $res = $domainImport->update($import);

            if (!$res->isValid()) {
                try {
                    $this->contentManager->remove('import', $resultPath);
                } catch (\Throwable $e) {
                    // no check to optimize request to delete file, so do nothing on error
                }
            }

            return $res->isValid();
        }

        return true;
    }

    private function findColumns(ImportInterface $import, array &$columns): bool
    {
        try {
            $uploaderName = $this->contentManager->getUploaderName($import);
            $file = $this->contentManager->buildAbsolutePath($uploaderName, $import->getResultFilePath());
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $highestCol = $sheet->getHighestColumn();

            $rangeRowNames = $sheet->rangeToArray('A1:'.$highestCol.'1');
            $spreadsheet->garbageCollect();

            foreach ($rangeRowNames as $rangeRow) {
                $index = 0;
                foreach ($rangeRow as $rangeName) {
                    ++$index;

                    if (!empty($rangeName)) {
                        $columns[(string) $rangeName] = $index;
                    }
                }
            }

            return true;
        } catch (\Throwable $e) {
            $import->setStatusCode(ImportErrorCodes::UNREADABLE_FILE);

            return false;
        }
    }

    private function importData(
        DomainInterface $domainImport,
        DomainInterface $domainTarget,
        ObjectMetadataInterface $metadataTarget,
        ImportInterface $import,
        array $columns
    ): bool {
        $mappingColumns = [];
        $mappingFields = [];
        $mappingAssociations = [];

        foreach ($columns as $column => $index) {
            if ($metadataTarget->hasFieldByName($column)) {
                $mappingFields[$column] = $index;
                $mappingColumns[$column] = $index;
            } elseif ($metadataTarget->hasAssociationByName($column)) {
                $mappingAssociations[$column] = $index;
                $mappingColumns[$column] = $index;
            }
        }

        try {
            $uploaderName = $this->contentManager->getUploaderName($import);
            $file = $this->contentManager->buildAbsolutePath($uploaderName, $import->getResultFilePath());
            $type = IOFactory::identify($file);
            $spreadsheet = IOFactory::load($file);
            $writer = IOFactory::createWriter($spreadsheet, $type);
        } catch (\Throwable $e) {
            $import->setStatusCode(ImportErrorCodes::UNREADABLE_FILE);

            return false;
        }

        $config = new ImportConfig(
            $this->domainManager,
            $this->contentManager,
            $this->metadataManager,
            $domainImport,
            $domainTarget,
            $metadataTarget,
            $import,
            $mappingColumns,
            $mappingFields,
            $mappingAssociations,
            $spreadsheet,
            $writer,
            $file
        );

        try {
            if (null !== $adapterClass = $import->getAdapter()) {
                foreach ($this->adapters as $adapter) {
                    if (is_a($adapter, $adapterClass)) {
                        return $adapter->import($config);
                    }
                }

                $import->setStatusCode(ImportErrorCodes::UNDEFINED_ADAPTER);

                return false;
            }

            foreach ($this->adapters as $adapter) {
                if ($adapter->validate($config)) {
                    return $adapter->import($config);
                }
            }

            $import->setStatusCode(ImportErrorCodes::NO_ADAPTER_AVAILABLE);

            return false;
        } catch (\Throwable $e) {
            $import->setStatusCode(ImportErrorCodes::UNEXPECTED_ERROR);

            return false;
        }
    }

    private function finishImport(DomainInterface $domainImport, ImportInterface $import, bool $hasError): bool
    {
        $import->setStatus($hasError ? 'error' : 'success');
        $import->setEndedAt(new \DateTime());

        return $domainImport->update($import)->isValid();
    }
}
