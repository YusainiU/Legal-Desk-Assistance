<?php

return [
    'stages' => [
        ['Interview client', 'Take initial instructions and record the client’s objectives, parties involved and any urgent dates.', ['Record the interview and the client’s objectives', 'Review urgency and provisional deadlines']],
        ['Analyse the facts', 'Review the available facts and documents, identify gaps, and check for conflicts. Consider identity and AML checks where applicable.', ['Record the factual assessment and missing information', 'Record the conflict review and applicable onboarding checks']],
        ['Decide whether to take the case', 'Record the decision and its reasons. If the firm declines, record how the prospective client was informed.', []],
        ['Warrant to act & retainer', 'Record the agreed scope and fee terms, then confirm execution of the warrant to act and payment of the retainer.', ['Warrant to act executed', 'Retainer fee requested', 'Retainer fee paid']],
        ['Attend to the case', 'Plan and carry out the work for the selected practice area.', ['Record the work plan and verify relevant deadlines', 'Record substantive work and any outstanding actions']],
        ['Interim invoices', 'Track interim invoices as they are issued and record payment. Add a follow-up task for any outstanding payment.', ['Interim invoices issued and recorded, or marked not applicable in a note', 'Interim payments recorded, or marked not applicable in a note']],
        ['Update the client', 'Keep the client informed during the case and record progress updates in the case notes.', ['Record the progress update and next steps shared with the client']],
        ['Conclude & issue final invoice', 'Record the outcome, communicate it to the client and issue the final invoice.', ['Record the outcome and communicate it to the client', 'Final invoice issued and recorded']],
        ['Close the file', 'Complete the closing review and record the firm’s retention decision.', ['Send the closing communication and review billing and client funds as applicable', 'Record the retention decision and close out outstanding obligations']],
    ],
    'practices' => [
        'Civil' => 'Review the facts and documents, advise on options, and pursue the agreed resolution.',
        'Litigation' => 'Track pleadings, evidence, filings, hearings, and any negotiations or settlement.',
        'Conveyancing' => 'Track title checks, transaction documents, completion, and post-completion steps.',
        'Criminal' => 'Track instructions, evidence review, court appearances, and the outcome.',
        'Syariah' => 'Confirm the forum and applicable procedure, then track documents, filings, dates, and the outcome.',
        'Transaction' => 'Track due diligence, drafting, approvals, signing, and completion.',
        'Advisory' => 'Track research, advice, client instructions, and follow-up.',
        'Other' => 'Record the agreed work, review dates, and track any approvals or follow-up.',
    ],
];
