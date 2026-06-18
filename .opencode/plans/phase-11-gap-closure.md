# Phase 11: Gap Closure — 6 Remaining Features

## Overview

6 fitur yang masih partial akan diselesaikan. Total estimated: ~45-50 file diubah/dibuat.

| # | Fitur | Kompleksitas | Estimasi |
|---|-------|-------------|----------|
| 1 | Automation Wiring | Rendah | 1-2 jam |
| 2 | Scrum Sprint Board | Sedang | 2-3 jam |
| 3 | Dependency Tracking (Gantt) | Sedang | 2-3 jam |
| 4 | Notification Rules | Tinggi | 3-4 jam |
| 5 | Tags | Rendah | 30 menit |
| 6 | Integrations (Figma, Slack, Notion) | Tinggi | 4-5 jam |

---

## Fix: Project::savedFilters() Relationship Bug

**Issue:** `SavedFilterController::store()` memanggil `$project->savedFilters()->create(...)` tapi `Project` model tidak punya relasi `savedFilters()`.

**Fix:** Tambahkan di `app/Models/Project.php`:

```php
public function savedFilters(): HasMany
{
    return $this->hasMany(SavedFilter::class);
}
```

---

## Feature 1: Automation Wiring

### Kondisi Saat Ini
- `AutomationEngine::handleTaskEvent(Task $task, string $event, array $changes)` sudah jadi
- 5 trigger events sudah didefinisikan: `task.created`, `task.status_changed`, `task.priority_changed`, `task.assignee_added`, `task.due_date_passed`
- 7 action types sudah jadi: `assign`, `add_label`, `remove_label`, `set_priority`, `move_to_column`, `send_notification`, `add_comment`
- **Hanya `task.due_date_passed` yang jalan** (via `CheckOverdueTasks` artisan command)
- `TaskFieldUpdated` event di-dispatch tapi tidak ada listener
- `TaskMoved` event di-dispatch tapi tidak ada listener
- Tidak ada EventServiceProvider, tidak ada Listeners directory

### Yang Perlu Dibuat

#### 1. Event Listener: `app/Listeners/DispatchAutomationEvents.php`

```php
// Listen to TaskFieldUpdated
// Map field changes ke automation trigger strings:
//   board_column_id → task.status_changed
//   priority_id → task.priority_changed
// Call AutomationEngine::handleTaskEvent()
```

#### 2. Event Listener: `app/Listeners/DispatchTaskCreatedAutomation.php`

```php
// Listen to TaskCreated (perlu buat event baru)
// Call AutomationEngine::handleTaskEvent($task, 'task.created')
```

#### 3. Register Listeners: `app/Providers/EventServiceProvider.php`

```php
protected $listen = [
    TaskFieldUpdated::class => [DispatchAutomationEvents::class],
    TaskCreated::class => [DispatchTaskCreatedAutomation::class],
];
```

#### 4. Modify: `app/Http/Controllers/TaskController.php`

- Di `store()`: Dispatch event `TaskCreated` setelah task dibuat
- Di `update()`: Detect assignee changes → call `handleTaskEvent($task, 'task.assignee_added')`
- Di `moveColumn()`: Call `handleTaskEvent($task, 'task.status_changed', ['from' => $oldStatus, 'to' => $newStatus])`

#### 5. Event Baru: `app/Events/TaskCreated.php`

```php
class TaskCreated implements ShouldBroadcast
{
    public function __construct(
        public int $projectId,
        public int $taskId,
    ) {}
}
```

### File yang Diubah/Dibuat

| File | Aksi |
|------|------|
| `app/Listeners/DispatchAutomationEvents.php` | BUAT |
| `app/Listeners/DispatchTaskCreatedAutomation.php` | BUAT |
| `app/Providers/EventServiceProvider.php` | BUAT |
| `app/Events/TaskCreated.php` | BUAT |
| `app/Http/Controllers/TaskController.php` | UBAH (3 lokasi) |

### Testing

- Buat automation rule: "When task created → add label X"
- Buat task baru → verify label ditambahkan
- Buat automation rule: "When status changes to Done → notify reporter"
- Move task ke Done column → verify notifikasi terkirim

---

## Feature 2: Scrum Sprint Board

### Kondisi Saat Ini
- Board menampilkan SEMUA task project (project-wide)
- Sprint data sudah ada di board props (`sprints` array)
- Setiap task punya `sprints` array (many-to-many)
- Swimlane support: assignee, priority, epic (belum sprint)
- Sprint show page hanya list view, bukan board view

