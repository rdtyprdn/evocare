<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTypeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_default_types_are_seeded_by_migration(): void
    {
        $this->assertEqualsCanonicalizing(['lsm', 'komunitas', 'institusi'], OrganizationType::pluck('slug')->all());
        $this->assertSame('Institusi Pendidikan', (new Organization(['type' => 'institusi']))->typeLabel());
        $this->assertSame('Yayasan Sosial', (new Organization(['type' => 'yayasan-sosial']))->typeLabel());
    }

    public function test_admin_can_create_rename_and_delete_type(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.organization-types.index'))->assertOk()->assertSee('Jenis Organisasi')->assertSee('Komunitas');

        $this->actingAs($admin)->post(route('admin.organization-types.store'), ['name' => 'Yayasan Sosial'])->assertSessionHas('success');
        $type = OrganizationType::where('name', 'Yayasan Sosial')->firstOrFail();
        $this->assertSame('yayasan-sosial', $type->slug);

        $this->actingAs($admin)->put(route('admin.organization-types.update', $type), ['name' => 'Yayasan'])->assertSessionHas('success');
        $this->assertSame('Yayasan', $type->fresh()->name);
        $this->assertSame('yayasan-sosial', $type->fresh()->slug);

        $this->actingAs($admin)->post(route('admin.organization-types.store'), ['name' => 'Yayasan'])->assertSessionHasErrors('name');

        $this->actingAs($admin)->delete(route('admin.organization-types.destroy', $type))->assertSessionHas('success');
        $this->assertDatabaseMissing('organization_types', ['id' => $type->id]);
    }

    public function test_cannot_delete_type_in_use(): void
    {
        Organization::factory()->create(['type' => 'komunitas']);
        $type = OrganizationType::where('slug', 'komunitas')->firstOrFail();

        $this->actingAs($this->admin())->delete(route('admin.organization-types.destroy', $type))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'masih dipakai oleh 1 organisasi'));
        $this->assertDatabaseHas('organization_types', ['id' => $type->id]);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create();
        $type = OrganizationType::first();
        $this->actingAs($user)->get(route('admin.organization-types.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.organization-types.store'), ['name' => 'X'])->assertForbidden();
        $this->actingAs($user)->put(route('admin.organization-types.update', $type), ['name' => 'X'])->assertForbidden();
        $this->actingAs($user)->delete(route('admin.organization-types.destroy', $type))->assertForbidden();
    }

    public function test_new_type_appears_in_form_and_can_be_used(): void
    {
        OrganizationType::create(['slug' => 'yayasan', 'name' => 'Yayasan Sosial']);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('organizations.create'))->assertOk()->assertSee('Yayasan Sosial');
        $this->actingAs($user)->post(route('organizations.store'), ['name' => 'Yayasan Peduli', 'type' => 'yayasan', 'city' => 'Denpasar',
            'description' => 'Yayasan sosial.', 'contact' => 'kontak@contoh.test'])->assertSessionHasNoErrors();

        $org = Organization::firstOrFail();
        $this->assertSame('yayasan', $org->type);
        $this->assertSame('Yayasan Sosial', $org->typeLabel());
    }

    public function test_existing_type_still_works_and_unknown_type_rejected(): void
    {
        $payload = ['name' => 'HIMAIF', 'city' => 'Denpasar', 'description' => 'Himpunan.', 'contact' => 'a@b.test'];
        $this->actingAs(User::factory()->create())->post(route('organizations.store'), $payload + ['type' => 'tidak-ada'])->assertSessionHasErrors('type');
        $this->actingAs(User::factory()->create())->post(route('organizations.store'), $payload + ['type' => 'institusi'])->assertSessionHasNoErrors();
        $this->assertSame('Institusi Pendidikan', Organization::firstOrFail()->typeLabel());
    }
}
