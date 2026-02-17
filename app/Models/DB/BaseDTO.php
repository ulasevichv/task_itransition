<?php
namespace App\Models\DB;

abstract readonly class BaseDTO
{
    /**
     * @param array<string|\Closure> $fieldNamesOrClosures
     * @return array
     * @throws \Exception
     */
    public function toArray(array $fieldNamesOrClosures): array
    {
        $result = [];
        foreach ($fieldNamesOrClosures as $fieldNameOrClosure) {
            if (is_string($fieldNameOrClosure)) {
                $result[] = $this->{$fieldNameOrClosure};
            } elseif (is_callable($fieldNameOrClosure)) {
                $result[] = $fieldNameOrClosure($this);
            } else {
                throw new \Exception(sprintf("Invalid parameter type: %s", gettype($fieldNameOrClosure)));
            }
        }
        return $result;
    }
}