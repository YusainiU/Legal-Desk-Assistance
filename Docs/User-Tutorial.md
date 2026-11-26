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

Open **Teams** to see the members, open cases, and incomplete tasks assigned to each team you belong to. Administrators can see all teams. The team list shows assigned work. Case details and documents remain shared among eligible staff with Case Register access; ownership identifies responsibility.

## 12. Archive and restore cases

Archiving removes cases from the Case Register without deleting their records or changing their status or workflow stage. Open, Closed and Declined cases can all be archived.

1. In **Case register**, tick the checkbox beside one or more cases.
2. Select **Archive** and confirm. The selected cases move to the Archive list.
3. Open the **Archive** tab to search or filter archived cases, or select a case title to review its details.
4. Tick one or more archived cases and select **Restore** to return them to the Case Register.

Both actions are recorded in **Case history** with the name of the user who performed them. Restoring a closed or declined case does not reopen it. Archiving is not file closure and does not complete outstanding tasks.


## 13. Manage page access and permissions

Only an active Super Admin can configure this facility. Open **Administration → Page Access & Permissions** in the sidebar.

### Create a permission group

1. Expand **Create permission group** under **Permission groups**.
2. Enter a name, such as Research Team, and tick the active users who should belong to it.
3. Select **Create group**. Expand an existing group to change its name or membership and select **Save group**. Use **Delete group** to remove it and its grants.

Permission groups are separate from operational Teams. A user may belong to several groups.

### Restrict a page

1. Under **Page permissions**, expand **Search AI Resources**, for example.
2. Choose **Selected users and groups only** in **Access mode**.
3. Tick Research Team under **Permission groups** and any additional individual users under **Individual users**.
4. Select **Save permissions**.
5. Sign in using an ordinary staff account in a separate browser session. Verify that a permitted user can open the page and an unpermitted user cannot see its sidebar link or access its direct URL (HTTP 403).

Choose **All eligible active users** and save to remove the additional restriction. In restricted mode, either a direct user grant or a permitted group membership is sufficient. Removing one grant does not revoke access if another grant still applies. An empty restricted list denies everyone except active Super Admins.

The configurable pages are Case Register, Contacts, Teams, Build Resources, Library, Court Judgement Builder, Search AI Resources, AI Search Settings, Users, Firm Settings, Login Audit Log, Knowledge Base Guide and Getting Started. Existing administrative roles remain required where applicable. Dashboard, sign-in, sign-out and permission administration are not configurable; the Dashboard hides case statistics if Case Register access is denied.

Page access is not confidential-case access. Information from a case may still appear in another permitted module. Case and document policies and workflow rules continue to apply. See [Page Permissions](Page-Permissions.md) for technical details.

### Granular operation permissions

In Administration → Page Access & Permissions, each module now has a View rule and separate operation rules where applicable. Existing page grants remain unchanged. Unrestricted operations inherit View access; restricted operations require a direct or group grant in addition to View. To configure view-only access, grant View and restrict every operation, leaving its grant list empty or granting it only to other groups. To allow creation but not editing, grant Create and restrict Update. Case Register has separate rules for tasks, documents, charges, references, workflow, email and final decisions. Existing roles and workflow restrictions still apply. Active Super Admins bypass these restrictions. Operation rules reuse the existing permission tables; no additional migration is required after the original page permissions migration. Controls may still appear when an operation is denied; the server rejects guarded actions with HTTP 403. This is not a confidential-case boundary. Test each operation and cross-module path before production deployment.

Expand the application page under Page permissions, then click View or a granular operation to open its permission dialog. Select the access mode, groups and individual users, and save. Use the user search to filter the list; selected users remain selected when filtered.

### AI security (S-05–S-07)
Run `php artisan migrate` before use. AI Search administration explicitly enables external AI search and indexed analysis; without a saved enablement setting both are disabled, even if the old environment flag is true. Existing saved enablement is retained. Default shared quotas: 5 requests/user/minute, 50/user/day, 500/installation/day (fixed UTC windows). Failed attempts count; these are request counts, not token or monetary limits. Both AI actions require confidentiality acknowledgement; successful requests reset it. Indexed analysis sends the question and selected passages to OpenAI, while public search sends the question and filters to the configured provider. Review provider terms and authorization before enabling. No raw provider errors are intentionally logged by these guarded paths. Existing research remains available. Quota rows are retained; periodic cleanup is outstanding. Runtime, concurrency, and browser testing remain outstanding.
