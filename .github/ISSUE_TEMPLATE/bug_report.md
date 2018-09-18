---
name: Bug report
about: Create a report to help us improve

---

### What is going wrong?
**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected behavior**
A clear and concise description of what you expected to happen.

**Screenshots**
If applicable, add screenshots to help explain your problem.

### Information about your polls installation
**Polls version?** (see apps page)

**Fresh installation or update from a prior version (from which one)?**

**How did you install this version?(Appstore or describe installation)**

### Information about your Instance of Nextcloud/ownCloud
**Nextcloud or ownCloud?**

**Which Version?**

**Nextcloud/ownCloud version:** (see Nextcloud admin page)

**List of activated apps:**

```
If you have access to your command line run e.g.:
sudo -u www-data php occ app:list
from within your Nextcloud installation folder
```
**Nextcloud configuration:**

```
If you have access to your command line run e.g.:
sudo -u www-data php occ config:list system
from within your Nextcloud installation folder

or

Insert your config.php content here
Make sure to remove all sensitive content such as passwords. (e.g. database password, passwordsalt, secret, smtp password, …)
```

### Server configuration
<!--
You can use the Issue Template application to prefill most of the required information: https://apps.nextcloud.com/apps/issuetemplate
-->

**Operating system**:

**Web server:**

**Database:**

**PHP version:**

**Signing status:**

```
Login as admin user into your Nextcloud and access
http://example.com/index.php/settings/integrity/failed
paste the results here.
```

**Are you using an external user-backend, if yes which one:** LDAP/ActiveDirectory/Webdav/...

### Client configuration
**Device:**
Desktop/mobile phone/ tablet/... 

**Browser:**

**Operating system:**

### Logs

#### Nextcloud log (data/nextcloud.log)
```
Insert your Nextcloud log here
```

#### Browser log
```
Insert your browser log here, this could for example include:

a) The javascript console log
b) The network log
c) ...
```
