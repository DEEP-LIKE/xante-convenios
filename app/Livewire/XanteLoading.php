<?php

namespace App\Livewire;

use Livewire\Component;

class XanteLoading extends Component
{
    public bool $show = false;

    public string $message = 'Generando documentos...';

    public string $size = 'lg';

    protected $listeners = [
        'showLoading' => 'showLoading',
        'hideLoading' => 'hideLoading',
        'updateLoadingMessage' => 'updateMessage',
    ];

    public function showLoading($message = null)
    {
        $this->show = true;
        if ($message) {
            $this->message = $message;
        }
    }

    public function hideLoading()
    {
        $this->show = false;
    }

    public function updateMessage($message)
    {
        $this->message = $message;
    }

    public function render()
    {
        return view('components.xante-loading', [
            'show' => $this->show,
            'message' => $this->message,
            'size' => $this->size,
        ]);
    }
}
