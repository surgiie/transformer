<?php

namespace Surgiie\Transformer\Tests;

use Illuminate\Http\Request;

class SampleFormRequest extends Request
{
    public function validated(): array
    {
        return $this->only(['first_name']);
    }
}
