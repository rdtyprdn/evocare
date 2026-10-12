<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_upload_avatar(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('foto.jpg', 200, 200)])
            ->assertSessionHasNoErrors()->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee($user->avatarUrl());
    }

    public function test_replacing_avatar_deletes_old_file(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('a.png')]);
        $old = $user->refresh()->avatar_path;

        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('b.webp')]);
        $new = $user->refresh()->avatar_path;

        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }

    public function test_user_can_remove_avatar(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('a.jpg')]);
        $path = $user->refresh()->avatar_path;

        $this->actingAs($user)->delete(route('profile.avatar.destroy'))->assertRedirect();
        $this->assertNull($user->refresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_non_image_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors('avatar');
        $this->assertNull($user->refresh()->avatar_path);
    }

    public function test_too_large_image_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('besar.jpg')->size(3000)])
            ->assertSessionHasErrors('avatar');
    }

    public function test_initials_fallback_shown_without_avatar(): void
    {
        $user = User::factory()->create(['name' => 'Raditya Pradana']);
        $this->actingAs($user)->get(route('profile.edit'))->assertSee('RP');
    }

    public function test_post_too_large_redirects_back_with_indonesian_error(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->call('POST', route('profile.avatar.update'), [], [], [], [
                'CONTENT_LENGTH' => 19 * 1024 * 1024,
                'HTTP_REFERER' => route('profile.edit'),
            ])
            ->assertRedirect(route('profile.edit').'?upload_error=too_large');

        $this->actingAs($user)->get(route('profile.edit').'?upload_error=too_large')
            ->assertOk()->assertSee('Ukuran file terlalu besar. Maksimal 2 MB.');
        $this->assertNull($user->refresh()->avatar_path);
    }

    public function test_post_too_large_json_returns_413(): void
    {
        $this->actingAs(User::factory()->create())
            ->call('POST', route('profile.avatar.update'), [], [], [], ['CONTENT_LENGTH' => 19 * 1024 * 1024, 'HTTP_ACCEPT' => 'application/json'])
            ->assertStatus(413)->assertJson(['message' => 'Ukuran file terlalu besar. Maksimal 2 MB.']);
    }

    public function test_success_after_too_large_error_shows_no_error(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->call('POST', route('profile.avatar.update'), [], [], [], [
            'CONTENT_LENGTH' => 19 * 1024 * 1024, 'HTTP_REFERER' => route('profile.edit'),
        ]);

        $this->actingAs($user)
            ->from(route('profile.edit').'?upload_error=too_large')
            ->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('ok.jpg')])
            ->assertRedirect(route('profile.edit'));

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertSee('Foto profil diperbarui.')->assertSee('error: null', false); // error server tidak di-set

        // Walau URL masih membawa query, flash sukses menyembunyikan error.
        $this->actingAs($user)->withSession(['status' => 'avatar-updated'])
            ->get(route('profile.edit').'?upload_error=too_large')->assertSee('error: null', false);
    }

    public function test_avatar_form_action_has_no_query_string(): void
    {
        $this->actingAs(User::factory()->create())->get(route('profile.edit').'?upload_error=too_large')
            ->assertSee('action="'.route('profile.avatar.update').'"', false);
    }

    public function test_avatar_actions_redirect_plainly_and_profile_name_has_no_autofocus(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('a.jpg')])
            ->assertRedirect(route('profile.edit'));
        $this->actingAs($user)->delete(route('profile.avatar.destroy'))->assertRedirect(route('profile.edit'));

        $html = $this->actingAs($user)->get(route('profile.edit'))->assertSee('id="foto-profil"', false)->getContent();
        $this->assertDoesNotMatchRegularExpression('/id="name"[^>]*autofocus/', $html);
    }
}
