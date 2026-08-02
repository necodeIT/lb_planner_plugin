# LB Planner Moodle Plugin

LB Planner 2.0 is a token-scoped, read-only Moodle data provider for EduPlanner. It exposes contract version 1 through exactly four web-service functions:

- `local_lbplanner_sync_get_identity`
- `local_lbplanner_sync_get_courses`
- `local_lbplanner_sync_get_assignments`
- `local_lbplanner_sync_get_quizzes`

The generated API documentation is available [here](https://necodeit.github.io/lb_planner_docs/moodle/index.html).

## Mandatory 2.0 upgrade warning

**Back up the Moodle database before installing LB Planner 2.0.0.** The upgrade permanently drops all legacy LB Planner plans, invitations, deadlines, kanban entries, notifications, course preferences, reservations, slots, supervisors, and user preferences. There is no migration or compatibility API, and the discarded data cannot be recovered from the plugin.

The activity-classification custom field and its `GK`, `EK`, `TEST`, and `M` data are retained. Any existing global `eduplanner` course tag is left untouched, but 2.0 neither creates nor reads it.

## Requirements

- Moodle 4.4
- PHP 8.1 or later (through Moodle 4.4)
- [`local_modcustomfields`](https://gitlab.com/adapta/moodle-local_modcustomfields/) version `2023110600` or later
- An HTTPS Moodle `$CFG->wwwroot`

The plugin owns no database tables after installation. Sentry observability is optional at runtime; release packages bundle the
Sentry SDK, but no events or performance traces are sent until an administrator configures a DSN.

## Sentry observability

Configure Sentry under **Site administration → Plugins → Local plugins → LB Planner Sync API**. The integration captures uncaught
errors and performance transactions across every LB Planner sync API call. It identifies the authenticated Moodle user only by
their string-encoded user ID, disables Sentry's default personally identifiable information collection, and excludes request
bodies from events.

Existing `sentry_dsn` and `sentry_environment` values are preserved by the destructive 2.0 upgrade. The trace sample rate defaults
to 20% and can be changed independently. A Sentry SDK or transport failure is reported only through Moodle developer debugging and
never replaces the original sync response or exception.

## Development setup

One supported local layout is:

```
-root ← top folder. any name works
 |-plugin ← this folder
 |-moodle ← folder with moodle in it
   |-local ← moodle's plugin folder
     |-lbplanner ← symlink to the lbplanner folder
     |-modcustomfields ← a dependency of ours (get at https://gitlab.com/adapta/moodle-local_modcustomfields/)
```

Run the configured `moodle-plugin-ci` workflow against the `lbplanner` directory. Generate or validate the external-service documentation with:

```shell
python3 document_services.py -
```

## Packaging

Run `composer install --no-dev --prefer-dist` from the repository root, then zip the `lbplanner` directory itself. The release
workflow performs this dependency step automatically.
