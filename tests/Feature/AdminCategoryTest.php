<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_index_shows_new_ui(): void
    {
        Category::factory()->create(['name' => 'Lingkungan']);
        $this->actingAs($this->admin())->get(route('admin.categories.index'))->assertOk()
            ->assertSee('Kelola kategori kegiatan yang tampil saat penyelenggara membuat kegiatan.')
            ->assertSee('Lingkungan')->assertSee('Ubah')->assertSee('(0 kegiatan)', false);
    }

    public function test_admin_can_rename_and_slug_stays(): void
    {
        $c = Category::factory()->create(['name' => 'Lingkungan', 'slug' => 'lingkungan']);
        $this->actingAs($this->admin())->put(route('admin.categories.update', $c), ['name' => 'Lingkungan Hidup'])->assertSessionHas('success');
        $this->assertSame('Lingkungan Hidup', $c->fresh()->name);
        $this->assertSame('lingkungan', $c->fresh()->slug);
    }

    public function test_duplicate_name_rejected(): void
    {
        Category::factory()->create(['name' => 'Pendidikan']);
        $c = Category::factory()->create(['name' => 'Kesehatan']);
        $admin = $this->admin();
        $this->actingAs($admin)->put(route('admin.categories.update', $c), ['name' => 'Pendidikan'])->assertSessionHasErrorsIn('rename'.$c->id, 'name');
        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Pendidikan'])->assertSessionHasErrors('name');
        $this->assertSame('Kesehatan', $c->fresh()->name);
    }

    public function test_non_admin_forbidden(): void
    {
        $c = Category::factory()->create();
        $u = User::factory()->create();
        $this->actingAs($u)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($u)->put(route('admin.categories.update', $c), ['name' => 'X'])->assertForbidden();
        $this->actingAs($u)->delete(route('admin.categories.destroy', $c))->assertForbidden();
    }

    public function test_in_use_delete_blocked_and_unused_delete_works(): void
    {
        $used = Event::factory()->create()->category;
        $unused = Category::factory()->create();
        $admin = $this->admin();
        $this->actingAs($admin)->delete(route('admin.categories.destroy', $used))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'masih dipakai oleh 1 kegiatan'));
        $this->assertDatabaseHas('categories', ['id' => $used->id]);
        $this->actingAs($admin)->delete(route('admin.categories.destroy', $unused))->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $unused->id]);
    }
}
