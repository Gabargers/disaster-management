<?php

namespace App\View\Components\Form;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Collection;

class Select extends Component
{
    public function __construct(
        public string $name,
        public ?string $label = null,
        public array|Collection $options = [],
        public mixed $selected = null,
        public ?string $id = null,
        public bool $required = false,
        public ?string $placeholder = null,
        public bool $useSelect2 = true,
        public ?string $dropdownParent = null,
        public ?string $hint = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.form.select');
    }
}