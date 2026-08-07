<?php

namespace App\Livewire;

use App\Models\AuditLog;
use Livewire\Component;

class AuditLogs extends Component
{
    public string $search = '';

    public function render()
    {
        $logs = AuditLog::query()
            ->with('user:id,name,email,role,profile_photo_path')
            ->when($this->search !== '', fn ($q) => $q
                ->where('action', 'like', '%'.$this->search.'%')
                ->orWhere('description', 'like', '%'.$this->search.'%')
                ->orWhereHas('user', fn ($uq) => $uq
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')))
            ->latest()
            ->limit(500)
            ->get();

        return view('livewire.audit-logs', [
            'logs' => $logs,
        ])->layout('layouts.app');
    }
}
