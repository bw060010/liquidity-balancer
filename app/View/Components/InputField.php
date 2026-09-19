<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InputField extends Component
{
    public function __construct(
        public string $name,
        public string $label,
        public string $value = '',
        public bool $readonly = false,
        public string $pattern = '\d+(\.\d+)?',
        public ?string $id = null,
    ) {
        $this->id = $id ?? $name;
    }

    public function render(): View|Closure|string
    {
        return view('components.input-field');
    }
}
