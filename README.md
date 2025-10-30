# LB Planer Plugin

This repository contains the source code for the LB Planer Moodle plugin. Api endpoints are documented [here](https://necodeit.github.io/lb_planner_docs/moodle/index.html)

## Setup

### Bundled Dependencies

Run `composer up` here to download dependencies to the vendor folder.
Note that dependencies already present in moodle are excluded from the tree.

### Dev Env

You can do this multiple ways, but we personally have a directory structure like so:

```
-root ← top folder. any name works
 |-plugin ← this folder
 |-moodle ← folder with moodle in it
   |-local ← moodle's plugin folder
     |-lbplanner ← symlink to the lbplanner folder
     |-modcustomfields ← a dependency of ours (get at https://gitlab.com/adapta/moodle-local_modcustomfields/)
```

Using [kate](https://kate-editor.org/) with [intelephense](https://intelephense.com/) is recommended.

## Packaging

Zip the lbplanner folder. as shrimple as that.