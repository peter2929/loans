<?php
namespace App\Entity;


abstract class LoanEntity
{
    public function updateFields($values): void
    {
        $writableFields = array_flip(static::WRITABLE);
        foreach($values as $field => $value) {
            if(!isset($writableFields[$field])) {
                continue;
            }

            $setter = 'set' . ucfirst($field);
            $this->$setter($value);
        }
    }
}