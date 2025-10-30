<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# DRAFT

This documentation and the API are not final and may contain issues and bugs!
Details may change!

To act like a authenticated user, use username:password@ prefix in the URL
Parameters in the body override the URL-parameter

Base URL for all calls: /index.php/apps/polls
Example calls:
* Gets all comments of poll no. 1
`https://username:password@nextcloud.local/index.php/apps/polls/api/v1.0/poll/1/comments`
```bash
`curl -u username:password -X GET https://nextcloud.local/index.php/apps/polls/api/v1.0/poll/1/comments`
```
* Create a new poll
```bash
`curl  -u username -X POST https://nextcloud.local/index.php/apps/polls/api/v1.0/poll -H "Content-Type: application/json;charset=utf-8" -d "{\"title\": \"New poll\", \"type\": \"datePoll\"}"`
```

# Poll
## Default functions
| Method    | Endpoint                 | Payload | Description            | Return codes       | Return value   |
| --------- | ------------------------ | ------- | ---------------------- | ------------------ | -------------- |
| GET       | /api/v1.0/polls          | no      | Get array of polls     | 200, 403, 404      | array          |
| GET       | /api/v1.0/poll/{pollId}  | no      | Get poll with {pollId} | 200, 403, 404      | requested poll |
| POST      | /api/v1.0/poll           | yes     | Add new poll           | 201, 403, 404      | added poll     |
| PUT       | /api/v1.0/poll/{pollId}  | yes     | Update poll            | 200, 403, 404, 409 | updated poll   |
| DELETE    | /api/v1.0/poll/{pollId}  | no      | Delete poll            | 200, 403, 404      | deleted poll   |


## Special functions
| Method    | Endpoint                       | Payload | Description                  | Return codes       | Return value   |
| --------- | ------------------------------ | ------- | ---------------------------- | ------------------ | -------------- |
| POST      | /api/v1.0/poll/{pollId}/clone  | no      | Clone poll from {pollId}     | 201, 403, 404      | cloned poll    |
| POST      | /api/v1.0/poll/{pollId}/trash  | no      | Move to/remove from trash    | 200, 403, 404      | updated poll   |
| GET       | /api/v1.0/enum/poll            | no      | Get valid enums              | 200, 403, 404      | array          |
| PUT       | /api/v1.0/poll/{pollId}/close  | no      | Close poll with {pollId}     | 200. 403, 404      | closed poll    |
| PUT       | /api/v1.0/poll/{pollId}/reopen | no      | Close poll with {pollId}     | 200. 403, 404      | reopened poll  |

## Valid payloads
### Add new poll
```json
{
    "type": "datePoll",
    "title": "Poll Title"
}
```

### Update poll
Send the full or a partial structure of "configuration" (see return strucure below).
`expire` field is set to 0 to make it an endless poll (without an expiration date). This field can be set to a date in the future to automatically close the poll on that date.
A poll can be closed immediately by using the endpoint `poll/{pollId}/close` or by setting `expire` to a negative number and reopened by using `poll/{pollId}/reopen` or by setting `expire` to 0.
```json
{
    "poll": {
        "title": "Changed Title",
        "description": "Updated description",
        "expire": 0,
        "access": "private",
        "anonymous": true,
        "allowMaybe": true,
        "allowComment": true,
        "allowProposals": true,
        "showResults": "never",
		"autoReminder": false,
		"hideBookedUp": false,
		"proposalsExpire": 0,
		"useNo": true,
		"maxVotesPerOption": 0,
		"maxVotesPerUser": 0
   }
}
```
## Return value
A poll newly created will look like this.
#### Notice: Only the attributes of the "configuration" section are changeable.

```json
{
	"poll": {
		"id": 1,
		"type": "datePoll",
	    "configuration": {
			"title": "New Poll",
			"description": "Description of poll",
			"access":"private",
			"allowComment":false,
			"allowMaybe":false,
			"allowProposals":"",
			"anonymous":false,
			"autoReminder":false,
			"expire":0,
			"hideBookedUp":false,
			"proposalsExpire":0,
			"showResults":"always",
			"useNo":false,
			"maxVotesPerOption":0,
			"maxVotesPerUser":0
		},
		"descriptionSafe": "Description of poll",
		"owner": {
			"userId": "username",
			"displayName": "Username",
			"emailAddress": "username@example.com",
			"subName": "User",
			"subtitle": "User",
			"isNoUser": false,
			"desc": "User",
			"type":"user",
			"id":"username",
			"user":"Username",
			"organisation":"",
			"languageCode":"en",
			"localeCode":"en",
			"timeZone":"UTC",
			"icon":"icon-user",
			"categories":[]
		},
		"status": {
			"lastInteraction":1714078369,
			"created":1714078369,
			"deleted":false,
			"expired": false,
			"relevandThreshold" : 1714078369
		},
		"currentUserStatus": {
			"userRole":"owner",
			"isLocked":false,
		    "isLoggedIn": true,
			"isNoUser": false,
			"isOwner": true,
			"userId": "username",
			"orphanedVotes":0,
			"yesVotes":0,
			"countVotes":0,
			"shareToken": "",
			"groupInvitations": {
				"1": "Users",
				"2": "Administrators",
			},
		},
		"permissions": {
			"addOptions": true,
			"archive": true,
			"comment": true,
			"delete": true,
			"edit": true,
			"seeResults": true,
			"seeUsernames": true,
			"subscribe": true,
			"view": true,
			"vote": true
			}
	}
}
```
### Keys and values
| Key         | Type    | description                                                        |
| ----------- | ------- | ------------------------------------------------------------------ |
| expire      | integer | Unix timestamp (0 if no expiration date)                           |
| access      | string  | "open" if anyone can access it, "private" otherwise                |
| showResults | string  | "never", "always" or "closed" (to show when the poll is closed)    |
| type        | string  | "textPoll" or "datePoll"                                           |

# Acl
## Default functions
| Method    | Endpoint                     | Payload | Description            | Return codes       | Return value   |
| --------- | ---------------------------- | ------- | ---------------------- | ------------------ | -------------- |
| GET       | /api/v1.0/poll/{pollId}/acl  | no      | Get acl for {pollId}   | 200, 403, 404      | requested acl  |

## Return value
```json
{
	"acl": {
		"pollId":1,
		"pollExpired":false,
		"pollExpire":0,
		"currentUser": {
			"displayName":"Username",
			"hasVoted":false,
			"isInvolved":true,
			"isLoggedIn":true,
			"isNoUser":false,
			"isOwner":true,
			"userId":"username"
		},
		"permissions": {
			"addOptions":true,
			"allAccess":true,
			"archive":true,
			"comment":true,
			"delete":true,
			"edit":true,
			"pollCreation":true,
			"pollDownload":true,
			"publicShares":true,
			"seeResults":true,
			"seeUsernames":true,
			"seeMailAddresses":true,
			"subscribe":false,
			"view":true,
			"vote":true
		}
	}
}
```

# Options
## Default functions
| Method    | Endpoint                        | Payload  | Description       | Return codes       | Return value   |
| --------- | ------------------------------- | -------  | ----------------- | ------------------ | -------------- |
| GET       | /api/v1.0/poll/{pollId}/options | no       | Get poll options  | 200, 403, 404      | array          |
| POST      | /api/v1.0/poll/{pollId}/option  | yes      | Add new option    | 201, 403, 404, 409 | added option   |
| PUT       | /api/v1.0/option/{optionId}     | yes      | Update option     | 200, 403, 404      | updated option |
| DELETE    | /api/v1.0/option/{optionId}     | no       | Delete option     | 200, 403, 404      | deleted option |

## Special functions (no payloads)
| Method    | Endpoint                                     | Description                   | Return codes       | Return value     |
| --------- | -------------------------------------------- | ----------------------------- | ------------------ | ---------------- |
| PUT       | /api/v1.0/option/{optionId}/confirm          | Confirm/unconfirm option      | 200, 403, 404      | confirmed option |
| PUT       | /api/v1.0/option/{optionId}/setorder/{order} | Set order (text poll)         | 200, 403, 404      | array            |

## Valid payloads
### Add/update option (text poll)

```json
{
    "pollOptionText": "Text of new option"
}
```

### Add/update option (date poll)
Add a single option
```json
{
	"option":
    {
		"text": "",
		"timestamp": 1589195823,
	    "duration": 1800
	}
}
```
Add with sequence options (like in the UI)
````json
{
	"option":
	{
		"text":"",
		"timestamp":1761343200,
		"duration":86400
	},
	"sequence":
	{
		"unit":
		{
			"id":"day",
			"value":"day",
			"name":"Day",
			"timeOption":false
		},
		"stepWidth":1,
		"repetitions":0
	},
	"voteYes":false
}
````

### Keys and values
| Key            | Type    | description             |
| -------------- | ------- | ----------------------- |
| pollOptionText | String  | poll text               |
| timestamp      | Integer | 10 digit unix timestamp |
| duration       | Integer | duration in seconds     |



# Votes
| Method    | Endpoint                               | Description                          | Return codes       |
| --------- | -------------------------------------- | ------------------------------------ | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/votes          | Get votes                            | 200, 403, 404      |
| POST      | /api/v1.0/vote                         | Set vote with Payload                | 200, 403, 404      |
| DELETE    | /api/v1.0/poll/{pollId}/user/{userId}  | Delete user from poll                | 200, 403, 404      |
| DELETE    | /api/v1.0/poll/{pollId}/user           | Delete current user from poll        | 200, 403, 404      |
| DELETE    | /api/v1.0/poll/{pollId}/votes/orphaned | Delete current user's orphaned votes | 200, 403, 404      |

## set vote
```json
{
    "optionId": 1,
    "setTo" :"yes"
}
```

# Comments
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/comments     | Get comments                 | 200, 403, 404      |
| POST      | /api/v1.0/comment                    | Add new comment with Payload | 201, 403, 404      |
| DELETE    | /api/v1.0/comment/{commentId}        | Delete comment               | 200, 403, 404      |

# Add comment
```json
{
    "pollId": 1,
    "message": "Comment text"
}
```

# Shares
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/shares       | Get shares                   | 200, 403, 404      |
| GET       | /api/v1.0/share/{token}              | Get share by token           | 200, 403, 404      |
| POST      | /api/v1.0/poll/{pollId}/share/{type} | Add new share with Payload   | 201, 403, 404      |
| DELETE    | /api/v1.0/share/{token}              | Delete share                 | 200, 404, 409      |

## Valid payloads
### Add a public share
```json
{
    "type": "public"
}
```
### Add a user share
```json
{
    "type": "user",
    "userId": "user"
}
```
### Add a email share
```json
{
    "type": "email",
    "userId": "email@example.com",
    "displayName": "email@example.com"
}
```

### Add a contact share
tbd

## Return value
### Public share
```json
{
	"share": {
		"type": "public",
		"id": 5,
		"token":"K4I4nR7C",
		"type":"public",
		"pollId":1,
		"userId":"K4I4nR7C",
		"emailAddress":"",
		"invitationSent":true,
		"reminderSent":false,
		"locked":false,
		"label":"",
		"URL":"https:\/\/example.com\/index.php\/apps\/polls\/s\/K4I4nR7C",
		"showLogin":true,
		"publicPollEmail":"optional",
		"voted":false,
		"deleted":false,
		"user" {
			"userId":"K4I4nR7C",
			"displayName":"",
			"emailAddress":"",
			"subName":"",
			"subtitle":"",
			"isNoUser":true,
			"desc":"",
			"type":"public",
			"id":"K4I4nR7C",
			"user":"K4I4nR7C",
			"organisation":"",
			"languageCode":"",
			"localeCode":"",
			"timeZone":"",
			"icon":"icon-public",
			"categories":[]
		}
	}
}
```
### User share
```json
{
	"share": {
		"id":6,
		"token":"be4ILI62",
		"type":"user",
		"pollId":1,
		"userId":"username",
		"emailAddress":"",
		"invitationSent":false,
		"reminderSent":false,
		"locked":false,
		"label":"username",
		"URL":"https:\/\/example.com\/index.php\/apps\/polls\/vote\/1",
		"showLogin":true,
		"publicPollEmail":"optional",
		"voted":false,
		"deleted":false,
		"user": {
			"userId":"username",
			"displayName":"Username",
			"emailAddress":"",
			"subName":"User",
			"subtitle":"User",
			"isNoUser":false,
			"desc":"User",
			"type":"user",
			"id":"Username",
			"user":"Username",
			"organisation":"",
			"languageCode":"de",
			"localeCode":"de",
			"timeZone":"Europe\/Berlin",
			"icon":"icon-user",
			"categories":[]
		}
	}
}
```
### Email share
```json
{
	"share": {
		"id":7,
		"token":"o4MI8tGC",
		"type":"email",
		"pollId":1,
		"userId":"email@example.com",
		"emailAddress":"email@example.com",
		"invitationSent":false,
		"reminderSent":false,
		"locked":false,
		"label":"email@example.com",
		"URL":"https:\/\/example.com\/index.php\/apps\/polls\/s\/o4MI8tGC",
		"showLogin":true,
		"publicPollEmail":"optional",
		"voted":false,
		"deleted":false,
		"user": {
			"userId":"email@example.com",
			"displayName":"email@example.com",
			"emailAddress":"email@example.com",
			"subName":"email@example.com \u003Cemail@example.com\u003E",
			"subtitle":"email@example.com \u003Cemail@example.com\u003E",
			"isNoUser":true,
			"desc":"email@example.com \u003Cemail@example.com\u003E",
			"type":"email",
			"id":"email@example.com",
			"user":"email@example.com",
			"organisation":"",
			"languageCode":"",
			"localeCode":"",
			"timeZone":"",
			"icon":"icon-mail",
			"categories":[]
		}
	}
}
```
# Subscription
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/subscription | Get subscription status      | 200, 403, 404      |
| PUT       | /api/v1.0/poll/{pollId}/subscription | Subcribe                     | 201, 403           |
| DELETE    | /api/v1.0/poll/{pollId}/subscription | unsubscribe                  | 200, 403           |
