<?php

declare(strict_types=1);

use App\Models\PresentationFont;
use App\Models\PresentationProfile;
use App\Models\User;
use App\Services\Presentation\PresentationDisplayConfigService;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake(PresentationFont::DISK);
});

function fontMediaUser(): User
{
    return User::factory()->create(['role' => User::ROLE_MEDIA]);
}

/**
 * A canonical live config to build stored test configs from.
 *
 * @return array<string, mixed>
 */
function fontBaseConfig(): array
{
    return app(PresentationDisplayConfigService::class)->defaults();
}

function fakeFontFile(string $name = 'Poppins-Bold.woff2', string $magic = 'wOF2'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $magic.str_repeat("\x00\x01", 64));
}

function storedFont(string $name = 'Poppins'): PresentationFont
{
    $path = 'fonts/'.md5($name).'.woff2';
    Storage::disk(PresentationFont::DISK)->put($path, 'wOF2'.str_repeat("\x00", 16));

    return PresentationFont::query()->create([
        'name' => $name,
        'file_path' => $path,
        'format' => 'woff2',
        'file_size' => 20,
    ]);
}

function liveTypography(array $typography): void
{
    app(SettingsService::class)->set(
        PresentationDisplayConfigService::LIVE_SETTINGS_KEY,
        array_merge(fontBaseConfig(), ['typography' => $typography]),
    );
}

