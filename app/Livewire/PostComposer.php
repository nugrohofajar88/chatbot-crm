<?php

namespace App\Livewire;

use App\Models\Post;
use App\Support\ContentPublisher;
use App\Support\ImageGenerator;
use App\Support\PostWriter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Komposer postingan AI untuk Facebook Page & Instagram.
 * Modul TERPISAH dari inbox/chat: prompt -> caption AI -> (gambar) -> publish.
 */
#[Layout('components.layouts.app')]
class PostComposer extends Component
{
    use WithFileUploads;
    use WithPagination;

    /** Tab aktif: form | history. */
    public string $tab = 'form';

    public string $prompt = '';

    public string $caption = '';

    public $image = null;

    public string $imagePrompt = '';

    /** Path relatif gambar hasil AI (di public/uploads). */
    public ?string $generatedImagePath = null;

    /** Pesan error generate gambar (ditampilkan di bawah prompt). */
    public string $imageError = '';

    /** @var array<int,string> */
    public array $platforms = ['facebook'];

    public string $busy = '';   // '', 'generate', 'image', 'publish'

    public string $toast = '';

    /** Buat caption dari prompt via AI (PostWriter, bukan persona chat). */
    public function generate(): void
    {
        if (trim($this->prompt) === '') {
            return;
        }

        $this->busy = 'generate';
        $this->caption = PostWriter::generate($this->prompt);
        $this->busy = '';

        $this->toast = $this->caption !== '' ? 'Caption dibuat — silakan edit bila perlu' : 'AI sedang sibuk, coba lagi';
    }

    /** Generate gambar via AI (provider/model diatur di /configuration). */
    public function generateImage(): void
    {
        $this->imageError = '';

        if (trim($this->imagePrompt) === '') {
            return;
        }

        $this->busy = 'image';
        $result = ImageGenerator::generate($this->imagePrompt);
        $this->busy = '';

        if ($result['ok']) {
            $this->generatedImagePath = (string) $result['path'];
            $this->image = null;   // pakai gambar AI, kosongkan upload manual
            $this->toast = 'Gambar AI dibuat';
        } else {
            $this->imageError = $result['error'] ?? 'Gagal generate gambar — cek provider/model di /configuration';
        }
    }

    public function publish(ContentPublisher $publisher): void
    {
        $caption = trim($this->caption);

        if ($caption === '' || $this->platforms === []) {
            $this->toast = 'Caption & minimal satu platform wajib diisi';

            return;
        }

        // Gambar (opsional untuk FB, WAJIB untuk IG).
        $imagePath = null;
        $imageUrl = null;
        if ($this->image) {
            $this->validate(['image' => 'image|max:10240']);
            $imagePath = $this->image->store('posts', 'public_uploads');
            $imageUrl = url('uploads/'.$imagePath);
        } elseif ($this->generatedImagePath) {
            $imagePath = $this->generatedImagePath;
            $imageUrl = url('uploads/'.$imagePath);
        }

        if (in_array('instagram', $this->platforms, true) && ! $imageUrl) {
            $this->toast = 'Instagram wajib menyertakan gambar';

            return;
        }

        $this->busy = 'publish';

        $result = [];
        if (in_array('facebook', $this->platforms, true)) {
            $result['facebook'] = $publisher->publishFacebook($caption, $imageUrl);
        }
        if (in_array('instagram', $this->platforms, true)) {
            $result['instagram'] = $publisher->publishInstagram($caption, $imageUrl);
        }

        $this->busy = '';

        $oks = collect($result)->filter(fn ($r) => $r['ok'] ?? false)->count();
        $status = $oks === count($result) ? 'published' : ($oks > 0 ? 'partial' : 'failed');

        Post::create([
            'prompt' => $this->prompt,
            'caption' => $caption,
            'image_path' => $imagePath,
            'platforms' => array_values($this->platforms),
            'status' => $status,
            'fb_post_id' => $result['facebook']['id'] ?? null,
            'ig_post_id' => $result['instagram']['id'] ?? null,
            'result' => $result,
        ]);

        unset($this->posts);
        $this->reset(['prompt', 'caption', 'image', 'imagePrompt', 'generatedImagePath', 'imageError']);
        $this->platforms = ['facebook'];
        $this->tab = 'history';
        $this->resetPage();

        $this->toast = match ($status) {
            'published' => 'Berhasil diposting 🎉',
            'partial' => 'Sebagian terposting — cek riwayat di bawah',
            default => 'Gagal posting — cek detail di riwayat',
        };
    }

    #[Computed]
    public function posts()
    {
        return Post::latest()->paginate(6);
    }

    public function render()
    {
        return view('livewire.post-composer');
    }
}
