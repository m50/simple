<?php

declare(strict_types=1);

namespace NotSoSimple\Config;

interface ConfigInterface
{
    /**
     * @psalm-mutation-free
     * @return array<string,mixed>
     */
    public function toArray(): array;
}
