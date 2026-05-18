<?php
declare(strict_types=1);
namespace CombatGameAdsManager\Core;

final class Container
{
    private array $bindings = [];
    public function set(string $id, callable $resolver): void { $this->bindings[$id] = $resolver; }
    public function get(string $id): mixed { return ($this->bindings[$id])($this); }
}
