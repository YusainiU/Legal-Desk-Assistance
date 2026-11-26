<x-layouts.app>
    <div class="mx-auto max-w-4xl space-y-6">
        <header class="space-y-3">
            <h1 class="desk-title">Getting started</h1>
            <flux:text>Follow a first case from the client interview to file closure.</flux:text>
            <nav aria-label="Tutorial sections" class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
                <flux:link href="#create-case">Create a case</flux:link>
                <flux:link href="#working-a-stage">Work on a stage</flux:link>
                <flux:link href="#workflow-stages">Follow the nine stages</flux:link>
                <flux:link href="#reference-search">Find and reuse references</flux:link>
                <flux:link href="#using-teams">Use teams</flux:link>
                <flux:link href="#archive-cases">Archive and restore cases</flux:link>
                <flux:link href="#page-permissions">Page access and permissions</flux:link>
            </nav>
        </header>

        <flux:card id="create-case" class="desk-panel space-y-4 scroll-mt-6">
            <flux:heading size="lg" level="2">1. Create your first case</flux:heading>
            <ol class="list-decimal space-y-3 ps-6 text-sm leading-relaxed">
                <li>Open <flux:link :href="route('contacts.index')">Contacts</flux:link> and select <strong>New contact</strong>. Save the client’s name and an email address or phone number.</li>
                <li>Open <flux:link :href="route('workspace')">Case Register</flux:link> and select <strong>New case</strong>. Enter the title, case description, owner, and practice area. Select the client contact and add the other party if relevant. You can also create a contact from this form.</li>
                <li>Select <strong>Create case</strong>. The case opens at <strong>Interview client</strong>. The <strong>Current stage</strong> header shows your progress, and <strong>Workflow</strong> shows the checklist and next action.</li>
            </ol>
            <flux:text size="sm">If you are practising, use a fictional client and case description.</flux:text>
        </flux:card>

        <section id="working-a-stage" class="scroll-mt-6 space-y-3">
            <flux:heading size="lg" level="2">2. Work on each active stage</flux:heading>
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:card class="desk-panel space-y-2">
                    <flux:heading level="3">Tasks</flux:heading>
                    <flux:text size="sm">Open <strong>Tasks → New task</strong>, describe the work, assign it, and add a due date if needed. Select <strong>Mark complete</strong> when done. Complete or delete outstanding tasks for this stage or earlier stages before advancing.</flux:text>
                </flux:card>
                <flux:card class="desk-panel space-y-2">
                    <flux:heading level="3">Time &amp; charges</flux:heading>
                    <flux:text size="sm">Select <strong>Log time</strong>, choose the stage, and enter the minutes and hourly rate. The first entry sets the case currency. The panel shows stage subtotals and a running total.</flux:text>
                </flux:card>
                <flux:card class="desk-panel space-y-2">
                    <flux:heading level="3">Progress notes</flux:heading>
                    <flux:text size="sm">In <strong>Workflow</strong>, use <strong>Stage note → Save note</strong> to record progress or a decision. The note appears in <strong>Case history</strong> with the stage name.</flux:text>
                </flux:card>
                <flux:card class="desk-panel space-y-2">
                    <flux:heading level="3">Email the client</flux:heading>
                    <flux:text size="sm">If email delivery is enabled and configured, open <strong>Contacts → Email a contact</strong>. Choose a linked recipient with an email address, enter a subject and message, and select <strong>Send email</strong>. Check the result in <strong>Case history</strong>.</flux:text>
                </flux:card>
            </div>
        </section>

        <flux:card id="workflow-stages" class="desk-panel space-y-4 scroll-mt-6">
            <div class="space-y-2">
                <flux:heading size="lg" level="2">3. Follow the nine stages</flux:heading>
                <flux:text>Complete the current checklist and address outstanding tasks. Use <strong>Advance stage</strong> for routine steps, then look at the <strong>Current stage</strong> header for what comes next.</flux:text>
            </div>

            @php
                $stageTips = [
                    'Save the interview summary as a stage note and add tasks for urgent dates.',
                    'Record the conflict decision and its basis. Use Legal References to capture the authorities or clauses that apply to the case.',
                    'Enter the decision and reasons. Accept case requires conflict clearance. Decline case ends the workflow.',
                    'Confirm the warrant was executed and the retainer was requested and paid before advancing.',
                    'Follow the practice-area guidance and track work, dates, and charges as you go.',
                    'Use Case history to record invoices and payments. If interim billing does not apply, record that in a note.',
                    'Record what you shared and the next steps. Use case email if it is configured.',
                    'Record the outcome and review Time & charges before issuing the final invoice through your billing process.',
                    'Finish the checklist and all open tasks, then select Close file. The final time-charge total is recorded in Case history.',
                ];
            @endphp

            <ol class="divide-y divide-zinc-800">
                @foreach (config('matterdesk.stages') as $index => [$name, $description])
                    <li class="flex gap-3 py-4">
                        <span aria-hidden="true" class="grid size-8 shrink-0 place-items-center rounded-lg bg-zinc-800 text-sm font-semibold">{{ $index + 1 }}</span>
                        <div class="min-w-0 space-y-1">
                            <flux:heading level="3">{{ $name }}</flux:heading>
                            <flux:text size="sm">{{ $stageTips[$index] ?? $description }}</flux:text>
                        </div>
                    </li>
                @endforeach
            </ol>

            <flux:text size="sm">To review an earlier step, select <strong>Previous stage</strong> or use <strong>All workflow stages → View stage</strong>. In an open case, <strong>Reinstate stage</strong> makes a completed step current again.</flux:text>
        </flux:card>

        <flux:card id="reference-search" class="desk-panel space-y-3 scroll-mt-6">
            <flux:heading size="lg" level="2">4. Record and reuse legal references</flux:heading>
            <ol class="list-decimal space-y-2 ps-6 text-sm leading-relaxed">
                <li>In an open case, select <strong>Legal References → Add reference</strong>. Choose the authority type, enter its title, and explain why it applies. Add a citation, court or jurisdiction, and source link if available.</li>
                <li>Open <flux:link :href="route('knowledge-base')">Knowledge base</flux:link> in the sidebar. Search for a title, citation, jurisdiction, or previous reasoning. Use <strong>Reference type</strong> to narrow the results.</li>
                <li>Select <strong>Copy to case</strong>, pick another open case, and write why the authority applies there. The citation and source carry over, and the new explanation stays with the destination case.</li>
            </ol>
        </flux:card>

        <flux:card id="using-teams" class="desk-panel space-y-3 scroll-mt-6">
            <flux:heading size="lg" level="2">5. Assign work to teams</flux:heading>
            <ol class="list-decimal space-y-2 ps-6 text-sm leading-relaxed">
                <li>Administrators can go to <strong>Teams → New team</strong>, enter a name and description, and select members. Use <strong>Edit team</strong> to update membership.</li>
                <li>Choose <strong>Team (optional)</strong> when creating a case, or select <strong>Assign Team</strong> or the assigned team’s name on an open case. A team can also be set separately when creating or editing a task.</li>
                <li>Open <flux:link :href="route('teams.index')">Teams</flux:link> to see each team’s members, open cases, and incomplete tasks. Staff see their own teams, and administrators can see all teams.</li>
            </ol>
            <flux:text size="sm">The case keeps its owner. Case details and documents are shared among eligible staff with Case Register access; ownership identifies responsibility.</flux:text>
        </flux:card>

        <flux:card id="archive-cases" class="desk-panel space-y-3 scroll-mt-6">
            <flux:heading size="lg" level="2">6. Archive and restore cases</flux:heading>
            <flux:text size="sm">Archiving removes a case from the Case Register without deleting its records or changing its status or workflow stage. Open, Closed and Declined cases can all be archived.</flux:text>
            <ol class="list-decimal space-y-2 ps-6 text-sm leading-relaxed">
                <li>In <strong>Case register</strong>, tick the checkbox beside one or more cases. Select <strong>Archive</strong> and confirm.</li>
                <li>Select the <strong>Archive</strong> tab to find archived cases. Select a case title to review its details.</li>
                <li>Tick the cases to return to the register and select <strong>Restore</strong>. They return with their original status and stage.</li>
            </ol>
            <flux:text size="sm">Both actions are recorded in Case history with the user’s name. Archiving is not file closure and does not complete outstanding tasks.</flux:text>
        </flux:card>

        <flux:card id="page-permissions" class="desk-panel space-y-3 scroll-mt-6">
            <flux:heading size="lg" level="2">7. Manage page access and permissions</flux:heading>
            <flux:text>Only an active Super Admin can configure page permissions. Permission groups are separate from operational teams.</flux:text>
            <ol class="list-decimal space-y-2 ps-6 text-sm leading-relaxed">
                <li>Open <strong>Administration → Page Access &amp; Permissions</strong>. Expand <strong>Create permission group</strong>, enter a name, select members and save using <strong>Create group</strong>.</li>
                <li>Under <strong>Page permissions</strong>, expand a page such as Search AI Resources. Choose <strong>Selected users and groups only</strong>, tick the allowed groups or individual users and select <strong>Save permissions</strong>.</li>
                <li>Test with an ordinary staff account. Restricted sidebar links are hidden and unauthorized direct requests are denied. Choose <strong>All eligible active users</strong> and save to remove the restriction.</li>
            </ol>
            <flux:text size="sm">A direct grant or any permitted group membership allows access, subject to existing role requirements. An empty restricted list denies everyone except active Super Admins. Dashboard and sign-out remain available; case statistics are hidden without Case Register access.</flux:text>
            <flux:text size="sm">Page permissions do not isolate individual cases or prevent related information appearing in other permitted modules.</flux:text>
            <flux:text size="sm">Each page now has a View rule and separate operation rules. For view-only access, allow View and restrict every operation to other groups or leave their grants empty. Unrestricted operations inherit View access. Existing role and workflow checks remain in force.</flux:text>
            <flux:text size="sm">Expand the application page under Page permissions, then click View or a granular operation to open its permission dialog. Select the access mode, groups and individual users, and save. Use the user search to filter the list; selected users remain selected when filtered.</flux:text>
            @can('manage-page-permissions')
                <flux:button :href="route('admin.page-permissions.index')">Open Page Access &amp; Permissions</flux:button>
            @endcan
        </flux:card>

        <flux:card class="desk-panel flex flex-wrap items-center justify-between gap-3">
            <div class="space-y-1">
                <flux:heading level="2">Share a case update</flux:heading>
                <flux:text size="sm">Use <strong>Print case summary</strong> to preview and download a PDF. Review the recorded history before sharing it.</flux:text>
            </div>
            <flux:button :href="route('workspace')" variant="primary">Go to Case Register</flux:button>
        </flux:card>
    </div>
</x-layouts.app>
