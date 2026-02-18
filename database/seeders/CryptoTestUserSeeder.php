<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CryptoTestUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'WAF Test User',
            'email' => 'test@waf.com',
            'password' => Hash::make('password123'),
            'api_key' => 'test_api_key_12345',
            // This will be automatically ENCRYPTED in the DB due to your Model cast
            'secret_key' => 'test_secret_key_67890_very_long_string_for_security',
            'is_admin' => true,
        ]);

        $this->command->info('Test User Created Successfully!');
        $this->command->warn('API Key: test_api_key_12345');
        $this->command->warn('Secret Key: test_secret_key_67890_very_long_string_for_security');
    }
}