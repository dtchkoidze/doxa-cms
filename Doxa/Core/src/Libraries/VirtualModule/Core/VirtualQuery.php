<?php

namespace Doxa\Core\Libraries\VirtualModule\Core;

use Illuminate\Support\Facades\DB;

class VirtualQuery
{
    protected VirtualModel $model;
    protected array $wheres = [];
    protected ?int $limit = null;

    public function __construct(VirtualModel $model)
    {
        $this->model = $model;
    }

    public function where(string $column, mixed $value): static
    {
        $this->wheres[] = [$column, '=', $value];
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function get(): array
    {
        $query = DB::table($this->model->getTable());

        foreach ($this->wheres as [$col, $op, $val]) {
            $query->where($col, $op, $val);
        }

        if ($this->limit !== null) {
            $query->limit($this->limit);
        }

        $rows = $query->get()->toArray();

        return array_map(fn($row) => (clone $this->model)->fill((array) $row), $rows);
    }

    public function first(): ?VirtualModel
    {
        return $this->limit(1)->get()[0] ?? null;
    }
}
