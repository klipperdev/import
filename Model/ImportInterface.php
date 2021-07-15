<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Import\Model;

use Klipper\Component\Model\Traits\FilePathInterface;
use Klipper\Component\Model\Traits\IdInterface;
use Klipper\Component\Model\Traits\OrganizationalRequiredInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Component\Model\Traits\UserTrackableInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface ImportInterface extends
    IdInterface,
    FilePathInterface,
    OrganizationalRequiredInterface,
    TimestampableInterface,
    UserTrackableInterface
{
    /**
     * @return static
     */
    public function setMetadata(?string $metadata);

    public function getMetadata(): ?string;

    /**
     * @return static
     */
    public function setAdapter(?string $adapter);

    public function getAdapter(): ?string;

    /**
     * @return static
     */
    public function setStatus(?string $status);

    public function getStatus(): ?string;

    /**
     * @return static
     */
    public function setStatusCode(?string $statusCode);

    public function getStatusCode(): ?string;

    /**
     * @return static
     */
    public function setLocale(?string $locale);

    public function getLocale(): ?string;

    public function getExtra(): array;

    public function hasExtra(string $key): bool;

    /**
     * @return null|mixed
     */
    public function getExtraValue(string $key);

    public function setExtra(array $extra): void;

    /**
     * @param null|mixed $value
     */
    public function addExtra(string $extra, $value): void;

    public function removeExtra(string $key): void;

    public function clearExtra(): void;

    /**
     * @return static
     */
    public function setTotalCount(int $totalCount);

    public function getTotalCount(): int;

    /**
     * @return static
     */
    public function setSuccessCount(int $successCount);

    public function getSuccessCount(): int;

    /**
     * @return static
     */
    public function setErrorCount(int $errorCount);

    public function getErrorCount(): int;

    /**
     * @return static
     */
    public function setStartedAt(?\DateTimeInterface $startedAt);

    public function getStartedAt(): ?\DateTimeInterface;

    /**
     * @return static
     */
    public function setEndedAt(?\DateTimeInterface $endedAt);

    public function getEndedAt(): ?\DateTimeInterface;

    /**
     * @return static
     */
    public function setResultFilePath(?string $resultFilePath);

    public function getResultFilePath(): ?string;

    public function hasResultFile(): bool;

    public function getResultFileExtension(): ?string;
}
