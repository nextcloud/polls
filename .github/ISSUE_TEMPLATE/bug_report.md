---
name: Bug report
about: Create a report to help us improve

---

## What is going wrong? What did you observe?
**Describe the bug**

A clear and concise description of what is wrong.

**Steps to reproduce the behavior**
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Add a screenshot of the misbehavior**

If applicable, add screenshots to help explain your problem.

## How should it work?
**What you expected to happen?**

A clear and concise description of

## Information about your polls installation
**Polls version:** (see apps page)

**Fresh installation or update from a prior version (from which one)?**
<!--
Put an x between the brackets ([x]) or check these boxes after you created the issue
-->
- [ ] First time install
- [ ] Update from a prior version
  Version number of the previous version:

**How did you install this version?(Appstore or describe installation)**
<!--
Put an x between the brackets ([x]) or check these boxes after you created the issue
-->
- [ ] Installed from the appstore
- [ ] Installed via occ
- [ ] Installed via extracting downloaded package to the apps folder

## Information about your Instance of Nextcloud
**Nextcloud version:** (see Nextcloud admin page)

<details>
<summary>List of activated apps</summary>

```
If you have access to your command line run e.g.:
sudo -u www-data php occ app:list
from within your Nextcloud installation folder
```

</details>
<details>
<summary>Nextcloud Configuration</summary>

```
If you have access to your command line run e.g.:
sudo -u www-data php occ config:list system
from within your Nextcloud installation folder

or

Insert your config.php content here
Make sure to remove all sensitive content such as passwords. (e.g. database password, passwordsalt, secret, smtp password, …)
```
</details>

### Server configuration (decide, if you think it is helpful)
<!--
You can use the Issue Template application to prefill most of the required information: https://apps.nextcloud.com/apps/issuetemplate
-->

**Database:**
- [ ] MySql version:
- [ ] MariaDB version:
- [ ] PostgreSQL version:
- [ ] Oracle version:
- [ ] SQLite:
- [ ] Other (add name and version):

**PHP version:**
- [ ] 7.3 or lower
- [ ] 7.4
- [ ] 8.0

**Are you using an external user-backend, if yes which one:** LDAP/ActiveDirectory/Webdav/...

### Client configuration
**Device:** <!-- Put an x between the brackets ([x]) or check these boxes after you created the issue -->
- [ ] PC
- [ ] Mac
- [ ] Mobile phone
- [ ] Tablet

**Browser:** <!-- Add your Browser's version and put an x between the brackets ([x]) or check these boxes after you created the issue -->

- [ ] Firefox version:
- [ ] Chrome version:
- [ ] Safari version:
- [ ] Other (add name and version):

## Logs

<details>
<summary>Nextcloud log (data/nextcloud.log)</summary>

```
Insert your Nextcloud log here
```
</details>

<details>
<summary>Browser console log</summary>

```
Insert your browser console log here
```

</details>

<details>
<summary>Other browser logs</summary>

```
Insert additional logs from your browser here
```
</details>
