
To act like a authenticated user, use username:password@ prefix in the URL
Parameters in the body override the URL-parameter

Base URL for all calls: /index.php/apps/polls/api/1.0/
Example calls:
* Gets all comments of poll no. 1
`https://username:password@nextcloud.local/index.php/apps/polls/api/1.0/poll/1/comments`
```bash
`curl -u username:password -X GET https://nextcloud.local/index.php/apps/polls/api/1.0/poll/1/comments`
```

# Comments
## List all comments by poll
GET `/index.php/apps/polls/api/1.0/poll/{pollId}/comments`

### Return HTTP status 200
Response body contains all comments of the poll with {pollId}

### Return HTTP status 403 - Forbidden
Authorization is missing use correct username:passwort

### Return HTTP status 404 - Not found
commentId not found

## Post a new comment
POST `/index.php/apps/polls/api/1.0/comment`

Data
```json
{
    "message": "Comment text",
    "pollId": 1,
}
```
### Return HTTP status 201 - Created
Comment successfully created
Response Body contains the comment as json

### Return HTTP status 403 - Forbidden
Authorization is missing use correct username:passwort

### Return HTTP status 404 - Not found
pollId not found

## Delete a comment
DELETE `/index.php/apps/polls/api/1.0/comment/{commentId}`

### Return HTTP status 200
Response body contains the commentId

### Return HTTP status 403 - Forbidden
Authorization is missing, use correct username:passwort

### Return HTTP status 404 - Not found
commentId not found

# Options
## List all options by poll
GET `/index.php/apps/polls/api/1.0/poll/{pollId}/options`

### Return HTTP status 200
Response body contains all options of the poll with {pollId}

### Return HTTP status 403 - Forbidden
Authorization is missing, use correct username:passwort

### Return HTTP status 404 - Not found
optionId not found

## Post a new option
POST `/index.php/apps/polls/api/1.0/option`

Data
```json
{
    "message": "Comment text",
    "pollId": 1,
}
```
### Return HTTP status 201 - Created
Comment successfully created
Response Body contains the option as json

### Return HTTP status 403 - Forbidden
Authorization is missing use correct username:passwort

### Return HTTP status 404 - Not found
pollId not found

### Return HTTP status 409 - Conflict
The option already exists in this poll

## Delete an option
DELETE `/index.php/apps/polls/api/1.0/option/{optionId}`

### Return HTTP status 200 - OK
Response body contains the optionId

### Return HTTP status 403 - Forbidden
Authorization is missing use correct username:passwort

### Return HTTP status 404 - Not found
optionId not found
