<?php

namespace App\View\Components\Form;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Input extends Component
{
    public function __construct(
        public string $name,
        public ?string $label = null,
        public string $type = 'text',
        public mixed $value = null,
        public ?string $id = null,
        public ?string $placeholder = null,
        public bool $required = false,
        public bool $readonly = false,
        public ?string $hint = null,
        public ?int $min = null,
        public ?int $max = null,
        public ?string $step = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.form.input');
    }
}