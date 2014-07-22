code-quality
============

This is based on the blog post at http://carlosbuenosvinos.com/write-your-git-hooks-in-php-and-keep-them-under-git-control/

The quality tools are specified in composer and will be automatically downloaded.

To Use
======

Add the package as a requirement:

    "require": {
        "philwc/code-quality": "dev-master",
    }
    
Add the scripts to your root comnposer.json (If you want to be notified about the hook not being installed)
    
    "scripts": {
        "pre-update-cmd": "philwc\\Hooks::checkHooks",
        "pre-install-cmd": "philwc\\Hooks::checkHooks"
    }
    
Install the hook
    
    rm -rf .git/hooks && ln -s ../vendor/philwc/code-quality/src/hooks ./.git/hooks
