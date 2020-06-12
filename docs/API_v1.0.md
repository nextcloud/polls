
To act like a authenticated user, use username:password@ prefix in the URL
Parameters in the body override the URL-parameter

Base URL for all calls: /index.php/apps/polls/api/1.0/
Example calls:
* Gets all comments of poll no. 1
`https://username:password@nextcloud.local/index.php/apps/polls/api/1.0/comments/1`
```bash
curl -u username:password \
  -X GET https://nextcloud.local/index.php/apps/polls/api/1.0/comments/1
```

You can add a Body with the parameters, which overrides the URL-Parameter
`https://username:password@nextcloud.local/index.php/apps/polls/api/1.0/comments/1`

```json
[
    {
        "pollId": 2,
    },

]
```

This will return all comments from poll no. 2

```json
[
    {
        "token": "X3jXHb8WHLMb9MRg",
    },

]
```

This returns all comments from tzhe poll wich can be called with the token "X3jXHb8WHLMb9MRg"


# Comments
## Get comments
### Get all Comments by poll as a nextcloud user
GET `/index.php/apps/polls/api/1.0/comments/{pollId}`

### Post a comment
POST `/index.php/apps/polls/api/1.0/comments`

Body
```json
[
    {
        "message": "Comment text",
        "pollId": 1,
        "token": "users's personal token"
    },

]
```

DELETE `/index.php/apps/polls/api/1.0/comments/{commentId}`

Body
```json
[
    {
		"commentId": 123,
		"token": "users's personal token"
    },

]
```

### Returns an array of Comment objects
```json
[
    {
        "id": 1,
        "pollId": 1,
        "userId": "Commenter's name",
        "dt": "2020-01-21 14:01:01",
        "timestamp": 1587468691,
        "comment": "message",
        "displayName": "Commenters's display name"
    }, ...

]
```
