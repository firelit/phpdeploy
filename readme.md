## PHP Deploy 
This is a simple PHP deploy script. It clones a git repository locally, checks out the specified tag and re-links the root web folder to the new code. The the actual switch from old code to new code is quite quick: it only takes the time necessary to remove a symbolic link and create a new one. Reverting back to a previous version is also quite speedy as long as the old version has not been removed from the server.

## Usage
Requires PHP >= 5.3 (earlier versions untested)

### Command Line Format
At the command line call the deploy script and specify the tag (eg, v3.5), the commit (eg, da83f01) or the branch to deploy. If you don't specify any of these, the latest code (HEAD) will be used.

`php deploy.php deploy [options] <tag|commit|branch>`

### Command Line Options
Run the script without a command (or with the `-h` switch) to see all of the run-time options.

`php deploy.php`

### Examples
Ideally, you'll tag your releases with version numbers. Your deployment can then reference the tag.

`php deploy.php deploy v3.5`

You can rollback a deployment quickly with 'rollback'. It simply reverts to the previously deployed version. The script keeps a JSON history file in order to facilitate quick roll-backs.

`php deploy.php rollback`

### Config File
A config file is required and its location can be specified with the `-c` option. If not specified it automatically checks a few places for this file including `/var/www/deploy.json` and `deploy.json` in the current working directory.

The config file should be in JSON format and contain the following keys.
- repo: the repo's SSH link (eg, "git@github.com:myusername/myrepositoryname.git")
- cmds: array of shell commands to run just before deployment (use {FOLDER} to reference deployment folder)
- history: (optional) the file used for keeping deployment history
- web-root: (optional) the location of the web server's root folder

Example deploy.json config file:
```js
{
  "repo":"git@github.com:myname\/myrepo.git",
  "cmds":[
    "cd {FOLDER} && curl -sS https:\/\/getcomposer.org\/installer | php",
    "cd {FOLDER} && php composer.phar install",
    "rm -f {FOLDER}\/composer.*",
    "rm -rf {FOLDER}\/.git"
  ]
}
```

## Notes
Uses git, thus git must be installed on the server. It is also beneficial to have setup deployment keys on the repository so that a github/ssh login is not required when cloning. Alternatively, you may be able to use [SSH agent forwarding](https://help.github.com/articles/using-ssh-agent-forwarding) with this script, but it has not been tested.

When cloning a repo for deployment a shallow clone is made. This increases speed because very little history is being copied with the repo. This is appropriate for most uses. It also means that you shouldn't treat it like a normal repo. If you make changes to the repo and want to share, you'll have to do so as a patch instead of a normal git push.

### License

[Open Source, MIT: http://opensource.org/licenses/MIT](http://opensource.org/licenses/MIT)
