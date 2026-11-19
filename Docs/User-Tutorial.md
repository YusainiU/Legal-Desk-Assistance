# Legal Desk Assistance user tutorial

In the app, select **Getting started** below your name in the sidebar to open the quick-start tutorial.

Follow a fictional matter from the first client interview to file closure. Use fictional names and details when practising or demonstrating the application.

## 1. Sign in and open the matter register

Open the application and sign in with your email and password. The Super Admin can set the name shown to everyone from **Firm settings** in the sidebar. Administrators can view the setting. If the application runs locally, use the Laravel address printed by `composer run dev`, usually `http://127.0.0.1:8000`. See the [README](../README.md) for local setup and the optional demo account.

Select **Case register** to see each matter’s stage or status, owner, and next due date. Use **Search cases** to find a record, or filter by **Open**, **Closed**, or **Declined**. Select a matter’s title to open it. **Workflow guide** explains what each stage requires.

## 2. Create a practice matter

First, open **Contacts** from the sidebar and select **New contact**. In the modal, enter **Cedar Vale Studio** as the name and a fictional phone number or `cedar-vale@example.test` as the email address. Select **Create contact**.

Return to **Case Register**, select **New case**, enter the following example, and select **Create case**. You can also use **New contact** on that screen to create and select a contact without leaving the form.

| Field | Example |
| --- | --- |
| Case title | Cedar Vale supplier dispute |
| Client contact | Cedar Vale Studio |
| Other party | Northbank Supplies |
| Case Description | The client wants advice about late deliveries under its supply agreement. |
| Case owner | Your name |
| Practice area | Litigation |

**Case Description** is required for new cases. After creating a case, select **Edit description** below its client and owner details to update it at any time.

The case opens at **Interview client**. The **Current stage** header stays visible as you switch between case sections. Under **Workflow**, use the current checklist and look for **Next stage** next to the advance button. Select **Previous stage**, or expand **All workflow stages** and select **View stage**, to review a completed step. Completed checklists, stage notes, and tasks are read-only. On an open case, select **Reinstate stage** and confirm to make that step current again. Later stages become upcoming and their recorded information stays on file.

## 3. Link and email contacts

Select a saved contact’s name in **Contacts** or **Case contacts** to open its details. You can add or update the optional address there. The new-contact form only asks for the contact’s basic details. Select the email address to open your default mail app. Under the phone number, choose **Call** to use your default calling app or **WhatsApp** to open a chat. WhatsApp requires the saved number to include an international prefix and country code, such as `+60`.

In an open case, open **Contacts** to link more contacts from your address book. Select **New contact**, then **Create and link contact** to add a new contact directly to the case. For a contact already in your address book, choose it from the list and select **Link contact**. For an older case without a contact, link one before moving to the next stage.

To send email, select a linked contact under **Email a contact**, enter a subject and message, then select **Send email**. A linked contact needs an email address, and the deployment needs a working delivery mailer. Case history records each attempt and whether the mail provider accepted it. Use a mailbox you control to test sending.

## 4. Manage stage tasks and record updates

While the case is open, open **Case history** and select **Add entry**. Choose an **Entry type**, enter the **Details**, and select **Save entry**. The timeline shows the newest entries first, with a date and time on each entry.

| Entry type | Example |
| --- | --- |
| **Note** | The client wants to resolve the delivery dispute and avoid further delays. |
| **Client update** | The client was told that the agreement is under review and when to expect the next update. |
| **Billing** | Interim invoice DEMO-001 was issued. Add a billing entry when payment is received. |

Under **Tasks**, select **New task**. Enter what needs doing, who is responsible, and an optional due date. The task is assigned to the current stage. Use **Mark complete** once it’s done, **Edit** to change the details, or **Delete** to remove it.

The workflow flags incomplete tasks for the current and earlier stages. Complete or delete them before advancing or closing the file. If you reinstate a stage, tasks in later stages are checked when you reach those stages again. Existing tasks are assigned to the stage the case was at when the task module was installed.

Record client updates and billing events in **Case history** as they happen.

## 5. Attach and open documents

Open a case and select **Documents**. Choose a file and select **Upload document**. Each file can be up to 10 MB. Accepted formats include PDF, Word, Excel, PowerPoint, plain text, JPEG, and PNG.

Use **Preview** to open a PDF, image, or plain-text file in a new tab. Use **Download** to save any attachment, including Office files. On an open case, use **Delete** next to a document and confirm **Delete document** to remove the attachment. Closed and declined cases keep their existing documents available for preview and download.

## 6. Log time and review charges

Open **Time & charges → Log time**. Select the stage, describe the work, and enter the time in minutes and the hourly rate. The first entry sets the case currency. The charge is calculated from the minutes and rate, and the panel shows a subtotal for each stage. You can edit or delete entries while the case is open. Select **Download CSV** in **Time & charges** to export all entries with their total minutes and charges.

At **Conclude & issue final invoice**, use **Review time charges** to see the total to carry into your invoice. When you select **Close file**, Legal Desk saves the final time and charge total in **Case history** and makes the entries read-only. Use your usual invoicing process for discounts, taxes, payments, and issuing the invoice.

## 7. Work through the nine stages

At each active stage, enter a comment in **Stage note** and select **Save note**. The note appears in **Case history** with the stage name. A draft also saves when you successfully advance, reinstate a stage, decline, or close the case.

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

## 8. Record legal references

Open **Legal References → Add reference** to record a statute, provision, legal or contract clause, precedent, or other authority. Choose a type and title and explain **Why it applies to this case**. Add a citation or section number, jurisdiction or court, and a source URL when available. References stay with the case and can be edited or deleted while the case is open.

## 9. Search and reuse references

Open **Knowledge base** from the sidebar to search references recorded in your cases. Search by title, citation, jurisdiction, or reasoning and filter by reference type. Use **Copy to case** to carry the citation details into another open case, then write a new explanation of why the reference applies there.

## 10. Print a case summary

Open a case and select **Print case summary**. The PDF preview shows the firm name, full case details, completed stages, and all recorded case history. Review the contents before sharing, then select **Print** or **Download PDF**. Attach the downloaded file using your email application.

## 11. Teams and assigned work

Administrators can open **Teams → New team**, enter a name and description, and select the team’s members. **Edit team** lets them change those details and memberships.

When creating a case, use **Team (optional)** to assign it alongside the owner. On an existing open case, select **Assign Team** or the assigned team’s name. In **Tasks**, select a team when creating or editing a task. A task can have a different team from its case.

Open **Teams** to see the members, open cases, and incomplete tasks assigned to each team you belong to. Administrators can see all teams. The team list shows assigned work. Full case details and documents are available to the case owner.
