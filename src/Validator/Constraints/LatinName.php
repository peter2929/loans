<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute]
class LatinName extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Sequentially([
                new Assert\NotBlank,
                new Assert\Length(min: 2, max: 32),
                new Assert\Regex(pattern: '/^[A-Za-z]+$/', message: 'Latin letters only')
            ])
        ];
    }
}
