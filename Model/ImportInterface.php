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
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Component\Model\Traits\UserTrackableInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface ImportInterface extends
    IdInterface,
    FilePathInterface,
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
