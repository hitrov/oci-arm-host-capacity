<?php

namespace Hitrov;

class OciConfig
{
    public string $region = '';
    public string $ociUserId = '';
    public string $compartmentId = '';
    public string $keyFingerPrint = '';
    public string $privateKeyFilename = '';
    public string $availabilityDomain = '';
    public string $subnetId = '';
    public string $imageId = '';
    public ?int $ocpus;
    public ?int $memoryInGBs;

    public function __construct(
        string $region,
        string $ociUserId,
        string $compartmentId,
        string $keyFingerPrint,
        string $privateKeyFilename,
        string $availabilityDomain,
        string $subnetId,
        string $imageId,
        int $ocups = 4,
        int $memoryInGBs = 24
    )
    {
        $this->region = $region;
        $this->ociUserId = $ociUserId;
        $this->compartmentId = $compartmentId;
        $this->keyFingerPrint = $keyFingerPrint;
        $this->privateKeyFilename = $privateKeyFilename;
        $this->availabilityDomain = $availabilityDomain;
        $this->subnetId = $subnetId;
        $this->imageId = $imageId;
        $this->ocpus = $ocups;
        $this->memoryInGBs = $memoryInGBs;
        $this->imageId = $imageId;
    }
}