### Yang Perlu Dibuat

#### 1. Backend: Tambah sprint filter di `BoardController::show()`

```php
$sprintId = $request->query('sprint_id');
// Filter tasks per column:
$tasks = $col->tasks()
    ->when($sprintId, fn($q) => $q->whereHas('sprints', fn($sq) => $sq->where('sprint_id', $sprintId)))
    ->with([...])
    ->orderBy('column_order')
    ->get();
```

#### 2. Frontend: Tambah sprint filter dropdown di `board.tsx`

- Tambah dropdown "Sprint: All / [Sprint Name]" di board header
- Update URL query param `sprint_id` saat dipilih
- Reload board data dengan filter

#### 3. Frontend: Tambah swimlane "Sprint" di `board.tsx`

- Extend `getSwimlaneKey()` untuk handle `'sprint'`
- Group tasks berdasarkan sprint pertama mereka (atau "No sprint")

#### 4. Sprint Show Page: Tambah tombol "Open Board"

- Di `sprints/show.tsx`, tambah button yang link ke board dengan `?sprint_id=X`

### File yang Diubah/Dibuat

| File | Aksi |
|------|------|
| `app/Http/Controllers/BoardController.php` | UBAH (tambah sprint filter) |
| `resources/js/pages/projects/board.tsx` | UBAH (tambah filter dropdown + sprint swimlane) |
| `resources/js/pages/projects/sprints/show.tsx` | UBAH (tambah "Open Board" button) |

### Testing

- Buka board, pilih sprint dari dropdown → hanya task sprint itu yang muncul
- Aktifkan swimlane "Sprint" → tasks ter-group per sprint
- Dari sprint show page, klik "Open Board" → board terbuka dengan filter sprint

---

## Feature 3: Dependency Tracking (Gantt Arrows)

### Kondisi Saat Ini
- `task_relations` tabel ada: `task_id`, `related_task_id`, `relation_type`
- Relation types: `blocks`, `relates_to`, `duplicates`, `blocked_by`
- CRUD sudah jadi: `TaskRelationController`, UI di task detail drawer
- **Gantt chart belum menampilkan dependency arrows**
- Tidak ada blocking warning di board
- Relations tidak di-load di board/sprint views

### Yang Perlu Dibuat

#### 1. Backend: Load relations di Gantt data

- Di `ProjectController::show()` (timeline tab) atau API endpoint, eager load `relatedTasks` untuk tasks yang ditampilkan di Gantt

#### 2. Frontend: Tambah dependency arrows di `gantt-chart.tsx`

- Terima `relations` array sebagai prop baru
- Gambar SVG paths dari task bar ke related task bar
- Arrow types: `blocks` = solid arrow, `relates_to` = dashed line
- Color: red untuk blocking, gray untuk relates_to

#### 3. Frontend: Tambah blocking warning di board

- Di `task-card.tsx`, tambah badge "Blocked" jika task punya `blocks` relation
- Di `TaskController::moveColumn()`, tambah check: jika task diblokir oleh task lain yang belum selesai, return warning (bisa diabaikan)

#### 4. Frontend: Tambah relation info di Gantt tooltip

- Saat hover task bar di Gantt, tampilkan relations info

### File yang Diubah/Dibuat

| File | Aksi |
|------|------|
| `app/Http/Controllers/ProjectController.php` | UBAH (load relations untuk timeline) |
| `resources/js/components/gantt-chart.tsx` | UBAH (tambah SVG arrows) |
| `resources/js/components/task-card.tsx` | UBAH (tambah blocked badge) |
| `app/Http/Controllers/TaskController.php` | UBAH (tambah blocking check di moveColumn) |

### Testing

- Buat task A yang blocks task B
- Buka Gantt view → ada arrow dari A ke B
- Coba pindahkan task B yang belum selesai → ada warning "This task is blocked by A"
- Tandai task A selesai → task B bisa dipindahkan

---

## Feature 4: Notification Rules

### Kondisi Saat Ini
- 5 notification types: `task.assigned`, `task.commented`, `task.mentioned`, `task.updated`, `workspace.invitation`
- Preferences: per-user, per-type, on/off toggle (in_app + email)
- Tidak ada custom rules (filter by field, project, label, dll)
- `due_date_reminder` dan `member.added` ada di UI tapi belum ada backend

### Yang Perlu Dibuat

#### 1. Migration: `notification_rules` table

