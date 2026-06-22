<?php

namespace App\Livewire;

use App\Models\Listing;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * CRUD katalog produk (generalisasi dari listing properti).
 */
#[Layout('components.layouts.app')]
class Listings extends Component
{
    use WithFileUploads;

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $category = '';
    public int $price = 0;
    public int $stock = 0;
    public string $description = '';
    public string $status = 'tersedia';
    public $image = null;
    public ?string $existingImage = null;
    public bool $removeImage = false;

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
        $this->existingImage = $l->imageUrl();
        $this->image = null;
        $this->removeImage = false;
        $this->showForm = true;
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
            'image' => 'nullable|image|max:8192',
        ]);

        $listing = $this->editingId ? Listing::findOrFail($this->editingId) : new Listing;

        if ($this->image) {
            $this->deleteImage($listing->image_path);
            $data['image_path'] = $this->image->store('listings', 'public_uploads');
        } elseif ($this->removeImage) {
            $this->deleteImage($listing->image_path);
            $data['image_path'] = null;
        }
        unset($data['image']);

        $listing->fill($data)->save();

        $this->showForm = false;
        $this->resetForm();
        $this->toast = 'Produk disimpan';
    }

    public function delete(int $id): void
    {
        $l = Listing::findOrFail($id);
        $this->deleteImage($l->image_path);
        $l->delete();
        $this->toast = 'Produk dihapus';
    }

    protected function deleteImage(?string $path): void
    {
        if ($path) {
            Storage::disk('public_uploads')->delete($path);
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'category', 'price', 'stock', 'description', 'image', 'existingImage', 'removeImage']);
        $this->status = 'tersedia';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.listings');
    }
}
