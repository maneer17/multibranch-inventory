<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $downtown = Branch::where('code', 'BR-DWT')->first();
        $westside = Branch::where('code', 'BR-WST')->first();
        $riverside = Branch::where('code', 'BR-RVS')->first();

        $this->createUser('Samir Haddad', 'admin@example.com', null, 'admin');

        $this->createUser('Layla Odeh', 'layla@example.com', $downtown, 'branch_manager');
        $this->createUser('Omar Nasser', 'omar@example.com', $downtown, 'staff');

        $this->createUser('Rania Khalil', 'rania@example.com', $westside, 'branch_manager');
        $this->createUser('Yousef Barakat', 'yousef@example.com', $westside, 'staff');

        $this->createUser('Tariq Yousef', 'tariq@example.com', $riverside, 'branch_manager');
        $this->createUser('Hana Masri', 'hana@example.com', $riverside, 'staff');
    }

    private function createUser(string $name, string $email, ?Branch $branch, string $role): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'branch_id' => $branch?->id,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
