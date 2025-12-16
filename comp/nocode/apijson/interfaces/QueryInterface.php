<?php

namespace Imee\Comp\Nocode\Apijson\Interfaces;

interface QueryInterface
{
    public function all(): array;
    public function count($column = '*'): int;
    public function toSql(): string;
    public function insert(array $values, $sequence = null): int;
    public function update(array $values): bool;
    public function delete($id = null): bool;
    public function replace(array $values, $sequence = null): int;
    public function getBindings(): array;
    public function getWhereKeys(): array;
    public function setColumns(string $columns): void;
    public function getRowCount(): int;
    public function exists(array $where): bool;
    public function getPrimaryKey(): string;
    public function setPrimaryKey(string $primaryKey): void;
    public function getTableName(): string;
}