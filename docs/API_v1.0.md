
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
| Method    | Endpoint                      | Payload | Description                  | Return codes       | Return value   |
| --------- | ------------------------------| ------- | ---------------------------- | ------------------ | -------------- |
| POST      | /api/v1.0/poll/{pollId}/clone | no      | Clone poll from {pollId}     | 201, 403, 404      | cloned poll    |
| POST      | /api/v1.0/poll/{pollId}/trash | no      | Move to/remome from trash    | 200, 403, 404      | updated poll   |
| GET       | /api/v1.0/enum/poll           | no      | Get valid enums              | 200, 403, 404      | array          |

## Valid payloads
### Add new poll
```json
{
    "type": "datePoll",
    "title": "Poll Title"
}
```

### Update poll
send the full or a partial structure
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
```json
{
	"timestamp": 1589195823
}
```

### Keys and values
| Key            | Type    | description             |
| -------------- | ------- | ----------------------- |
| pollOptionText | String  | poll text               |
| timestamp      | Integer | 10 digit unix timestamp |



# Votes
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/votes        | Get votes                    | 200, 403, 404      |
| POST      | /api/v1.0/vote                       | Set vote with Payload        | 200, 403, 404      |

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
