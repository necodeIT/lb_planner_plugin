# LB Planner Sync-Only 2.0 Migration

This document records the contract and destructive migration implemented for
release `2.0.0` (`202607260000`). The data-provider contract and removal of LB
Planner's legacy backend ship together in this single major release. The
repository is release-ready, but this change does not tag, publish, or deploy it.

> **Mandatory backup:** back up the complete Moodle database before upgrading.
> All legacy LB Planner application data is permanently discarded without
> migration or a compatibility path.

## Target architecture

The plugin becomes a token-scoped, read-only Moodle data provider. EduPlanner
owns planning, calendars, kanban state, preferences, and synchronization
history. The plugin remains responsible for Moodle-native enrollment, group,
activity, completion, submission, attempt, and activity-classification queries.
Sentry remains as the sole runtime integration: it records uncaught errors and
sampled performance transactions around every sync API call without changing
the API result. The legacy panic switch is removed.

Contract version `1` exposes exactly these functions:

- `local_lbplanner_sync_get_identity`
- `local_lbplanner_sync_get_courses`
- `local_lbplanner_sync_get_assignments`
- `local_lbplanner_sync_get_quizzes`

Register all four in `db/services.php` as read-only functions. They require the
token owner to be logged in and hold the dedicated, system-level
`local/lb_planner:sync` capability. They never accept another user ID.

## Wire contract

All field names and types below are exact. IDs are decimal Moodle identifiers
encoded as strings so consumers do not lose precision. Timestamps are UTC Unix
seconds. A missing date is JSON `null`; zero Moodle dates must be normalized to
`null`.

### Identity

`local_lbplanner_sync_get_identity` returns:

```json
{
  "siteUrl": "https://school.example/moodle",
  "remoteUserId": "42",
  "displayLabel": "Ada Student",
  "pluginRelease": "2.0.0",
  "contractVersion": 1,
  "supportedResources": [
    "assignments",
    "courses",
    "groups",
    "quizzes"
  ]
}
```

- `siteUrl` is Moodle's canonical HTTPS site URL without a trailing slash.
- `displayLabel` is a safe non-empty user label.
- `supportedResources` is sorted, contains no duplicates, and includes all four
  values shown above.

### Courses and groups

`local_lbplanner_sync_get_courses` returns a JSON array sorted lexicographically
by `externalId`:

```json
[
  {
    "externalId": "12",
    "name": "Mathematics",
    "createdAt": 1714521600,
    "startDate": 1714521600,
    "endDate": null,
    "url": "https://school.example/moodle/course/view.php?id=12",
    "groups": [
      {
        "externalId": "3",
        "name": "2A"
      }
    ]
  }
]
```

Each `groups` array is the token owner's complete membership snapshot for that
course, sorted lexicographically by group `externalId`. Do not read or apply the
legacy disabled-course preference.

### Assignments and quizzes

The assignment and quiz functions return separate arrays with the same shape.
Rows are sorted first by `courseExternalId`, then by `externalId`,
lexicographically:

```json
[
  {
    "externalId": "81",
    "courseExternalId": "12",
    "courseModuleId": "340",
    "name": "Chapter exercise",
    "description": "",
    "deadline": 1770000000,
    "url": "https://school.example/moodle/mod/assign/view.php?id=340",
    "completionState": 0,
    "submitted": false,
    "finishedAttempt": false,
    "classificationKey": "GK"
  }
]
```

- Assignment `deadline` comes from `assign.duedate`; quiz `deadline` comes from
  `quiz.timeclose`.
- `completionState` is Moodle's integer completion state, or `0` when activity
  completion is unavailable.
- Assignment rows set `submitted` from the current user's submitted state and
  always set `finishedAttempt` to `false`.
- Quiz rows set `finishedAttempt` when the current user has a finished attempt
  and always set `submitted` to `false`.
- `classificationKey` is the raw custom activity-field selection. Missing or
  empty selections are returned as `null`.
