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

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Klipper\Component\DoctrineExtensionsExtra\Mapping\Annotation\MetadataField;
use Klipper\Component\Import\Validator\Constraints as KlipperImportAssert;
use Klipper\Component\Model\Traits\FilePathTrait;
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Component\Model\Traits\UserTrackableTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractImport implements ImportInterface
{
    use FilePathTrait;
    use OrganizationalRequiredTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected ?string $metadata = null;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     */
    protected ?string $adapter = null;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @KlipperImportAssert\ImportStatusChoice
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected ?string $status = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=65535)
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected ?string $statusCode = null;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=5)
     *
     * @Serializer\Expose
     */
    protected ?string $locale = null;

    /**
     * @ORM\Column(type="json")
     *
     * @Serializer\Expose
     */
    protected array $extra = [];

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="integer")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected int $totalCount = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="integer")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected int $successCount = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="integer")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected int $errorCount = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected ?\DateTimeInterface $startedAt = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected ?\DateTimeInterface $endedAt = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(max=255)
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     * @Serializer\SerializedName("original_file_url")
     * @Serializer\Type("OrgUrl<'klipper_apiimport_import_downloadoriginal', 'id=`{{id}}`'>")
     *
     * @MetadataField(
     *     sortable=false,
     *     filterable=false,
     *     searchable=false
     * )
     */
    protected ?string $filePath = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(max=255)
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     * @Serializer\SerializedName("result_file_url")
     * @Serializer\Type("OrgUrl<'klipper_apiimport_import_downloadresult', 'id=`{{id}}`'>")
     *
     * @MetadataField(
     *     sortable=false,
     *     filterable=false,
     *     searchable=false
     * )
     */
    protected ?string $resultFilePath = null;

    public function setMetadata(?string $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getMetadata(): ?string
    {
        return $this->metadata;
    }

    public function setAdapter(?string $adapter): self
    {
        $this->adapter = $adapter;

        return $this;
    }

    public function getAdapter(): ?string
    {
        return $this->adapter;
    }

    /**
     * @return static
     */
    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatusCode(?string $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getStatusCode(): ?string
    {
        return $this->statusCode;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getExtra(): array
    {
        return $this->extra ?? [];
    }

    public function hasExtra(string $key): bool
    {
        return isset($this->extra[$key]);
    }

    public function getExtraValue(string $key)
    {
        return $this->extra[$key] ?? null;
    }

    public function setExtra(array $extra): void
    {
        foreach ($extra as $key => $value) {
            if (empty($value)) {
                $this->removeExtra($key);
            } else {
                $this->addExtra($key, $value);
            }
        }
    }

    public function addExtra(string $extra, $value): void
    {
        $this->extra[$extra] = $value;
    }

    public function removeExtra(string $key): void
    {
        unset($this->extra[$key]);
    }

    public function clearExtra(): void
    {
        $this->extra = [];
    }

    public function setTotalCount(int $totalCount): self
    {
        $this->totalCount = $totalCount;

        return $this;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function setSuccessCount(int $successCount): self
    {
        $this->successCount = $successCount;

        return $this;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function setErrorCount(int $errorCount): self
    {
        $this->errorCount = $errorCount;

        return $this;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setEndedAt(?\DateTimeInterface $endedAt): self
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeInterface
    {
        return $this->endedAt;
    }

    public function setResultFilePath(?string $resultFilePath): self
    {
        $this->resultFilePath = $resultFilePath;

        return $this;
    }

    public function getResultFilePath(): ?string
    {
        return $this->resultFilePath;
    }

    public function hasResultFile(): bool
    {
        return null !== $this->resultFilePath;
    }

    public function getResultFileExtension(): ?string
    {
        return null !== $this->resultFilePath ? pathinfo($this->resultFilePath, PATHINFO_EXTENSION) : null;
    }
}
