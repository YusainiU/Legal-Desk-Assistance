<?php
namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Matter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Demo data is restricted to local and testing environments.');
        }

        DB::transaction(function () {
            $users = collect();
            for ($i = 1; $i <= 20; $i++) {
                $number = sprintf('%02d', $i);
                $email = "demo-user-{$number}@example.test";
                $user = User::withTrashed()->where('email', $email)->first()
                    ?? User::factory()->create(['email' => $email, 'password' => Str::random(64), 'has_access' => false]);
                $users->push($user);
            }

            $user = $users->first();
            if ($user->trashed()) {
                throw new RuntimeException('The primary demo user has been deleted.');
            }

            for ($i = 1; $i <= 20; $i++) {
                $number = sprintf('%02d', $i);
                $email = "demo-client-{$number}@example.test";
                $contact = $user->contacts()->where('email', $email)->first()
                    ?? Contact::factory()->create(['user_id' => $user->id, 'email' => $email]);
                $title = "Demo case {$number}";
                if (! Matter::query()->where('user_id', $user->id)->where('title', $title)->exists()) {
                    Matter::factory()->forClient($user, $contact)->create([
                        'title' => $title,
                        'owner' => $users[$i - 1]->name,
                    ]);
                }
            }
        });
    }
}
