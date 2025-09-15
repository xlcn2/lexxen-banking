<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

abstract class BaseDTO
{
    /**
     * Create a new DTO instance from an array of data.
     */
    public static function fromArray(array $data)
    {
        return new static(...$data);
    }

    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Create a collection of DTOs from a collection of arrays.
     */
    public static function collection(Collection|array $items): Collection
    {
        $items = $items instanceof Collection ? $items : collect($items);
        
        return $items->map(fn ($item) => static::fromArray($item));
    }
}
