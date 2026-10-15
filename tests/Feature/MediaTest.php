<?php

namespace Tests\Feature;

use App\Support\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    public function test_an_upload_is_stored_as_webp_at_its_own_size(): void
    {
        Storage::fake('public');

        $path = Media::store(UploadedFile::fake()->image('foto.jpg', 800, 600), 'berita');

        $this->assertStringStartsWith('berita/', $path);
        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame([800, 600, 'image/webp'], $this->dimensions($path));
    }

    public function test_an_image_larger_than_1920_pixels_is_scaled_down_on_its_longest_side(): void
    {
        Storage::fake('public');

        $path = Media::store(UploadedFile::fake()->image('hero.jpg', 3000, 1500), 'slider');

        $this->assertSame([1920, 960, 'image/webp'], $this->dimensions($path));
    }

    public function test_a_smaller_limit_scales_the_image_to_that_limit(): void
    {
        Storage::fake('public');

        $path = Media::store(UploadedFile::fake()->image('logo.png', 1200, 900), 'situs', maxSide: 512);

        $this->assertSame([512, 384, 'image/webp'], $this->dimensions($path));
    }

    public function test_transparent_pixels_stay_transparent(): void
    {
        Storage::fake('public');
        $image = imagecreatetruecolor(40, 40);
        imagealphablending($image, false);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagesavealpha($image, true);
        ob_start();
        imagepng($image);
        $png = ob_get_clean();

        $path = Media::store(UploadedFile::fake()->createWithContent('logo.png', $png), 'situs');

        $stored = imagecreatefromstring(Storage::disk('public')->get($path));
        $this->assertSame(127, imagecolorsforindex($stored, imagecolorat($stored, 20, 20))['alpha']);
    }

    public function test_a_jpeg_rotated_by_its_camera_is_stored_upright(): void
    {
        Storage::fake('public');
        $image = imagecreatetruecolor(400, 200);
        ob_start();
        imagejpeg($image);
        $jpeg = ob_get_clean();
        // EXIF block (big-endian) holding one tag: Orientation = 6, "rotate 90 degrees clockwise to view".
        $exif = "Exif\0\0MM\0\x2A\0\0\0\x08\0\x01\x01\x12\0\x03\0\0\0\x01\0\x06\0\0\0\0\0\0";
        $jpeg = substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);

        $path = Media::store(UploadedFile::fake()->createWithContent('ponsel.jpg', $jpeg), 'galeri');

        $this->assertSame([200, 400, 'image/webp'], $this->dimensions($path));
    }

    public function test_a_webp_that_would_grow_when_compressed_is_kept_as_uploaded(): void
    {
        Storage::fake('public');
        $image = imagecreatetruecolor(300, 200);
        for ($x = 0; $x < 300; $x++) {
            for ($y = 0; $y < 200; $y++) {
                imagesetpixel($image, $x, $y, (($x * 7) ^ ($y * 13)) & 0xFFFFFF);
            }
        }
        ob_start();
        imagewebp($image, null, 10);
        $webp = ob_get_clean();

        $path = Media::store(UploadedFile::fake()->createWithContent('kecil.webp', $webp), 'galeri');

        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame($webp, Storage::disk('public')->get($path));
    }

    public function test_a_file_that_is_not_an_image_is_stored_as_uploaded(): void
    {
        Storage::fake('public');

        $path = Media::store(UploadedFile::fake()->createWithContent('rusak.jpg', 'bukan gambar'), 'berita');

        $this->assertStringEndsNotWith('.webp', $path);
        $this->assertSame('bukan gambar', Storage::disk('public')->get($path));
    }

    public function test_a_replaced_upload_is_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('berita/lama.jpg', 'gambar lama');

        $path = Media::store(UploadedFile::fake()->image('baru.jpg', 640, 360), 'berita', 'berita/lama.jpg');

        Storage::disk('public')->assertMissing('berita/lama.jpg');
        Storage::disk('public')->assertExists($path);
    }

    /**
     * @return array{0: int, 1: int, 2: string}
     */
    private function dimensions(string $path): array
    {
        $info = getimagesizefromstring(Storage::disk('public')->get($path));

        return [$info[0], $info[1], $info['mime']];
    }
}
