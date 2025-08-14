<?php

namespace Doxa\Core\Libraries\VirtualModule\Core;

use Doxa\Core\Libraries\Package\Package;

abstract class VirtualModel
{
    protected array $attributes = [];
    protected array $original = [];
    protected ?Package $package = null;

    public function __construct()
    {
        $this->package = new Package($this->getModuleKey());
    }

    abstract public function getModuleKey(): string;

    public function getTable(): string
    {
        return $this->package->scheme->getTable();
    }

    public function getPrimaryKey(): string
    {
        return $this->package->scheme->getPrimaryKey();
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function fill(array $data): static
    {
        $fields = $this->package->scheme->getAllFieldKeys();
        foreach ($data as $key => $value) {
            if (in_array($key, $fields)) {
                $this->attributes[$key] = $value;
            }
        }
        $this->original = $this->attributes;
        return $this;
    }

    public function isDirty(): bool
    {
        return $this->attributes !== $this->original;
    }

    public function getDirty(): array
    {
        return array_diff_assoc($this->attributes, $this->original);
    }

    public static function query(): VirtualQuery
    {
        return new VirtualQuery(new static());
    }

    public static function find(int|string $id): ?static
    {
        return static::query()->where((new static())->getPrimaryKey(), $id)->first();
    }

    public function save(): bool
    {
        return VirtualSaver::save($this);
    }

    public static function create(array $data): static
    {
        $instance = (new static())->fill($data);
        $instance->save();
        return $instance;
    }

    public function getPackage(): Package
    {
        return $this->package;
    }
}



