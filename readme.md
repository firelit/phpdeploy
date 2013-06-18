Overview
--------
This is a simple PHP deploy script. It clones a repository locally, checks out the specified tag and re-links the root web folder to the new code. The the actual switch from old code to new code is quite quick: it only takes the time necessary to remove a symbolic link and create a new one. Reverting back to a previous version is also quite speedy as long as the old version has not been removed from the server.

Usage
-----
Requires PHP version 5.3 (untested below)

### Command Line Format
`php deploy.php repo-tag`

### Example
`php deploy.php v3.5`

Config File
-----------
A config file is required at the defined `CONFIG_FILE` location (defaults to `/var/www/deploy.json`).

Should be JSON and contain the following keys.
- repo: the repo's SSH link (eg, "git@github.com:myusername/myrepositoryname.git")
- cmds: array of shell commands to run just before deployment (use {FOLDER} to reference deployment folder)

Notes
-----
Uses git, thus git must be installed on the server. It is also beneficial to have setup deployment keys on the repository so that no github/ssh login is required when cloning. Alternatively, you may be able to use [SSH agent forwarding](https://help.github.com/articles/using-ssh-agent-forwarding) with this script, but it has not been tested.