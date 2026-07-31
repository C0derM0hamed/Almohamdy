# Inquiry Status Map

## Verified legacy sources

- `OldProject/db/demohhospitals_db (6).sql`
- `OldProject/inquiries_and_services.php`
- `OldProject/inquiries_and_services_receiver.php`
- `OldProject/inquiries_and_services_timeline.php`
- Their equivalent `branch/` and `admin/` pages

## Statuses

| Value | Legacy label | Verified behavior |
|---:|---|---|
| `0` | New | Historical/new value accepted by legacy list and display logic. |
| `1` | New | Present in legacy records although absent from the status lookup seed. Treated as new for display compatibility. |
| `3` | تحت الإجراء | Published lookup status. A reply row is inserted and the inquiry status is updated. |
| `4` | تم التواصل | Published lookup status. A reply row is inserted and the inquiry status is updated. The legacy code prepares a closure SMS, but local delivery must remain disabled. |
| `5` | تم التواصل ولم يتم الرد من قبل المراجع | Published lookup status. A reply row is inserted and the inquiry status is updated. |
| `999999` | New / forward sentinel | Default new value. When submitted as an action it means forward to another department: update `inquired_section`, insert a reply, then keep status `999999`. A reply with this value distinguishes a forwarded item from a never-processed new item. |

## Direction and scope

- Outgoing records are identified by the sender `branch_id`.
- Incoming records are identified by destination `inquired_section`.
- Both legacy lists constrain records by `companies_groups_id`.
- NewProject always applies the same branch/destination scope; it is not controlled by a feature flag.
- The legacy schema has no `assigned_to` column. Forwarding is therefore limited to a department and must not offer employee assignment.

## Transition rules retained in NewProject

- Status writes are allowed only on the incoming route.
- Forward requires a published destination department.
- Each write updates the inquiry and inserts an `inquiries_and_services_reply` event in one transaction.
- Updates remain available for every current status except `4`; the legacy receiver hides its Add Status button only after successful contact.
- No status `6` (Completed) exists in the approved legacy schema, so it is not offered or written.

## Deliberately excluded

- Employee assignment and `assigned_to`: not supported by the legacy schema.
- Status `6`: introduced only by a NewProject migration and not verified in OldProject.
- Live SMS delivery: Saudi-restricted integration and not safe to invoke locally.
