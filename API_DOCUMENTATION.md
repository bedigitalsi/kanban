# Taskboard API Documentation

**Base URL:** `https://tasks.bedigital.si/api`  
**Authentication:** Bearer token in `Authorization` header

```bash
curl -H "Authorization: Bearer podklanec" https://tasks.bedigital.si/api/tasks
```

---

## Tasks

### List Tasks
```
GET /tasks
```

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `status` | string | Filter by status: `backlog`, `todo`, `in_progress`, `done` |
| `priority` | string | Filter by priority: `low`, `medium`, `high` |
| `assigned_to` | string | Filter by assignee: `sandi`, `alex` |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Task title",
      "description": "Task description",
      "status": "todo",
      "priority": "high",
      "assigned_to": "alex",
      "due_date": "2026-02-10",
      "tags": ["urgent", "email"],
      "position": 0,
      "created_at": "2026-02-03T10:00:00.000000Z",
      "updated_at": "2026-02-03T10:00:00.000000Z"
    }
  ]
}
```

### Get Single Task
```
GET /tasks/{id}
```

### Create Task
```
POST /tasks
```

**Body:**
```json
{
  "title": "Required: task title",
  "description": "Optional: longer description",
  "status": "backlog|todo|in_progress|done (default: backlog)",
  "priority": "low|medium|high (default: medium)",
  "assigned_to": "sandi|alex|null",
  "due_date": "YYYY-MM-DD (optional)",
  "tags": ["optional", "array", "of", "strings"],
  "position": 0
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": { ... }
}
```

### Update Task
```
PUT /tasks/{id}
```

Same fields as create, all optional. Only send fields you want to update.

### Delete Task
```
DELETE /tasks/{id}
```

Soft-deletes the task.

### Update Task Positions (Drag & Drop)
```
POST /tasks/positions
```

**Body:**
```json
{
  "tasks": [
    { "id": 1, "position": 0, "status": "todo" },
    { "id": 2, "position": 1, "status": "todo" },
    { "id": 3, "position": 0, "status": "in_progress" }
  ]
}
```

---

## Activity Logs

For logging AI/automated work (so you have visibility into what happened).

### List Activity Logs
```
GET /activity-logs
```

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `date` | string | Filter by date: `YYYY-MM-DD` |
| `type` | string | Filter by type |
| `page` | int | Pagination (50 per page) |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "email",
      "title": "Checked Gmail inbox",
      "description": "Found 3 new emails, none urgent",
      "metadata": { "emails_checked": 3 },
      "created_at": "2026-02-03T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "total": 230
  }
}
```

### Create Activity Log
```
POST /activity-logs
```

**Body:**
```json
{
  "type": "email|sms|order_fix|analysis|integration|other",
  "title": "Required: short title",
  "description": "Optional: details",
  "metadata": { "any": "json", "you": "want" }
}
```

**Example: Log an email check**
```bash
curl -X POST https://tasks.bedigital.si/api/activity-logs \
  -H "Authorization: Bearer podklanec" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "email",
    "title": "Checked alex@bedigital.si",
    "description": "2 new emails - forwarded inquiry from customer to Sandi",
    "metadata": {"inbox": "alex@bedigital.si", "new_count": 2}
  }'
```

---

## Scheduled Routines

Track recurring automated tasks (for reference, not execution).

### List Routines
```
GET /scheduled-routines
```

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `category` | string | Filter: `email`, `sms`, `orders`, `analysis`, `monitoring`, `other` |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Check Gmail",
      "description": "Hourly email check",
      "schedule_time": "Every hour 7-22",
      "schedule_type": "hourly",
      "frequency": "7:00-22:00 CET",
      "assigned_to": "alex",
      "enabled": true,
      "category": "email",
      "position": 0
    }
  ]
}
```

### Create Routine
```
POST /scheduled-routines
```

**Body:**
```json
{
  "title": "Required",
  "description": "Optional",
  "schedule_time": "Human-readable time, e.g. '09:00 CET' or 'Every hour'",
  "schedule_type": "daily|hourly|interval|manual",
  "frequency": "Optional details, e.g. '7:00-22:00'",
  "assigned_to": "sandi|alex",
  "enabled": true,
  "category": "email|sms|orders|analysis|monitoring|other",
  "position": 0
}
```

### Update Routine
```
PUT /scheduled-routines/{id}
```

### Delete Routine
```
DELETE /scheduled-routines/{id}
```

---

## Error Responses

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

**Not Found (404):**
```json
{
  "message": "No query results for model [App\\Models\\Task] 999"
}
```

**Unauthorized (401):**
```json
{
  "error": "Unauthorized"
}
```

---

## Quick Reference

| Action | Method | Endpoint |
|--------|--------|----------|
| List tasks | GET | `/tasks` |
| Get task | GET | `/tasks/{id}` |
| Create task | POST | `/tasks` |
| Update task | PUT | `/tasks/{id}` |
| Delete task | DELETE | `/tasks/{id}` |
| Reorder tasks | POST | `/tasks/positions` |
| List activity logs | GET | `/activity-logs` |
| Create activity log | POST | `/activity-logs` |
| List routines | GET | `/scheduled-routines` |
| Create routine | POST | `/scheduled-routines` |
| Update routine | PUT | `/scheduled-routines/{id}` |
| Delete routine | DELETE | `/scheduled-routines/{id}` |

---

## Usage Notes for Alex (AI)

1. **Log your work** — After completing tasks like email checks, order fixes, or SMS sends, create an activity log entry so Sandi can see what you did.

2. **Task assignment** — When creating tasks, use `"assigned_to": "alex"` for your own tasks, `"sandi"` for things that need human attention.

3. **Tags** — Use consistent tags like `["urgent"]`, `["woocommerce"]`, `["gls"]`, `["email"]` for easy filtering.

4. **Routines** — The scheduled-routines table is for documentation only. Actual cron execution is handled by OpenClaw's cron system, not this API.