```
id              bigint PK
user_id         FK users.id
name            varchar(100)
event_type      varchar(100)  -- task.status_changed, task.field_updated, etc.
conditions      json          -- {"field": "board_column_id", "op": "equals", "value": 5}
channels        json          -- ["in_app", "email"]
enabled         boolean default true
project_id      FK nullable   -- null = all projects
created_at/updated_at
```

#### 2. Model: `app/Models/NotificationRule.php`

- BelongsTo User, BelongsTo Project (nullable)
- JSON conditions with operators: equals, not_equals, in, not_in, contains

#### 3. Service: `app/Services/NotificationRuleEngine.php`

```php
public function evaluate(User $user, string $eventType, Task $task, array $changes): void
{
    $rules = NotificationRule::where('user_id', $user->id)
        ->where('event_type', $eventType)
        ->where('enabled', true)
        ->get();

    foreach ($rules as $rule) {
        if ($this->conditionsMatch($rule->conditions, $task, $changes)) {
            $this->sendNotification($rule, $task, $changes);
        }
    }
}
```

#### 4. Controller: `app/Http/Controllers/NotificationRuleController.php`

- CRUD untuk notification rules
- Project-scoped listing

#### 5. Frontend: `resources/js/pages/settings/notifications.tsx`

- Tambah section "Notification Rules" di bawah preferences table
- Rule builder: event type dropdown, dynamic conditions, project scope, channel toggles
- List rules dengan toggle on/off, edit, delete

#### 6. Integration: Panggil engine dari TaskController

- Setelah field changes dideteksi, panggil `NotificationRuleEngine::evaluate()` untuk semua watchers/assignees/reporter

### File yang Diubah/Dibuat

| File | Aksi |
|------|------|
| `database/migrations/xxxx_create_notification_rules_table.php` | BUAT |
| `app/Models/NotificationRule.php` | BUAT |
| `app/Services/NotificationRuleEngine.php` | BUAT |
| `app/Http/Controllers/NotificationRuleController.php` | BUAT |
| `routes/web.php` | UBAH (tambah routes) |
| `resources/js/pages/settings/notifications.tsx` | UBAH (tambah rules section) |
| `app/Http/Controllers/TaskController.php` | UBAH (panggil rule engine) |
| `resources/js/types/index.ts` | UBAH (tambah NotificationRule type) |

### Testing

- Buat rule: "When status changes to Done → notify me via in-app"
- Pindahkan task ke Done → user dapat notifikasi
- Buat rule: "When priority is High → email me, project X only"
- Ubah priority task di project X ke High → user dapat email
- Ubah priority task di project Y ke High → tidak ada notifikasi

---

## Feature 5: Tags

### Kondisi Saat Ini
- Labels sudah fully implemented: CRUD, filter, task linking
- Tidak ada tabel tags terpisah
- Labels serve the same purpose as tags

### Keputusan: Extend Labels, Jangan Buat Tags Baru

Membuat sistem tags terpisah akan:
- Membuat bingung user ("apa bedanya label dan tag?")
- Duplikasi schema, pivot, controller, dan UI
- Maintenance 2 sistem yang hampir identik

### Yang Perlu Dilakukan

#### 1. Tambah kolom `type` di `labels` table

```php
Schema::table('labels', function (Blueprint $table) {
    $table->string('type', 20)->default('label')->after('color');
});
```

Values: `label` (default) atau `tag`

#### 2. Update Label model

- Tambah `type` ke fillable
- Scope: `scopeType($query, $type)` untuk filter by type

#### 3. Update LabelController

- Filter by type di index
- Accept type di store/update

#### 4. Update Frontend

- Di `label-dialog.tsx`: tambah type selector (Label / Tag)
- Di `task-detail-drawer.tsx`: pisahkan label dan tag sections (opsional)
- Di `task-search-filters.tsx`: tambah filter by type

### File yang Diubah/Dibuat

| File | Aksi |
|------|------|
| `database/migrations/xxxx_add_type_to_labels_table.php` | BUAT |
| `app/Models/Label.php` | UBAH (tambah type ke fillable + scope) |
| `app/Http/Controllers/LabelController.php` | UBAH (filter by type) |
| `resources/js/components/label-dialog.tsx` | UBAH (tambah type selector) |
| `resources/js/components/task-detail-drawer.tsx` | UBAH (pisahkan label/tag sections) |
| `resources/js/components/task-search-filters.tsx` | UBAH (filter by type) |

### Testing

- Buat label dengan type "label" → muncul di Label section
- Buat tag dengan type "tag" → muncul di Tag section
- Filter task by tag → hanya task dengan tag itu yang muncul

