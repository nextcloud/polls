
# DRAFT

This documentation and the API are not final and may contain issues and bugs!
Details may change!

To act like a authenticated user, use username:password@ prefix in the URL
Parameters in the body override the URL-parameter

Base URL for all calls: /index.php/apps/polls
Example calls:
* Gets all comments of poll no. 1
`https://username:password@nextcloud.local/index.php/apps/polls/api/1.0/poll/1/comments`
```bash
`curl -u username:password -X GET https://nextcloud.local/index.php/apps/polls/api/1.0/poll/1/comments`
```

# Poll
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/polls                      | Get polls list as array      | 200, 403, 404      |
| GET       | /api/v1.0/poll/{pollId}              | Get poll with {pollId}       | 200, 403, 404      |
| POST      | /api/v1.0/poll/add                   | Add new poll with payload    | 201, 403, 404      |
| POST      | /api/v1.0/poll/clone/{pollId}        | Clone poll {pollId}          | 201, 403, 404      |
| PUT       | /api/v1.0/poll/{pollId}              | Update poll                  | 200, 403, 404, 409 |
| DELETE    | /api/v1.0/poll/{pollId}              | Delete poll logical          | 200, 403, 404      |
| DELETE    | /api/v1.0/poll/permanent{pollId}     | Delete poll permanently      | 200, 403, 404      |
| GET       | /api/v1.0/poll/enum                  | Get valid enums              | 200, 403, 404      |

## Add poll

```json
{
    "type": "datePoll",
    "title": "Test"
}
```

## Update poll
```json
{
    "poll": {
        "title": "Changed Title",
        "description": "Updated description",
        "expire": 0,
        "deleted": 0,
        "access": "hidden",
        "anonymous": 1,
        "allowMaybe": 1,
        "showResults": "never",
        "adminAccess": 1
    }
}
```

### Keys and values
| Key     | Type    | description        |
| ------- | ------- | -------------------|
| expire  | integer | unix timestamp     |
| deleted | integer | unix timestamp     |



# Options
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/options      | Get options as array         | 200, 403, 404      |
| POST      | /api/v1.0/option                     | Add new option with Payload  | 201, 403, 404, 409 |
| PUT       | /api/v1.0/option                     | Update option with Payload   | 200, 403, 404      |
| DELETE    | /api/v1.0/option/{optionId}          | Delete option                | 200, 403, 404      |

## add option
```json
{
    "pollId": 139,
    "pollOptionText": "19-06-2020 17:00:00",
	"timestamp": 0,
}
```

## Update option
```json
{
	"id": 17,
	"pollId": 1,
	"pollOptionText": "poll option",
	"timestamp": 0,
	"order": 1,
	"confirmed": 1590762104
},
```

### Keys and values
| Key            | Type    | description                           |
| -------------- | ------- | -------------------------------------  |
| id             | String  | id overrides optionID if used          |
| pollOptionText | String  | poll text or date option in UTC        |
| confirmed      | Integer | unix timestamp                         |
| timestamp      | Integer | unix timestamp for date option         |
| order          | Integer | position on option order for textpolls |

* if timestamp is given in a date poll, the poll option text is ignored

# Votes
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/votes        | Get votes                    | 200, 403, 404      |
| POST      | /api/v1.0/vote                       | Set vote with Payload        | 200, 403, 404      |

## set vote
```json
{
    "pollId": 1,
    "pollOptionText": "Saturn",
    "setTo" :"yes"
}
```

# Comments
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/comments     | Get comments                 | 200, 403, 404      |
| POST      | /api/v1.0/comment                    | Add new commen twith Payload | 201, 403, 404      |
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
| POST      | /api/v1.0/share                      | Add new share with Payload   | 201, 403, 404      |
| DELETE    | /api/v1.0/share/{token}              | Delete share                 | 200, 404, 409      |

# Add share

## public share
```json
{
    "type": "public",
    "pollId": 1
}
```
## user share
tbd

## email share
tbd

## contact share
tbd



# Subscription
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/subscription | Get subscription status      | 200, 403, 404      |
| PUT       | /api/v1.0/poll/{pollId}/subscription | Subcribe                     | 201, 403           |
| DELETE    | /api/v1.0/poll/{pollId}/subscription | unsubscribe                  | 200, 403           |
