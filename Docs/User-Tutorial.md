# Legal Desk Assistance user tutorial

Follow a fictional matter from the first client interview to file closure. Use fictional names and details when practising or demonstrating the application.

## 1. Sign in and open the matter register

Open Legal Desk Assistance and sign in with your email and password. If the application runs locally, use the Laravel address printed by `composer run dev`, usually `http://127.0.0.1:8000`. See the [README](../README.md) for local setup and the optional demo account.

Select **Case register** to see each matter’s stage or status, owner, and next due date. Use **Search cases** to find a record, or filter by **Open**, **Closed**, or **Declined**. Select a matter’s title to open it. **Workflow guide** explains what each stage requires.

## 2. Create a practice matter

First, open **Contacts** from the sidebar and select **New contact**. Enter **Cedar Vale Studio** as the name and a fictional phone number or `cedar-vale@example.test` as the email address. Select **Create contact**.

Return to **Workspace**, select **New case**, enter the following example, and select **Create case**.

| Field | Example |
| --- | --- |
| Case title | Cedar Vale supplier dispute |
| Client contact | Cedar Vale Studio |
| Other party | Northbank Supplies |
| Case owner | Your name |
| Practice area | Litigation |

The matter opens at **Interview client**. The current stage shows its checklist. The **Workflow** panel shows the full sequence and lets you review recorded checklists for earlier stages.

## 3. Link and email contacts

In an open case, use **Case contacts** to link more contacts from your address book. For an older case without a contact, link one before moving to the next stage.

To send email, select a linked contact under **Email a contact**, enter a subject and message, then select **Send email**. A linked contact needs an email address, and the deployment needs a working delivery mailer. Case history records each attempt and whether the mail provider accepted it. Use a mailbox you control to test sending.

## 4. Record notes, tasks, and updates

While the case is open, go to **Case history** and select **Add entry**. Choose an **Entry type**, enter the **Details**, and select **Save entry**. The timeline shows the newest entries first, with a date and time on each entry.

| Entry type | Example |
| --- | --- |
| **Note** | The client wants to resolve the delivery dispute and avoid further delays. |
| **Task** | Review the signed supply agreement. Enter an assignee and, if needed, a due date. |
| **Client update** | The client was told that the agreement is under review and when to expect the next update. |
| **Billing** | Interim invoice DEMO-001 was issued. Add a billing entry when payment is received. |

Tasks appear under **Tasks & deadlines**. Tick a task there when it’s complete. Record client updates and billing events as they happen throughout the matter.

## 5. Attach and open documents

Open a case and find **Documents**. Choose a file and select **Upload document**. Each file can be up to 10 MB. Accepted formats include PDF, Word, Excel, PowerPoint, plain text, JPEG, and PNG.

Use **Preview** to open a PDF, image, or plain-text file in a new tab. Use **Download** to save any attachment, including Office files. On an open case, use **Delete** next to a document and confirm **Delete document** to remove the attachment. Closed and declined cases keep their existing documents available for preview and download.

## 6. Work through the nine stages

Tick each checklist item after completing the work it describes. Select **Advance stage** when the stage is ready. The decision and closing stages have their own buttons.

| Stage | What to do |
| --- | --- |
| **1. Interview client** | Add a note summarising the interview and the client’s objectives. Review urgency and add tasks for dates that need follow-up. |
| **2. Analyse the facts** | Record your assessment, missing information, and applicable onboarding checks. Note who reviewed the conflict and why, then set **Conflict decision** to **Pending**, **Cleared**, or **Escalated** as appropriate. |
| **3. Decide whether to take the case** | Enter the **Decision and reasons**. Select **Accept case** once the conflict decision is **Cleared**. If declining, record why and how the prospective client was informed, then select **Decline case**. |
| **4. Warrant to act & retainer** | Record the agreed scope and fee terms. Tick the items when the warrant has been executed, the retainer requested, and payment received. |
| **5. Attend to the case** | Use the practice-area guidance to plan the work. Add tasks with verified deadlines, then record the work done and any outstanding actions. |
| **6. Interim invoices** | Record invoices and payments as **Billing** entries. If interim billing doesn’t apply, record why in a note and complete the relevant checklist items. |
| **7. Update the client** | Add a **Client update** entry describing what was shared and the agreed next steps. Complete the stage checklist. |
| **8. Conclude & issue final invoice** | Record the outcome and when it was communicated to the client. Add a billing entry for the final invoice and complete the checklist. |
| **9. Close the file** | Record the closing review and retention decision. Complete the closing checklist and any remaining tasks, then select **Close file**. The matter appears as **Closed** in the register. |

Use the matter history to record work performed outside the application, including signing the warrant, sending client communications, issuing invoices, and receiving payments.
