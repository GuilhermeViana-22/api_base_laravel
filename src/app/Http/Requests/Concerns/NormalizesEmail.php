<?php

namespace App\Http\Requests\Concerns;

/** E-mail sempre sem espaços e em minúsculas, antes de validar e consultar. */
trait NormalizesEmail
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }
}
