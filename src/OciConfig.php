<?php
declare(strict_types=1);

namespace Hitrov;

use Hitrov\Exception\AvailabilityDomainRequiredException;
use Hitrov\Exception\BootVolumeSizeException;

class OciConfig
{
    public string $region = '';
    public string $ociUserId = '';
    public string $tenancyId = '';
    public string $keyFingerPrint = '';
    public string $privateKeyFilename = '';

    /**
     * @var array|string|null
     */
    public $availabilityDomains;
    public string $subnetId = '';
    public string $imageId = '';
    public ?int $ocpus;
    public ?int $memoryInGBs;

    public string $sourceDetails;
    public string $bootVolumeId;
    public string $bootVolumeSizeInGBs;

    /**
     * @param string $region
     * @param string $ociUserId
     * @param string $tenancyId
     * @param string $keyFingerPrint
     * @param string $privateKeyFilename
     * @param string|array|null $availabilityDomains
     * @param string $subnetId
     * @param string $imageId
     * @param int $ocups
     * @param int $memoryInGBs
     */
    public function __construct(
        string $region,
        string $ociUserId,
        string $tenancyId,
        string $keyFingerPrint,
        string $privateKeyFilename,
        $availabilityDomains,
        string $subnetId,
        string $imageId,
        int $ocups = 4,
        int $memoryInGBs = 24
    )
    {
        $this->region = $region;
        $this->ociUserId = $ociUserId;
        $this->tenancyId = $tenancyId;
        $this->keyFingerPrint = $keyFingerPrint;
        $this->privateKeyFilename = $privateKeyFilename;
        $this->availabilityDomains = $availabilityDomains;
        $this->subnetId = $subnetId;
        $this->imageId = $imageId;
        $this->ocpus = $ocups;
        $this->memoryInGBs = $memoryInGBs;
        $this->imageId = $imageId;
    }

    /**
     * @param string $bootVolumeId
     */
    public function setBootVolumeId(string $bootVolumeId): void
    {
        $this->bootVolumeId = $bootVolumeId;
    }

    /**
     * @return string
     * @throws AvailabilityDomainRequiredException|BootVolumeSizeException
     */
    public function getSourceDetails(): string
    {
        if (isset($this->sourceDetails)) {
            return $this->sourceDetails;
        }

        $sourceDetails = [
            'sourceType' => 'image',
            'imageId' => $this->imageId,
        ];

        if (!empty($this->bootVolumeId) && !empty($this->bootVolumeSizeInGBs)) {
            throw new BootVolumeSizeException('OCI_BOOT_VOLUME_ID and OCI_BOOT_VOLUME_SIZE_IN_GBS cannot be used together');
        }

        if (!empty($this->bootVolumeSizeInGBs)) {
            if (!is_numeric($this->bootVolumeSizeInGBs)) {
                throw new BootVolumeSizeException('OCI_BOOT_VOLUME_SIZE_IN_GBS must be numeric');
            }
            $sourceDetails['bootVolumeSizeInGBs'] = $this->bootVolumeSizeInGBs;
        } elseif (!empty($this->bootVolumeId)) {
            if (!is_string($this->availabilityDomains) || empty($this->availabilityDomains)) {
                throw new AvailabilityDomainRequiredException('OCI_AVAILABILITY_DOMAIN must be specified as string if using OCI_BOOT_VOLUME_ID');
            }

            $sourceDetails = [
                'sourceType' => 'bootVolume',
                'bootVolumeId' => $this->bootVolumeId,
            ];
        }

        return json_encode($sourceDetails);
    }

    /**
     * @param string $bootVolumeSizeInGBs
     */
    public function setBootVolumeSizeInGBs(string $bootVolumeSizeInGBs): void
    {
        $this->bootVolumeSizeInGBs = $bootVolumeSizeInGBs;
    }

    /**
     * @param string $sourceDetails
     */
    public function setSourceDetails(string $sourceDetails): void
    {
        $this->sourceDetails = $sourceDetails;
    }
}
