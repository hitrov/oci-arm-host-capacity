<?php
declare(strict_types=1);

namespace Hitrov;

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

    /**
     * OciConfig constructor.
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
}
