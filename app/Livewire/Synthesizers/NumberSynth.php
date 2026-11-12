<?php

namespace App\Livewire\Synthesizers;

use BcMath\Number;
use Livewire\Mechanisms\HandleComponents\Synthesizers\Synth;

class NumberSynth extends Synth
{
    public static string $key = 'bcnumber';

    public static function match($target): bool
    {
        return $target instanceof Number;
    }

    public function dehydrate($target)
    {
        return [$target->value, []];
    }

    public function hydrate($value)
    {
        return new Number($value);
    }
}
