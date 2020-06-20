
To act like a authenticated user, use username:password@ prefix in the URL
Parameters in the body override the URL-parameter

Base URL for all calls: /index.php/apps/polls/api/1.0/
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

# Options
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/options      | Get options                  | 200, 403, 404      |
| POST      | /api/v1.0/option                     | Add new option with Payload  | 201, 403, 404, 409 |
| PUT       | /api/v1.0/option                     | Update option with Payload   | 200, 403, 404      |
| DELETE    | /api/v1.0/option/{optionId}          | Delete option                | 200, 403, 404      |

# Votes
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/votes        | Get votes                    | 200, 403, 404      |
| POST      | /api/v1.0/vote                       | Set vote with Payload        | 200, 403, 404      |

# Comments
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/comments     | Get comments                 | 200, 403, 404      |
| POST      | /api/v1.0/comment                    | Add new commen twith Payload | 201, 403, 404      |
| DELETE    | /api/v1.0/comment/{commentId}        | Delete comment               | 200, 403, 404      |

# Shares
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/shares       | Get shares                   | 200, 403, 404      |
| GET       | /api/v1.0/share/{token}              | Get share by token           | 200, 403, 404      |
| POST      | /api/v1.0/share                      | Add new share with Payload   | 201, 403, 404      |
| DELETE    | /api/v1.0/share/{token}              | Delete share                 | 200, 404, 409      |

# Subscription
| Method    | Endpoint                             | Description                  | Return codes       |
| --------- | -----------------------------------  | ---------------------------- | ------------------ |
| GET       | /api/v1.0/poll/{pollId}/subscription | Get subscription status      | 200, 403, 404      |
| PUT       | /api/v1.0/poll/{pollId}/subscription | Subcribe                     | 201, 403           |
| DELETE    | /api/v1.0/poll/{pollId}/subscription | unsubscribe                  | 200, 403           |