it('shows the font library to media users only', function (): void {
    $this->actingAs(fontMediaUser())
        ->get(route('media.kawalan.fon.index'))
        ->assertOk()
        ->assertSee(__('Pustaka Fon'));

    $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
        ->get(route('media.kawalan.fon.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['role' => User::ROLE_USER]))
        ->get(route('media.kawalan.fon.index'))
        ->assertForbidden();
});

it('renders library fonts as @font-face rules on the library page', function (): void {
    $font = storedFont('Poppins');

    $this->actingAs(fontMediaUser())
        ->get(route('media.kawalan.fon.index'))
        ->assertOk()
        ->assertSee('font-family:"apcfont-'.$font->id.'"', false)
        ->assertSee('format("woff2")', false);
});

it('uploads a font and detects its format from the file signature', function (): void {
    $this->actingAs(fontMediaUser())
        ->post(route('media.kawalan.fon.store'), [
            'name' => 'Poppins Bold',
            'font_file' => fakeFontFile(),
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('font.name', 'Poppins Bold')
        ->assertJsonPath('font.format', 'woff2');

    $font = PresentationFont::query()->sole();

    expect($font->format)->toBe('woff2')
        ->and($font->file_path)->toStartWith('fonts/')
        ->and($font->file_path)->toEndWith('.woff2');
    Storage::disk(PresentationFont::DISK)->assertExists($font->file_path);
});

it('detects truetype and opentype signatures', function (string $file, string $magic, string $format): void {
    $this->actingAs(fontMediaUser())
        ->post(route('media.kawalan.fon.store'), [
            'name' => 'Fon '.$format,
            'font_file' => fakeFontFile($file, $magic),
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('font.format', $format);
})->with([
    'ttf' => ['Roboto.ttf', "\x00\x01\x00\x00", 'truetype'],
    'otf' => ['Lato.otf', 'OTTO', 'opentype'],
    'woff' => ['Lato.woff', 'wOFF', 'woff'],
]);

it('rejects a file that is not really a font', function (): void {
    $this->actingAs(fontMediaUser())
        ->post(route('media.kawalan.fon.store'), [
            'name' => 'Palsu',
            'font_file' => UploadedFile::fake()->createWithContent('palsu.ttf', '<?php echo "hi";'),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('font_file');

    expect(PresentationFont::query()->count())->toBe(0);
    expect(Storage::disk(PresentationFont::DISK)->allFiles())->toBe([]);
});

it('rejects a wrong extension and a duplicate name', function (): void {
    storedFont('Poppins');

    $this->actingAs(fontMediaUser())
        ->post(route('media.kawalan.fon.store'), [
            'name' => 'Poppins',
            'font_file' => UploadedFile::fake()->createWithContent('gambar.png', 'wOF2abcd'),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'font_file']);
});

it('stops uploads once the library limit is reached', function (): void {
    foreach (range(1, PresentationFont::MAX_FONTS) as $i) {
        storedFont('Fon '.$i);
    }

    $this->actingAs(fontMediaUser())
        ->post(route('media.kawalan.fon.store'), [
            'name' => 'Satu Lagi',
            'font_file' => fakeFontFile(),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('font_file');

    expect(PresentationFont::query()->count())->toBe(PresentationFont::MAX_FONTS);
});

it('renames a font without touching its file', function (): void {
    $font = storedFont('Poppins');

    $this->actingAs(fontMediaUser())
        ->put(route('media.kawalan.fon.update', $font), ['name' => 'Poppins Tajuk'], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('font.name', 'Poppins Tajuk');

    expect($font->fresh()->file_path)->toBe($font->file_path);
    Storage::disk(PresentationFont::DISK)->assertExists($font->file_path);
});

it('replaces a font file and removes the old one', function (): void {
    $font = storedFont('Poppins');
    $oldPath = $font->file_path;

    $this->actingAs(fontMediaUser())
        ->post(route('media.kawalan.fon.update', $font), [
            '_method' => 'PUT',
            'name' => 'Poppins',
            'font_file' => fakeFontFile('Poppins.ttf', "\x00\x01\x00\x00"),
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('font.format', 'truetype');

    $font->refresh();
    expect($font->file_path)->not->toBe($oldPath)
        ->and($font->file_path)->toEndWith('.ttf');
    Storage::disk(PresentationFont::DISK)->assertMissing($oldPath);
    Storage::disk(PresentationFont::DISK)->assertExists($font->file_path);
});

it('streams the font file with a font content type and long cache headers', function (): void {
    $font = storedFont('Poppins');

    $response = $this->actingAs(fontMediaUser())
        ->get(route('media.fonts.file', $font))
        ->assertOk()
        ->assertHeader('Content-Type', 'font/woff2')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Cache-Control'))->toContain('max-age=31536000');
});

it('lists where a font is used', function (): void {
    $font = storedFont('Poppins');
    liveTypography(['jawatan' => ['font_id' => $font->id], 'ptj' => ['font_id' => $font->id]]);
    PresentationProfile::query()->create([
        'name' => 'Majlis Pagi',
        'config' => array_merge(fontBaseConfig(), ['typography' => ['name' => ['font_id' => $font->id]]]),
    ]);

    $response = $this->actingAs(fontMediaUser())
        ->getJson(route('media.kawalan.fon.list'))
        ->assertOk()
        ->assertJsonPath('fonts.0.usage.profiles', ['Majlis Pagi']);

    expect($response->json('fonts.0.usage.parts'))->toHaveCount(2)
        ->and($response->json('font_face_css'))->toContain('apcfont-'.$font->id);
});

it('deletes a font and falls back to the default font in the live config and profiles', function (): void {
    $font = storedFont('Poppins');
    $other = storedFont('Lato');
    liveTypography([
        'jawatan' => ['font_id' => $font->id, 'weight' => 700, 'italic' => true, 'tracking' => 'wide'],
        'ptj' => ['font_id' => $other->id],
    ]);
    $profile = PresentationProfile::query()->create([
        'name' => 'Majlis Pagi',
        'config' => array_merge(fontBaseConfig(), ['typography' => ['name' => ['font_id' => $font->id]]]),
    ]);

    $this->actingAs(fontMediaUser())
        ->deleteJson(route('media.kawalan.fon.destroy', $font))
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('presentation_fonts', ['id' => $font->id]);
    Storage::disk(PresentationFont::DISK)->assertMissing($font->file_path);
    Storage::disk(PresentationFont::DISK)->assertExists($other->file_path);

    $live = app(PresentationDisplayConfigService::class)->live();
    expect($live['typography']['jawatan'])->toBe(['font_id' => null, 'weight' => 700, 'italic' => true, 'tracking' => 'wide'])
        ->and($live['typography']['ptj']['font_id'])->toBe($other->id);

    $stored = app(SettingsService::class)->get(PresentationDisplayConfigService::LIVE_SETTINGS_KEY);
    expect($stored['typography']['jawatan']['font_id'])->toBeNull();

    expect($profile->fresh()->config['typography']['name']['font_id'])->toBeNull();
});
