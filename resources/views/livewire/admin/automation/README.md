## Automation rules (admin)

### Triggers (examples)
- `request.created`, `request.updated`, `request.status_changed`, `request.assigned`, `request.completed`
- `invoice.created`, `invoice.sent`, `invoice.paid`, `invoice.overdue`, `invoice.due_soon`
- `contract.created`, `contract.expiring`, `contract.signed`
- `document.uploaded`, `document.shared`
- `payment.received`, `payment.failed`
- `client.created`, `client.tier_changed`
- `storage.quota_reached`
- `schedule.daily`, `schedule.weekly`, `schedule.monthly`

### Condition DSL
Stored as JSON on the rule:

```json
{
  "op": "and",
  "rules": [
    { "path": "request.priority", "operator": "equals", "value": "urgent" },
    { "path": "client.tier", "operator": "equals", "value": "basic" }
  ]
}
```

Operators: `equals`, `not_equals`, `contains`, `in`, `not_in`, `gt`, `gte`, `lt`, `lte`, `exists`, `not_exists`.

### Actions
Each action is `{ "type": "...", "params": { ... } }`.

Common actions:
- `send_notification`: `{ "channel": "slack|teams", "message": "..." }`
- `send_email`: `{ "to": "admin|client|email", "email": "x@y.com", "subject": "...", "body": "..." }`
- `assign_request`: `{ "request_id": 123, "assignee_role": "staff" }` or `{ "assignee_user_id": 5 }`
- `change_request_status`: `{ "request_id": 123, "status": "in_progress" }`
- `create_invoice`: `{ "client_id": 1, "request_id": 123, "status": "draft" }`
- `update_client_tier`: `{ "client_id": 1, "tier": "premium" }`
- `add_internal_note`: `{ "message": "..." }`
- `trigger_webhook`: `{ "client_id": 1, "event": "request.created", "data": { ... } }`
- `create_admin_task`: `{ "title": "...", "details": "..." }`

### Template variables
Strings support `{{dot.path}}` placeholders pulled from the trigger payload. Example:
- `Urgent request: {{request.title}} (#{{request.id}})`

