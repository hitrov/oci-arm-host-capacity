<?php

namespace Hitrov\Interfaces;

interface TooManyRequestsWaiterInterface
{
    public function isTooEarly(): bool;
    public function isConfigured(): bool;
    public function secondsRemaining(): int;
    public function enable(): void;
    public function remove(): void;
}