---

## Feature 6: Integrations (Figma, Slack, Notion)

### Kondisi Saat Ini
- GitHub integration sudah lengkap: OAuth, webhook, branch creation
- Integrations table: `workspace_id`, `project_id`, `provider`, tokens (encrypted), metadata
- Pattern: project-scoped, one per provider per project
- Socialite drivers: GitHub (built-in), Slack (built-in), Figma/Notion (perlu community package)

### Yang Perlu Dibuat

#### A. Slack Integration

**Backend:**
1. Install `socialiteproviders/slack` package
2. Config: `config/services.php` tambah `slack` config
3. Controller: `SlackAuthController` (mirror `GitHubAuthController`)
4. Webhook: `SlackWebhookController` + `ProcessSlackWebhookJob`
5. Routes: auth, callback, destroy, webhook

**Frontend:**
1. `slack-settings-tab.tsx`: Connect/Disconnect, channel selector, notification settings
2. Task detail: Show Slack thread links

**Features:**
- Receive task updates in Slack channel
- Create tasks from Slack messages
- Slash commands: `/task assign`, `/task status`

#### B. Figma Integration

**Backend:**
1. Install `socialiteproviders/figma` package
2. Config: `config/services.php` tambah `figma` config
3. Controller: `FigmaAuthController` (mirror GitHub)
4. Webhook: `FigmaWebhookController` + `ProcessFigmaWebhookJob`
5. Routes: auth, callback, destroy, webhook

**Frontend:**
1. `figma-settings-tab.tsx`: Connect/Disconnect, file selector
2. Task detail: Show linked Figma files/frames

**Features:**
- Link Figma files to tasks
- Show design thumbnails in task detail
- Auto-link when Figma file name matches task code

#### C. Notion Integration

**Backend:**
1. Install `socialiteproviders/notion` package
2. Config: `config/services.php` tambah `notion` config
3. Controller: `NotionAuthController` (mirror GitHub)
4. Webhook: `NotionWebhookController` + `ProcessNotionWebhookJob`
5. Routes: auth, callback, destroy, webhook

**Frontend:**
1. `notion-settings-tab.tsx`: Connect/Disconnect, database selector
2. Task detail: Show linked Notion pages

**Features:**
- Link Notion pages to tasks
- Sync task status with Notion database
- Create Notion pages from tasks

### File yang Diubah/Dibuat (per integrasi)

| File | Aksi |
|------|------|
| `app/Http/Controllers/SlackAuthController.php` | BUAT |
| `app/Http/Controllers/SlackWebhookController.php` | BUAT |
| `app/Jobs/ProcessSlackWebhookJob.php` | BUAT |
| `resources/js/components/slack-settings-tab.tsx` | BUAT |
| `routes/web.php` | UBAH |
| `config/services.php` | UBAH |
| `app/Models/Integration.php` | UBAH (tambah slackApi method) |

(Dobel untuk Figma dan Notion dengan prefix yang sesuai)

### Testing per Integrasi

- Connect Slack → OAuth flow selesai → integration tersimpan
- Kirim message di Slack dengan task code → comment ditambahkan di task
- Connect Figma → OAuth flow selesai → integration tersimpan
- Link Figma file ke task → file info muncul di task detail

---

## Execution Order

### Phase 1: Quick Wins (1-2 jam)
1. Fix `Project::savedFilters()` bug
2. Feature 5: Tags (tambah kolom type ke labels)
3. Feature 1: Automation Wiring (pasang event listeners)

### Phase 2: Board Enhancement (2-3 jam)
4. Feature 2: Scrum Sprint Board (filter + swimlane)

### Phase 3: Dependency (2-3 jam)
5. Feature 3: Dependency Tracking (Gantt arrows + blocking warnings)

### Phase 4: Notification Rules (3-4 jam)
6. Feature 4: Notification Rules (schema + engine + UI)

### Phase 5: Integrations (4-5 jam)
7. Feature 6a: Slack Integration
8. Feature 6b: Figma Integration
9. Feature 6c: Notion Integration

---

## Total Estimasi

| Fase | Estimasi |
|------|----------|
| Phase 1: Quick Wins | 1-2 jam |
| Phase 2: Board Enhancement | 2-3 jam |
| Phase 3: Dependency | 2-3 jam |
| Phase 4: Notification Rules | 3-4 jam |
| Phase 5: Integrations | 4-5 jam |
| **Total** | **~12-17 jam** |
