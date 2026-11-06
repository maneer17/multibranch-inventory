<?php

namespace App\Casts;

use BcMath\Number;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class AsDecimal implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Number
    {
        // Preserve null as null rather than guessing it means zero. For a
        // NOT NULL column, a null here means something upstream is already
        // broken, and that should surface, not be silently papered over.
        // For a genuinely nullable column (reorder_point, reorder_qty),
        // null means "not set", a different fact than "set to zero".
        if ($value === null) {
            return null;
        }

        return new Number($value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        // Guard against a raw PHP float reaching Number's constructor.
        //
        // Number::__construct only declares (string|int $num). A float
        // isn't in that union, so PHP coerces it in non-strict mode —
        // and it tries `int` before `string`, converting via (int) cast,
        // which TRUNCATES rather than rounds, and does so SILENTLY: no
        // exception, just a quiet deprecation notice almost nobody has
        // logging configured to surface.
        //
        // Confirmed directly in Tinker on this project:
        //   new Number(50.5)   -> value: "50"   (the .5 is just gone)
        //   new Number('50.5') -> value: "50.5"  (correct, string is an
        //                                         exact match, no coercion)
        //
        // This bug class is common enough to be independently rediscovered
        // across unrelated ORMs (silent int-inferred-from-number columns,
        // numeric-to-float coercion on read) — the shared lesson every one
        // of those projects reached the hard way: a loud failure here is
        // far cheaper than a quiet wrong number sitting in the ledger.
        if (is_float($value)) {
            throw new \InvalidArgumentException(
                "Decimal attribute [{$key}] on ".$model::class." received a raw PHP float ({$value}), ".
                'which silently loses precision when cast to a Number. Pass a numeric string '.
                "(e.g. '{$value}') or an already-built BcMath\\Number instance instead."
            );
        }

        $number = $value instanceof Number ? $value : new Number($value);

        return (string) $number;
    }
}