- URLs are absolute HTTPS URLs under `siteUrl`.

The activity instance ID is the resource identity. Assignment and quiz
identities remain separate even if their numeric IDs match.

## Classification and quiz work

Generalize the custom activity-field lookup currently used for assignments so
it accepts both `assign` and `quiz` course modules. Preserve these values:

| Key | EduPlanner meaning |
| --- | --- |
| `GK` | Required, non-exam |
| `EK` | Optional, non-exam |
| `TEST` | Required, exam |
| `M` | Optional, non-exam |
| Missing | Treated as `GK` by EduPlanner |

Do not infer exam status from Moodle module type: a `GK` quiz is not an exam,
and a `TEST` assignment is an exam. Unknown stored values must fail the complete
resource function rather than silently filtering or coercing a row.

Quiz discovery must honor Moodle visibility and availability for the token
owner, include the same classification and completion data as assignments, and
query finished attempts without exposing other users.

## Snapshot and error guarantees

Every successful resource response is complete and deterministic:

- Include every visible enrolled course, group membership, assignment, or quiz
  regardless of legacy plan membership, kanban state, course preference, or the
  `ekenabled` setting.
- Emit no duplicate external identity.
- Emit no task whose `courseExternalId` is absent from the complete course
  snapshot.
- Return an exception for an incomplete query, unsupported custom-field value,
  or malformed Moodle reference. Never return a truncated success response.
- Keep functions read-only and free of writes to legacy LB Planner tables.

EduPlanner rejects responses that are oversized, unsorted, duplicated,
cross-origin, incomplete, or incompatible. A rejected response is not
reconciled and archives nothing.

## Implemented release sequence

1. Add contract constants and PHPUnit fixtures for identity, courses/groups,
   assignments, quizzes, classifications, ordering, visibility, and failures.
2. Add dedicated sync external-service classes and declare exact Moodle
   external parameter/return structures.
3. Extract reusable course-module classification logic from assignment-only
   models and add quiz support.
4. Implement token-owner course/group snapshots without consulting plugin
   preference tables.
5. Implement assignment and quiz snapshots and deterministic ordering.
6. Bump the plugin to `2.0.0`, advertise contract version `1`, and prepare the
   repository for `moodle-plugin-ci` without publishing or deploying it.

## Destructive legacy removal in 2.0.0

The same guarded `2.0.0` upgrade removes the old planner backend without a data
compatibility path:

- Remove legacy plan, invitation, deadline, kanban, notification, slot,
  reservation, course-preference, and user-preference web services.
- Remove their service classes, models, helpers, settings, scheduled work, and
  capabilities that are not needed by the sync contract.
- Retain the focused Sentry error/performance wrapper and its DSN, environment,
  and trace-sampling settings. Preserve existing DSN and environment values
  during upgrade; remove the panic switch.
- Retain the plugin component, classification-field installation/configuration,
  sync capability, four sync functions, and minimal Moodle query helpers.
- Delete all 12 legacy plugin tables child-first through one explicit Moodle
  upgrade step. Fresh installs own no plugin tables because classification data
  is stored by Moodle's core custom-field subsystem. Legacy data is not
  migrated.
- Document the destructive upgrade prominently and require a database backup
  before installation.
- Update generated API documentation so only the sync contract remains.

The removal release must repeat the contract tests and verify that none of the
four sync functions reads a removed table.

## Acceptance criteria

- A contract-v1 token returns its own identity and all four advertised
  resources.
- Disabled courses and `ekenabled` never filter a sync result.
- Assignments and quizzes share the documented shape and retain separate
  identities.
- `GK`, `EK`, `TEST`, `M`, and missing classification behave as documented.
- A quiz with `GK` remains non-exam; an assignment with `TEST` is an exam.
- Duplicate, unknown-classification, partial, or unauthorized snapshots fail as
  a unit.
- All successful arrays and nested group arrays use deterministic ordering.
- The destructive 2.0 release leaves no legacy planner/write service or legacy
  application table.
