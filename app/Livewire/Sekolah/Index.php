<?php

namespace App\Livewire\Sekolah;

use App\Models\Installation;
use App\Models\Release;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus  = '';
    public string $filterChannel = '';
    public string $filterUpdate  = '';

    protected $queryString = ['search', 'filterStatus', 'filterChannel', 'filterUpdate'];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterChannel(): void { $this->resetPage(); }
    public function updatingFilterUpdate(): void { $this->resetPage(); }

    public function render()
    {
        $latestRelease = Release::latestForChannel('stable');
        $latestVersion = $latestRelease?->version;
        $threshold     = (int) config('monitor.online_threshold_minutes', 90);

        $query = Installation::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('app_name', 'ilike', '%' . $this->search . '%')
                  ->orWhere('app_url', 'ilike', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus === 'online') {
            $query->where('last_seen_at', '>', now()->subMinutes($threshold));
        } elseif ($this->filterStatus === 'offline') {
            $query->where(function ($q) use ($threshold) {
                $q->where('last_seen_at', '<=', now()->subMinutes($threshold))
                  ->orWhereNull('last_seen_at');
            });
        } elseif ($this->filterStatus === 'offline_long') {
            $query->where('last_seen_at', '<', now()->subHours(48));
        }

        if ($this->filterChannel) {
            $query->where('update_channel', $this->filterChannel);
        }

        if ($this->filterUpdate === 'perlu' && $latestVersion) {
            $query->where('app_version', '<', $latestVersion)->whereNotNull('app_version');
        } elseif ($this->filterUpdate === 'terbaru' && $latestVersion) {
            $query->where('app_version', '>=', $latestVersion);
        }

        $installations = $query->orderByDesc('last_seen_at')->paginate(20);

        return view('livewire.sekolah.index', compact('installations', 'latestVersion'))
            ->layout('layouts.app', ['header' => 'Daftar Sekolah', 'title' => 'Sekolah']);
    }
}
