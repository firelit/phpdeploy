Overview
========
This is a simple PHP deploy script. It clones a repository locally, checks out the specified tag and re-links the root web folder so the actual switch to the new production code bases is instantaneous (as long as it takes to remove one symbolic link and create a new one). Falling back to a previous version is also nearly instantaneous if the old version has not been removed from the server.

Usage
=====
Requires PHP version 5.3 (untested below)

Command Line Format
-------------------
`php deploy.php repo-tag`

Example
-------
`php deploy.php v3.5`

Config File
===========
A config file is required as defined at the `CONFIG_FILE` location (defaults to `/var/www/deploy.json`).

Should be JSON and contain the following keys.
- repo: the repo's SSH link (eg, "git@github.com:myusername/myrepositoryname.git")
- cmds: array of shell commands to run just before deployment (use {FOLDER} to reference deployment folder)

Notes
=====
Uses git, thus git must be installed on the server. It is also beneficial to have setup deployment keys on the repository so that no git/ssh login is required when cloning. Alternatively, may be able to use [SSH agent forwarding](https://help.github.com/articles/using-ssh-agent-forwarding) with this script, but it has not been tested.