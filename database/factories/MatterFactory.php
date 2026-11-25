<?php
namespace Database\Factories;

use App\Models\Contact;
use App\Models\Matter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

class MatterFactory extends Factory
{
    protected $model = Matter::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->randomElement(['Contract review', 'Property transaction', 'Civil dispute', 'Employment advice']).' — '.fake()->unique()->numerify('#######'),
            'description' => 'Fictional demonstration case for testing.',
            'client' => fake()->name(),
            'owner' => fn (array $attributes) => User::findOrFail($attributes['user_id'])->name,
            'practice' => fake()->randomElement(array_keys(config('matterdesk.practices'))),
            'stage' => 0,
            'status' => 'Open',
            'conflict' => 'Pending',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Matter $matter) {
            if (! $matter->contacts()->exists()) {
                $contact = Contact::factory()->create(['user_id' => $matter->user_id, 'name' => $matter->client]);
                $matter->contacts()->attach($contact->id);
            }
        });
    }

    public function forClient(User $user, Contact $contact): static
    {
        if ((int) $contact->user_id !== (int) $user->id) {
            throw new InvalidArgumentException('Contact must belong to the case user.');
        }

        return $this->state([
            'user_id' => $user->id,
            'owner' => $user->name,
            'client' => $contact->name,
        ])->hasAttached($contact, [], 'contacts');
    }
}
