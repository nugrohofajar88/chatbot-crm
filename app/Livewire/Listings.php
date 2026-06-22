<?php

namespace App\Livewire;

use App\Models\Listing;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * CRUD katalog produk (generalisasi dari listing properti). Mendukung banyak
 * media per produk: gambar & file (PDF).
 */
#[Layout('components.layouts.app')]
class Listings extends Component
{
    use WithFileUploads;

    public bool $showForm = false;
    public ?int $editingId = null;

    public ?int $confirmingDeleteId = null;
    public string $confirmingDeleteName = '';

    public string $name = '';
    public string $category = '';
    public int $price = 0;
    public int $stock = 0;
    public string $description = '';
    public string $status = 'tersedia';

    /** File baru yang diunggah (sementara) sebelum disimpan. */
    public array $newFiles = [];

    /** Media tersimpan: [{path,name,ext,is_image}]. */
    public array $media = [];

    public string $search = '';
    public string $toast = '';

    #[Computed]
    public function items()
    {
        return Listing::query()
            ->when($this->search !== '', function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('category', 'like', '%'.$this->search.'%');
            })
            ->orderBy('name')
            ->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $l = Listing::findOrFail($id);
        $this->editingId = $l->id;
        $this->name = $l->name;
        $this->category = (string) $l->category;
        $this->price = (int) $l->price;
        $this->stock = (int) $l->stock;
        $this->description = (string) $l->description;
        $this->status = $l->status ?: 'tersedia';
        $this->media = $l->media ?? [];
        $this->newFiles = [];
        $this->showForm = true;
    }

    /** Hapus 1 media tersimpan (di form, sebelum/saat edit). */
    public function removeMedia(int $index): void
    {
        if (isset($this->media[$index])) {
            $this->deleteFile($this->media[$index]['path'] ?? null);
            unset($this->media[$index]);
            $this->media = array_values($this->media);
        }
    }

    /** Batalkan 1 file yang baru diunggah (belum disimpan). */
    public function removeNewFile(int $index): void
    {
        if (isset($this->newFiles[$index])) {
            unset($this->newFiles[$index]);
            $this->newFiles = array_values($this->newFiles);
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'price' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:tersedia,habis,nonaktif',
            'newFiles.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:8192',
        ]);

        // Simpan file baru ke media.
        foreach ($this->newFiles as $file) {
            $path = $file->store('listings', 'public_uploads');
            $ext = strtolower($file->getClientOriginalExtension());
            $this->media[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'ext' => $ext,
                'is_image' => in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true),
            ];
        }

        unset($data['newFiles']);
        $data['media'] = $this->media;

        $listing = $this->editingId ? Listing::findOrFail($this->editingId) : new Listing;
        $listing->fill($data)->save();

        $this->showForm = false;
        $this->resetForm();
        $this->toast = 'Produk disimpan';
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
        $this->confirmingDeleteName = Listing::find($id)?->name ?? '';
    }

    public function delete(int $id): void
    {
        $l = Listing::findOrFail($id);
        foreach ($l->media ?? [] as $m) {
            $this->deleteFile($m['path'] ?? null);
        }
        $l->delete();
        $this->confirmingDeleteId = null;
        $this->confirmingDeleteName = '';
        $this->toast = 'Produk dihapus';
    }

    protected function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public_uploads')->delete($path);
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'category', 'price', 'stock', 'description', 'newFiles', 'media']);
        $this->status = 'tersedia';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.listings');
    }
}
