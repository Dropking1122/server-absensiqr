<?php

namespace App\Livewire\Rilis;

use App\Models\Release;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;
use Livewire\Component;

class Index extends Component
{
    public bool $showForm  = false;
    public bool $editMode  = false;
    public ?int $editId    = null;

    // Form fields
    public string $version    = '';
    public string $releasedAt = '';
    public string $channel    = 'stable';
    public string $category   = 'feature';
    public bool   $mandatory  = false;
    public string $minVersion = '';
    public string $title      = '';
    public string $notes      = '';
    public string $notesPreview = '';

    protected $rules = [
        'version'    => ['required', 'string', 'max:20', 'regex:/^\d+\.\d+\.\d+$/'],
        'releasedAt' => ['required', 'date'],
        'channel'    => ['required', 'in:stable,beta'],
        'category'   => ['required', 'in:feature,bugfix,security,hotfix'],
        'title'      => ['required', 'string', 'max:255'],
        'notes'      => ['required', 'string'],
        'mandatory'  => ['boolean'],
        'minVersion' => ['nullable', 'string', 'max:20'],
    ];

    protected $messages = [
        'version.regex' => 'Format versi harus SemVer, contoh: 1.2.0',
    ];

    public function updatedNotes(): void
    {
        $converter = new CommonMarkConverter(['html_input' => 'strip']);
        $this->notesPreview = $converter->convert($this->notes)->getContent();
    }

    public function openForm(): void
    {
        $this->resetForm();
        $this->releasedAt = now()->toDateString();
        $this->showForm   = true;
        $this->editMode   = false;
    }

    public function editRilis(int $id): void
    {
        $rilis = Release::findOrFail($id);
        $this->editId      = $id;
        $this->version     = $rilis->version;
        $this->releasedAt  = $rilis->released_at->toDateString();
        $this->channel     = $rilis->channel;
        $this->category    = $rilis->category;
        $this->mandatory   = $rilis->mandatory;
        $this->minVersion  = $rilis->min_version ?? '';
        $this->title       = $rilis->title;
        $this->notes       = $rilis->notes;
        $this->updatedNotes();
        $this->showForm    = true;
        $this->editMode    = true;
    }

    public function simpan(): void
    {
        if (! $this->editMode) {
            $this->rules['version'][] = 'unique:releases,version';
        }
        $validated = $this->validate();

        $data = [
            'released_at' => $validated['releasedAt'],
            'channel'     => $validated['channel'],
            'category'    => $validated['category'],
            'mandatory'   => $validated['mandatory'],
            'min_version' => $validated['minVersion'] ?: null,
            'title'       => $validated['title'],
            'notes'       => $validated['notes'],
        ];

        if ($this->editMode && $this->editId) {
            Release::findOrFail($this->editId)->update($data);
            session()->flash('success', 'Rilis berhasil diperbarui.');
        } else {
            Release::create(array_merge($data, ['version' => $validated['version']]));
            session()->flash('success', 'Rilis baru berhasil ditambahkan.');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function toggleAktif(int $id): void
    {
        $rilis = Release::findOrFail($id);
        $rilis->update(['is_active' => ! $rilis->is_active]);
    }

    public function tutupForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->version = $this->releasedAt = $this->minVersion = $this->title = $this->notes = $this->notesPreview = '';
        $this->channel = 'stable';
        $this->category = 'feature';
        $this->mandatory = false;
        $this->editId = null;
        $this->editMode = false;
        $this->resetValidation();
    }

    public function render()
    {
        $releases = Release::orderByDesc('released_at')->orderByDesc('id')->get();
        return view('livewire.rilis.index', compact('releases'))
            ->layout('layouts.app', ['header' => 'Manajemen Rilis', 'title' => 'Rilis']);
    }
}
