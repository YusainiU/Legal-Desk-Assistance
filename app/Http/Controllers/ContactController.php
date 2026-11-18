<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController
{
    public function index(Request $request): View
    {
        $search = Str::limit(trim($request->string('search')->toString()), 160, '');

        $contacts = $request->user()->contacts()
            ->when($search !== '', function (Builder $query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(fn (Builder $match) => $match
                    ->whereLike('name', $term)
                    ->orWhereLike('email', $term)
                    ->orWhereLike('phone', $term)
                    ->orWhereLike('organisation', $term));
            })
            ->orderBy('name')->orderBy('id')->paginate(25)->withQueryString();

        return view('contacts.index', compact('contacts', 'search'));
    }

    public function create(): View
    {
        return view('contacts.form', ['contact' => new Contact]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->contacts()->create($this->validateContact($request));

        return to_route('contacts.index')->with('status', 'Contact created.');
    }

    public function edit(Request $request, Contact $contact): View
    {
        abort_unless((int) $contact->user_id === (int) $request->user()->id, 404);

        return view('contacts.form', compact('contact'));
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        abort_unless((int) $contact->user_id === (int) $request->user()->id, 404);
        $contact->update($this->validateContact($request, $contact));

        return to_route('contacts.index')->with('status', 'Contact updated.');
    }

    private function validateContact(Request $request, ?Contact $contact = null): array
    {
        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => mb_strtolower(trim($email)) ?: null]);
        }

        $uniqueEmail = Rule::unique('contacts', 'email')->where('user_id', $request->user()->id);
        if ($contact) {
            $uniqueEmail->ignore($contact);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:254', $uniqueEmail],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:40'],
            'organisation' => ['nullable', 'string', 'max:160'],
        ]);
    }
}
