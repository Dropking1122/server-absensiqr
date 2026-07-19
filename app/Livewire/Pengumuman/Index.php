<?php

namespace App\Livewire\Pengumuman;

use App\Models\Announcement;
use Livewire\Component;

class Index extends Component
{
    public bool $showForm = false;
    public bool $editMode = false;
    public ?int $editId   = null;

    public string  $title         = '';
    public string  $message       = '';
    public string  $priority      = 'info';
    public string  $targetChannel = '';
    public string  $activeFrom    = '';
    public string  $activeUntil   = '';

    protected $rules = [
        'title'         => ['required', 'string', 'max:255'],
        'message'       => ['required', 'string'],
        'priority'      => ['required', 'in:info,warning,urgent'],
        'targetChannel' => ['nullable', 'in:,stable,beta'],
        'activeFrom'    => ['required', 'date'],
        'activeUntil'   => ['nullable', 'date', 'after:activeFrom'],
    ];

    protected $messages = [
        'activeUntil.after' => 'Berlaku hingga harus setelah berlaku dari.',
    ];

    public function openForm(): void
    {
        $this->resetForm();
        $this->activeFrom = now()->format('Y-m-d\TH:i');
        $this->showForm   = true;
        $this->editMode   = false;
    }

    public function editPengumuman(int $id): void
    {
        $a = Announcement::findOrFail($id);
        $this->editId        = $id;
        $this->title         = $a->title;
        $this->message       = $a->message;
        $this->priority      = $a->priority;
        $this->targetChannel = $a->target_channel ?? '';
        $this->activeFrom    = $a->active_from->format('Y-m-d\TH:i');
        $this->activeUntil   = $a->active_until?->format('Y-m-d\TH:i') ?? '';
        $this->showForm      = true;
        $this->editMode      = true;
    }

    public function simpan(): void
    {
        $validated = $this->validate();
        $data = [
            'title'          => $validated['title'],
            'message'        => $validated['message'],
            'priority'       => $validated['priority'],
            'target_channel' => $validated['targetChannel'] ?: null,
            'active_from'    => $validated['activeFrom'],
            'active_until'   => $validated['activeUntil'] ?: null,
            'is_active'      => true,
        ];

        if ($this->editMode && $this->editId) {
            Announcement::findOrFail($this->editId)->update($data);
            session()->flash('success', 'Pengumuman berhasil diperbarui.');
        } else {
            Announcement::create($data);
            session()->flash('success', 'Pengumuman berhasil ditambahkan.');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function toggleAktif(int $id): void
    {
        $a = Announcement::findOrFail($id);
        $a->update(['is_active' => ! $a->is_active]);
    }

    public function hapus(int $id): void
    {
        Announcement::findOrFail($id)->delete();
        session()->flash('success', 'Pengumuman dihapus.');
    }

    public function tutupForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->title = $this->message = $this->activeFrom = $this->activeUntil = '';
        $this->priority = 'info';
        $this->targetChannel = '';
        $this->editId = null;
        $this->editMode = false;
        $this->resetValidation();
    }

    public function render()
    {
        $pengumuman = Announcement::orderByDesc('created_at')->get();
        return view('livewire.pengumuman.index', compact('pengumuman'))
            ->layout('layouts.app', ['header' => 'Pengumuman', 'title' => 'Pengumuman']);
    }
}
