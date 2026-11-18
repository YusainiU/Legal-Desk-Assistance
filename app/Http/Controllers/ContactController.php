<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Support\ContactDetails;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function edit(Request $request, Contact $contact): View
    {
        abort_unless((int) $contact->user_id === (int) $request->user()->id, 404);

        return view('contacts.form', compact('contact'));
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        abort_unless((int) $contact->user_id === (int) $request->user()->id, 404);
        $contact->update(ContactDetails::validate(
            $request->only(['name', 'email', 'phone', 'organisation']),
            $request->user(),
            $contact,
        ));

        return to_route('contacts.index')->with('status', 'Contact updated.');
    }
}
