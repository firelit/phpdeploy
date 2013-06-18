## Overview
This is a simple PHP deploy script. It clones a git repository locally, checks out the specified tag and re-links the root web folder to the new code. The the actual switch from old code to new code is quite quick: it only takes the time necessary to remove a symbolic link and create a new one. Reverting back to a previous version is also quite speedy as long as the old version has not been removed from the server.

## Usage
Requires PHP >= 5.3 (earlier versions untested)

### Command Line Format
At the command line call the deploy script and specify the tag (eg, v3.5), the commit (eg, da83f01) or the branch to deploy. If you don't specify any of these, the latest code (HEAD) will be used.

`php deploy.php (options) (tag|commit|branch)`

### Example
Ideally, you'll tag your releases with version numbers. Your deployment can then reference the tag.

`php deploy.php v3.5`

### Command Line Options
- `-f <json config file>` : Used to specify the location of the configuration file, defaults to `/var/www/deploy.json`.
- `-w <web root>` : Used to specify the root public folder for the web server (will be replaced with symbolic link), defaults to `/var/www/html`.

### Config File
A config file is required and its location can be specified with the `-f` option (defaults to `/var/www/deploy.json`).

The file should be in JSON format and contain the following keys.
- repo: the repo's SSH link (eg, "git@github.com:myusername/myrepositoryname.git")
- cmds: array of shell commands to run just before deployment (use {FOLDER} to reference deployment folder)

## Notes
Uses git, thus git must be installed on the server. It is also beneficial to have setup deployment keys on the repository so that a github/ssh login is not required when cloning. Alternatively, you may be able to use [SSH agent forwarding](https://help.github.com/articles/using-ssh-agent-forwarding) with this script, but it has not been tested.

### License

[Open Source, MIT: http://opensource.org/licenses/MIT](http://opensource.org/licenses/MIT)
