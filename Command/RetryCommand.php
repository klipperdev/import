<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Import\Command;

use Klipper\Component\DataLoader\Exception\ConsoleResourceException;
use Klipper\Component\Import\Exception\ImportNotFoundException;
use Klipper\Component\Import\ImportManagerInterface;
use Klipper\Component\Import\Message\ImportRunMessage;
use Klipper\Component\Import\Model\ImportInterface;
use Klipper\Component\Resource\Domain\DomainManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class RetryCommand extends Command
{
    private ImportManagerInterface $importManager;

    private DomainManagerInterface $domainManager;

    private MessageBusInterface $messageBus;

    public function __construct(
        ImportManagerInterface $importManager,
        DomainManagerInterface $domainManager,
        MessageBusInterface $messageBus,
        string $name = null
    ) {
        parent::__construct($name);

        $this->importManager = $importManager;
        $this->domainManager = $domainManager;
        $this->messageBus = $messageBus;
    }

    protected function configure(): void
    {
        $this
            ->setName('import:retry')
            ->setDescription('Retry the import')
            ->addArgument('id', InputArgument::REQUIRED, 'The import id')
        ;
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        $style = new SymfonyStyle($input, $output);

        $domainImport = $this->domainManager->get(ImportInterface::class);
        $repo = $domainImport->getRepository();
        /** @var null|ImportInterface $import */
        $import = $repo->find($id);

        if (null === $import) {
            throw new ImportNotFoundException(sprintf('The import with id "%s" does not exist', $id));
        }

        if (!$this->importManager->reset($import)) {
            throw new RuntimeException('The import cannot be retried');
        }

        $res = $domainImport->upsert($import);

        if (!$res->isValid()) {
            throw new ConsoleResourceException($res);
        }

        $this->messageBus->dispatch(new ImportRunMessage($import->getId()));

        $style->success('The import has been relaunched with successfully and now is in the queue');

        return Command::SUCCESS;
    }
}
