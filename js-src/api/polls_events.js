const _mock_events = [
	{
		"id": 1,"hash": "12rdzh9QYiFZaFz4","type": 0,"title": "Public date poll with no expiration","description": "A date poll with a few date options. The poll is public and does not expire",
		"owner": "Admin","created": "2017-10-27 05:06:43","access": "public","expire": null,"is_anonymous": 0,"full_anonymous": 0,"disallow_maybe": 0
		},
	{
		"id": 2,"hash": "EN6l9VYT3kh6shJp","type": 1,"title": "Public test Poll with Expiration","description": "An option poll with a few options. The poll is public and expires on 2018/08/21",
		"owner": "dartcafe","created": "2017-10-27 05:10:34","access": "public","expire": "2018-08-21 22:00:00","is_anonymous": 0,"full_anonymous": 0,"disallow_maybe": 0
		},
	{
		"id": 3,"hash": "ymnMGdIEazz5DcvP","type": 0,"title": "Hidden date poll","description": "Just one option and the poll is hidden",
		"owner": "dartcafe","created": "2017-10-27 05:12:16","access": "hidden","expire": null,"is_anonymous": 0,"full_anonymous": 0,"disallow_maybe": 0
		},
	{
		"id": 4,"hash": "Ji7hO2zhnLX0VQRq","type": 0,"title": "Date poll just for registered users","description": "Date poll with many options and only accessable to registered users",
		"owner": "dartcafe","created": "2017-10-27 05:13:42","access": "registered","expire": null,"is_anonymous": 0,"full_anonymous": 0,"disallow_maybe": 0
		},
	{
		"id": 5,"hash": "TbSEnD8yA5rDi8Qq","type": 1,"title": "Invitation text poll","description": "This poll expires on 2018/02/15 and is only accessable to Admin from Hell and Angelo Mertel",
		"owner": "dartcafe","created": "2017-10-27 05:22:58","access": "user_Admin;user_User;","expire": "2018-02-15 23:00:00","is_anonymous": 0,"full_anonymous": 0,"disallow_maybe": 0
		},
	{
		"id": 6,"hash": "qemaWrLVwnAKnoEO","type": 0,"title": "Expired date poll","description": "This poll expired on 2017/10/27",
		"owner": "dartcafe","created": "2017-10-27 05:25:06","access": "public","expire": "2017-10-27 22:00:00","is_anonymous": 0,"full_anonymous": 0,"disallow_maybe": 0
		},
	{
		"id": 7,"hash": "5z0FpukeAEGnhqri","type": 0,"title": "Anonymous date poll","description": "Paticipants are only visible to the owner of the poll",
		"owner": "dartcafe","created": "2017-10-27 05:25:27","access": "public","expire": null,"is_anonymous": 1,"full_anonymous": 0,"disallow_maybe": 0
		},
	{
		"id": 8,"hash": "WojRcyzPLYM5Ckdg","type": 0,"title": "Date poll with Hidden usernames","description": "Participants are not visible, even for the owner",
		"owner": "dartcafe","created": "2017-10-27 05:25:51","access": "public","expire": null,"is_anonymous": 1,"full_anonymous": 1,"disallow_maybe": 0
		},
	{	
		"id": 9,"hash": "TTmAtP8Pvkx3Rfl5","type": 0,"title": "Neuer Test","description": "Ui",
		"owner": "dartcafe","created": "2018-07-22 18:52:19","access": "group_admin","expire": null,"is_anonymous": 0,"full_anonymous": 0,"disallow_maybe": 0
		},
	{
		"id": 10,"hash": "dKijkxFBrycCA5FL","type": 0,"title": "This is a hidden poll","description": "This poll should not be visible in th polls list for other users",
		"owner": "dartcafe","created": "2018-08-01 05:42:28","access": "public","expire": null,"is_anonymous": 0,"full_anonymous": 0,"disallow_maybe": 0
		}
]
const _mock_shares = [
{"id": 1, "poll_id": 5, "type": "user", "uid": "Admin", "displayName" : "", "avatarUrl": "", "hash": ""},
{"id": 2, "poll_id": 5, "type": "user", "uid": "User", "displayName" : "", "avatarUrl": "", "hash": ""},
{"id": 3, "poll_id": 9, "type": "group", "uid": "admin", "displayName" : "", "avatarUrl": "", "hash": ""},
{"id": 3, "poll_id": 9, "type": "email", "uid": "github@dartcafe.de", "displayName" : "Email dartcafe", "avatarUrl": "", "hash": "27ae441Ree05Ucca"},
{"id": 3, "poll_id": 9, "type": "external", "uid": "ext-dartcafe", "displayName" : "External dartcafe", "avatarUrl": "", "hash": "b2f9F0f1G106aZ26"},
]

const _mock_options = [
	{"id": 1,"poll_id": 1,"poll_option_text": "2018-02-13 17:30:00","timestamp": 0}, 
	{"id": 2,"poll_id": 1,"poll_option_text": "2018-02-13 18:30:00","timestamp": 0}, 
	{"id": 3,"poll_id": 1,"poll_option_text": "2018-02-20 17:30:00","timestamp": 0}, 
	{"id": 4,"poll_id": 1,"poll_option_text": "2018-02-20 18:30:00","timestamp": 0}, 
	{"id": 5,"poll_id": 3,"poll_option_text": "2017-10-27 06:11:00","timestamp": 0}, 
	{"id": 6,"poll_id": 4,"poll_option_text": "2018-02-11 06:30:00","timestamp": 0}, 
	{"id": 7,"poll_id": 4,"poll_option_text": "2018-02-11 07:15:00","timestamp": 0}, 
	{"id": 8,"poll_id": 4,"poll_option_text": "2018-02-11 08:45:00","timestamp": 0}, 
	{"id": 9,"poll_id": 4,"poll_option_text": "2018-02-11 11:30:00","timestamp": 0}, 
	{"id": 10,"poll_id": 4,"poll_option_text": "2018-02-11 14:15:00","timestamp": 0}, 
	{"id": 11,"poll_id": 4,"poll_option_text": "2018-02-12 06:30:00","timestamp": 0}, 
	{"id": 12,"poll_id": 4,"poll_option_text": "2018-02-12 07:15:00","timestamp": 0}, 
	{"id": 13,"poll_id": 4,"poll_option_text": "2018-02-12 08:45:00","timestamp": 0}, 
	{"id": 14,"poll_id": 4,"poll_option_text": "2018-02-12 11:30:00","timestamp": 0}, 
	{"id": 15,"poll_id": 4,"poll_option_text": "2018-02-12 14:15:00","timestamp": 0}, 
	{"id": 16,"poll_id": 4,"poll_option_text": "2018-02-13 06:30:00","timestamp": 0}, 
	{"id": 17,"poll_id": 4,"poll_option_text": "2018-02-13 07:15:00","timestamp": 0}, 
	{"id": 18,"poll_id": 4,"poll_option_text": "2018-02-13 08:45:00","timestamp": 0}, 
	{"id": 19,"poll_id": 4,"poll_option_text": "2018-02-13 11:30:00","timestamp": 0}, 
	{"id": 20,"poll_id": 4,"poll_option_text": "2018-02-13 14:15:00","timestamp": 0}, 
	{"id": 21,"poll_id": 4,"poll_option_text": "2018-02-14 06:30:00","timestamp": 0}, 
	{"id": 22,"poll_id": 4,"poll_option_text": "2018-02-14 07:15:00","timestamp": 0}, 
	{"id": 23,"poll_id": 4,"poll_option_text": "2018-02-14 08:45:00","timestamp": 0}, 
	{"id": 24,"poll_id": 4,"poll_option_text": "2018-02-14 11:30:00","timestamp": 0}, 
	{"id": 25,"poll_id": 4,"poll_option_text": "2018-02-14 14:15:00","timestamp": 0}, 
	{"id": 26,"poll_id": 4,"poll_option_text": "2018-02-15 06:30:00","timestamp": 0}, 
	{"id": 27,"poll_id": 4,"poll_option_text": "2018-02-15 07:15:00","timestamp": 0}, 
	{"id": 28,"poll_id": 4,"poll_option_text": "2018-02-15 08:45:00","timestamp": 0}, 
	{"id": 29,"poll_id": 4,"poll_option_text": "2018-02-15 11:30:00","timestamp": 0}, 
	{"id": 30,"poll_id": 4,"poll_option_text": "2018-02-15 14:15:00","timestamp": 0}, 
	{"id": 31,"poll_id": 4,"poll_option_text": "2018-02-16 06:30:00","timestamp": 0}, 
	{"id": 32,"poll_id": 4,"poll_option_text": "2018-02-16 07:15:00","timestamp": 0}, 
	{"id": 33,"poll_id": 4,"poll_option_text": "2018-02-16 08:45:00","timestamp": 0}, 
	{"id": 34,"poll_id": 4,"poll_option_text": "2018-02-16 11:30:00","timestamp": 0}, 
	{"id": 35,"poll_id": 4,"poll_option_text": "2018-02-16 14:15:00","timestamp": 0}, 
	{"id": 36,"poll_id": 4,"poll_option_text": "2018-02-17 06:30:00","timestamp": 0}, 
	{"id": 37,"poll_id": 4,"poll_option_text": "2018-02-17 07:15:00","timestamp": 0}, 
	{"id": 38,"poll_id": 4,"poll_option_text": "2018-02-17 08:45:00","timestamp": 0}, 
	{"id": 39,"poll_id": 4,"poll_option_text": "2018-02-17 11:30:00","timestamp": 0}, 
	{"id": 40,"poll_id": 4,"poll_option_text": "2018-02-17 14:15:00","timestamp": 0}, 
	{"id": 41,"poll_id": 6,"poll_option_text": "2017-10-26 06:15:00","timestamp": 0}, 
	{"id": 42,"poll_id": 6,"poll_option_text": "2017-10-26 06:30:00","timestamp": 0}, 
	{"id": 43,"poll_id": 6,"poll_option_text": "2017-10-27 06:15:00","timestamp": 0}, 
	{"id": 44,"poll_id": 6,"poll_option_text": "2017-10-27 06:30:00","timestamp": 0}, 
	{"id": 45,"poll_id": 7,"poll_option_text": "2017-10-27 06:30:00","timestamp": 0}, 
	{"id": 46,"poll_id": 7,"poll_option_text": "2017-10-27 06:45:00","timestamp": 0}, 
	{"id": 51,"poll_id": 2,"poll_option_text": "Optinion 1","timestamp": 0}, 
	{"id": 52,"poll_id": 2,"poll_option_text": "Opinion 2","timestamp": 0}, 
	{"id": 53,"poll_id": 2,"poll_option_text": "Opinion, which everybody should follow.","timestamp": 0}, 
	{"id": 54,"poll_id": 5,"poll_option_text": "This is a very very long option, which you should respect. Please vote for this, because it is the best option. ","timestamp": 0}, 
	{"id": 55,"poll_id": 5,"poll_option_text": "No, no, no, no. Vote for this option, as this is my oppinion.","timestamp": 0}, 
	{"id": 56,"poll_id": 5,"poll_option_text": "No!","timestamp": 0}, 
	{"id": 63,"poll_id": 8,"poll_option_text": "2017-10-20 06:15:00","timestamp": 1508480100}, 
	{"id": 64,"poll_id": 8,"poll_option_text": "2017-10-20 06:25:00","timestamp": 1508480700}, 
	{"id": 65,"poll_id": 8,"poll_option_text": "2017-10-27 06:15:00","timestamp": 1509084900}, 
	{"id": 66,"poll_id": 8,"poll_option_text": "2017-10-27 06:25:00","timestamp": 1509085500}, 
	{"id": 67,"poll_id": 8,"poll_option_text": "2018-07-03 10:00:00","timestamp": 1530612000}, 
	{"id": 68,"poll_id": 8,"poll_option_text": "2018-07-05 10:00:00","timestamp": 1530784800}, 
	{"id": 69,"poll_id": 8,"poll_option_text": "2018-07-10 10:00:00","timestamp": 1531216800}, 
	{"id": 70,"poll_id": 8,"poll_option_text": "2018-07-17 10:00:00","timestamp": 1531821600}, 
	{"id": 71,"poll_id": 8,"poll_option_text": "2018-07-19 10:00:00","timestamp": 1531994400}, 
	{"id": 72,"poll_id": 8,"poll_option_text": "2018-07-24 10:00:00","timestamp": 1532426400}, 
	{"id": 73,"poll_id": 8,"poll_option_text": "2018-07-25 10:00:00","timestamp": 1532512800}, 
	{"id": 80,"poll_id": 9,"poll_option_text": "2018-07-04 10:00:00","timestamp": 1530698400}, 
	{"id": 81,"poll_id": 9,"poll_option_text": "2018-07-06 10:00:00","timestamp": 1530871200}, 
	{"id": 82,"poll_id": 9,"poll_option_text": "2018-07-12 10:00:00","timestamp": 1531389600}, 
	{"id": 83,"poll_id": 9,"poll_option_text": "2018-07-12 10:00:00","timestamp": 1531389600}, 
	{"id": 84,"poll_id": 9,"poll_option_text": "2018-07-13 10:00:00","timestamp": 1531476000}, 
	{"id": 85,"poll_id": 9,"poll_option_text": "2018-07-19 10:00:00","timestamp": 1531994400}
]

const _mock_comments = [
	{"id": 1,"poll_id": 1,"user_id": "Customer","dt": "2017-11-12 05:10:22","comment": "My username is visible, even in public view"}, 
	{"id": 2,"poll_id": 8,"user_id": "User","dt": "2017-11-12 05:11:54","comment": "This is my first comment"}, 
	{"id": 3,"poll_id": 8,"user_id": "Customer","dt": "2017-11-12 05:12:49","comment": "I am Kim Customer and this comment is anonymous."}, 
	{"id": 4,"poll_id": 8,"user_id": "Public User","dt": "2017-11-12 05:13:30","comment": "I am a public user"}, 
	{"id": 5,"poll_id": 8,"user_id": "User","dt": "2017-11-12 05:14:40","comment": "Nobody knows, that I am Angelo Mertel"}, 
	{"id": 6,"poll_id": 1,"user_id": "User","dt": "2017-11-12 05:15:14","comment": "Hey Kim, the only possible date for me is the first."}, 
	{"id": 7,"poll_id": 1,"user_id": "Admin","dt": "2017-11-12 05:15:53","comment": "Hey girls. Party next Tuesday."}
]

const _mock_votes = [
	{"id": 1,"poll_id": 1,"user_id": "User","vote_option_id": 1,"vote_option_text": "2018-02-13 17:30:00","vote_answer": "yes"}, 
	{"id": 2,"poll_id": 1,"user_id": "User","vote_option_id": 2,"vote_option_text": "2018-02-13 18:30:00","vote_answer": "no"}, 
	{"id": 3,"poll_id": 1,"user_id": "User","vote_option_id": 3,"vote_option_text": "2018-02-20 17:30:00","vote_answer": "maybe"}, 
	{"id": 4,"poll_id": 1,"user_id": "User","vote_option_id": 4,"vote_option_text": "2018-02-20 18:30:00","vote_answer": "no"}, 
	{"id": 5,"poll_id": 3,"user_id": "User","vote_option_id": 5,"vote_option_text": "2017-10-27 06:11:00","vote_answer": "yes"}, 
	{"id": 6,"poll_id": 4,"user_id": "User","vote_option_id": 6,"vote_option_text": "2018-02-11 06:30:00","vote_answer": "no"}, 
	{"id": 7,"poll_id": 4,"user_id": "User","vote_option_id": 7,"vote_option_text": "2018-02-11 07:15:00","vote_answer": "no"}, 
	{"id": 8,"poll_id": 4,"user_id": "User","vote_option_id": 8,"vote_option_text": "2018-02-11 08:45:00","vote_answer": "yes"}, 
	{"id": 9,"poll_id": 4,"user_id": "User","vote_option_id": 9,"vote_option_text": "2018-02-11 11:30:00","vote_answer": "yes"}, 
	{"id": 10,"poll_id": 4,"user_id": "User","vote_option_id": 10,"vote_option_text": "2018-02-11 14:15:00","vote_answer": "no"}, 
	{"id": 11,"poll_id": 4,"user_id": "User","vote_option_id": 11,"vote_option_text": "2018-02-12 06:30:00","vote_answer": "no"}, 
	{"id": 12,"poll_id": 4,"user_id": "User","vote_option_id": 12,"vote_option_text": "2018-02-12 07:15:00","vote_answer": "no"}, 
	{"id": 13,"poll_id": 4,"user_id": "User","vote_option_id": 13,"vote_option_text": "2018-02-12 08:45:00","vote_answer": "yes"}, 
	{"id": 14,"poll_id": 4,"user_id": "User","vote_option_id": 14,"vote_option_text": "2018-02-12 11:30:00","vote_answer": "yes"}, 
	{"id": 15,"poll_id": 4,"user_id": "User","vote_option_id": 15,"vote_option_text": "2018-02-12 14:15:00","vote_answer": "no"}, 
	{"id": 16,"poll_id": 4,"user_id": "User","vote_option_id": 16,"vote_option_text": "2018-02-13 06:30:00","vote_answer": "no"}, 
	{"id": 17,"poll_id": 4,"user_id": "User","vote_option_id": 17,"vote_option_text": "2018-02-13 07:15:00","vote_answer": "yes"}, 
	{"id": 18,"poll_id": 4,"user_id": "User","vote_option_id": 18,"vote_option_text": "2018-02-13 08:45:00","vote_answer": "no"}, 
	{"id": 19,"poll_id": 4,"user_id": "User","vote_option_id": 19,"vote_option_text": "2018-02-13 11:30:00","vote_answer": "no"}, 
	{"id": 20,"poll_id": 4,"user_id": "User","vote_option_id": 20,"vote_option_text": "2018-02-13 14:15:00","vote_answer": "no"}, 
	{"id": 21,"poll_id": 4,"user_id": "User","vote_option_id": 21,"vote_option_text": "2018-02-14 06:30:00","vote_answer": "no"}, 
	{"id": 22,"poll_id": 4,"user_id": "User","vote_option_id": 22,"vote_option_text": "2018-02-14 07:15:00","vote_answer": "no"}, 
	{"id": 23,"poll_id": 4,"user_id": "User","vote_option_id": 23,"vote_option_text": "2018-02-14 08:45:00","vote_answer": "no"}, 
	{"id": 24,"poll_id": 4,"user_id": "User","vote_option_id": 24,"vote_option_text": "2018-02-14 11:30:00","vote_answer": "yes"}, 
	{"id": 25,"poll_id": 4,"user_id": "User","vote_option_id": 25,"vote_option_text": "2018-02-14 14:15:00","vote_answer": "yes"}, 
	{"id": 26,"poll_id": 4,"user_id": "User","vote_option_id": 26,"vote_option_text": "2018-02-15 06:30:00","vote_answer": "no"}, 
	{"id": 27,"poll_id": 4,"user_id": "User","vote_option_id": 27,"vote_option_text": "2018-02-15 07:15:00","vote_answer": "no"}, 
	{"id": 28,"poll_id": 4,"user_id": "User","vote_option_id": 28,"vote_option_text": "2018-02-15 08:45:00","vote_answer": "no"}, 
	{"id": 29,"poll_id": 4,"user_id": "User","vote_option_id": 29,"vote_option_text": "2018-02-15 11:30:00","vote_answer": "yes"}, 
	{"id": 30,"poll_id": 4,"user_id": "User","vote_option_id": 30,"vote_option_text": "2018-02-15 14:15:00","vote_answer": "no"}, 
	{"id": 31,"poll_id": 4,"user_id": "User","vote_option_id": 31,"vote_option_text": "2018-02-16 06:30:00","vote_answer": "no"}, 
	{"id": 32,"poll_id": 4,"user_id": "User","vote_option_id": 32,"vote_option_text": "2018-02-16 07:15:00","vote_answer": "no"}, 
	{"id": 33,"poll_id": 4,"user_id": "User","vote_option_id": 33,"vote_option_text": "2018-02-16 08:45:00","vote_answer": "yes"}, 
	{"id": 34,"poll_id": 4,"user_id": "User","vote_option_id": 34,"vote_option_text": "2018-02-16 11:30:00","vote_answer": "no"}, 
	{"id": 35,"poll_id": 4,"user_id": "User","vote_option_id": 35,"vote_option_text": "2018-02-16 14:15:00","vote_answer": "no"}, 
	{"id": 36,"poll_id": 4,"user_id": "User","vote_option_id": 36,"vote_option_text": "2018-02-17 06:30:00","vote_answer": "no"}, 
	{"id": 37,"poll_id": 4,"user_id": "User","vote_option_id": 37,"vote_option_text": "2018-02-17 07:15:00","vote_answer": "no"}, 
	{"id": 38,"poll_id": 4,"user_id": "User","vote_option_id": 38,"vote_option_text": "2018-02-17 08:45:00","vote_answer": "no"}, 
	{"id": 39,"poll_id": 4,"user_id": "User","vote_option_id": 39,"vote_option_text": "2018-02-17 11:30:00","vote_answer": "no"}, 
	{"id": 40,"poll_id": 4,"user_id": "User","vote_option_id": 40,"vote_option_text": "2018-02-17 14:15:00","vote_answer": "yes"}, 
	{"id": 41,"poll_id": 1,"user_id": "Customer","vote_option_id": 1,"vote_option_text": "2018-02-13 17:30:00","vote_answer": "yes"}, 
	{"id": 42,"poll_id": 1,"user_id": "Customer","vote_option_id": 2,"vote_option_text": "2018-02-13 18:30:00","vote_answer": "yes"}, 
	{"id": 43,"poll_id": 1,"user_id": "Customer","vote_option_id": 3,"vote_option_text": "2018-02-20 17:30:00","vote_answer": "yes"}, 
	{"id": 44,"poll_id": 1,"user_id": "Customer","vote_option_id": 4,"vote_option_text": "2018-02-20 18:30:00","vote_answer": "yes"}, 
	{"id": 49,"poll_id": 1,"user_id": "Admin","vote_option_id": 1,"vote_option_text": "2018-02-13 17:30:00","vote_answer": "yes"}, 
	{"id": 50,"poll_id": 1,"user_id": "Admin","vote_option_id": 2,"vote_option_text": "2018-02-13 18:30:00","vote_answer": "yes"}, 
	{"id": 51,"poll_id": 1,"user_id": "Admin","vote_option_id": 3,"vote_option_text": "2018-02-20 17:30:00","vote_answer": "no"}, 
	{"id": 52,"poll_id": 1,"user_id": "Admin","vote_option_id": 4,"vote_option_text": "2018-02-20 18:30:00","vote_answer": "maybe"}, 
	{"id": 53,"poll_id": 7,"user_id": "Admin","vote_option_id": 45,"vote_option_text": "2017-10-27 06:30:00","vote_answer": "yes"}, 
	{"id": 54,"poll_id": 7,"user_id": "Admin","vote_option_id": 46,"vote_option_text": "2017-10-27 06:45:00","vote_answer": "no"}, 
	{"id": 55,"poll_id": 8,"user_id": "Admin","vote_option_id": 47,"vote_option_text": "2017-10-20 06:15:00","vote_answer": "no"}, 
	{"id": 56,"poll_id": 8,"user_id": "Admin","vote_option_id": 48,"vote_option_text": "2017-10-20 06:25:00","vote_answer": "yes"}, 
	{"id": 57,"poll_id": 8,"user_id": "Admin","vote_option_id": 49,"vote_option_text": "2017-10-27 06:15:00","vote_answer": "yes"}, 
	{"id": 58,"poll_id": 8,"user_id": "Admin","vote_option_id": 50,"vote_option_text": "2017-10-27 06:25:00","vote_answer": "yes"}, 
	{"id": 59,"poll_id": 4,"user_id": "Admin","vote_option_id": 6,"vote_option_text": "2018-02-11 06:30:00","vote_answer": "no"}, 
	{"id": 60,"poll_id": 4,"user_id": "Admin","vote_option_id": 7,"vote_option_text": "2018-02-11 07:15:00","vote_answer": "no"}, 
	{"id": 61,"poll_id": 4,"user_id": "Admin","vote_option_id": 8,"vote_option_text": "2018-02-11 08:45:00","vote_answer": "no"}, 
	{"id": 62,"poll_id": 4,"user_id": "Admin","vote_option_id": 9,"vote_option_text": "2018-02-11 11:30:00","vote_answer": "yes"}, 
	{"id": 63,"poll_id": 4,"user_id": "Admin","vote_option_id": 10,"vote_option_text": "2018-02-11 14:15:00","vote_answer": "yes"}, 
	{"id": 64,"poll_id": 4,"user_id": "Admin","vote_option_id": 11,"vote_option_text": "2018-02-12 06:30:00","vote_answer": "no"}, 
	{"id": 65,"poll_id": 4,"user_id": "Admin","vote_option_id": 12,"vote_option_text": "2018-02-12 07:15:00","vote_answer": "yes"}, 
	{"id": 66,"poll_id": 4,"user_id": "Admin","vote_option_id": 13,"vote_option_text": "2018-02-12 08:45:00","vote_answer": "no"}, 
	{"id": 67,"poll_id": 4,"user_id": "Admin","vote_option_id": 14,"vote_option_text": "2018-02-12 11:30:00","vote_answer": "no"}, 
	{"id": 68,"poll_id": 4,"user_id": "Admin","vote_option_id": 15,"vote_option_text": "2018-02-12 14:15:00","vote_answer": "no"}, 
	{"id": 69,"poll_id": 4,"user_id": "Admin","vote_option_id": 16,"vote_option_text": "2018-02-13 06:30:00","vote_answer": "no"}, 
	{"id": 70,"poll_id": 4,"user_id": "Admin","vote_option_id": 17,"vote_option_text": "2018-02-13 07:15:00","vote_answer": "no"}, 
	{"id": 71,"poll_id": 4,"user_id": "Admin","vote_option_id": 18,"vote_option_text": "2018-02-13 08:45:00","vote_answer": "no"}, 
	{"id": 72,"poll_id": 4,"user_id": "Admin","vote_option_id": 19,"vote_option_text": "2018-02-13 11:30:00","vote_answer": "no"}, 
	{"id": 73,"poll_id": 4,"user_id": "Admin","vote_option_id": 20,"vote_option_text": "2018-02-13 14:15:00","vote_answer": "no"}, 
	{"id": 74,"poll_id": 4,"user_id": "Admin","vote_option_id": 21,"vote_option_text": "2018-02-14 06:30:00","vote_answer": "no"}, 
	{"id": 75,"poll_id": 4,"user_id": "Admin","vote_option_id": 22,"vote_option_text": "2018-02-14 07:15:00","vote_answer": "no"}, 
	{"id": 76,"poll_id": 4,"user_id": "Admin","vote_option_id": 23,"vote_option_text": "2018-02-14 08:45:00","vote_answer": "no"}, 
	{"id": 77,"poll_id": 4,"user_id": "Admin","vote_option_id": 24,"vote_option_text": "2018-02-14 11:30:00","vote_answer": "no"}, 
	{"id": 78,"poll_id": 4,"user_id": "Admin","vote_option_id": 25,"vote_option_text": "2018-02-14 14:15:00","vote_answer": "no"}, 
	{"id": 79,"poll_id": 4,"user_id": "Admin","vote_option_id": 26,"vote_option_text": "2018-02-15 06:30:00","vote_answer": "no"}, 
	{"id": 80,"poll_id": 4,"user_id": "Admin","vote_option_id": 27,"vote_option_text": "2018-02-15 07:15:00","vote_answer": "no"}, 
	{"id": 81,"poll_id": 4,"user_id": "Admin","vote_option_id": 28,"vote_option_text": "2018-02-15 08:45:00","vote_answer": "no"}, 
	{"id": 82,"poll_id": 4,"user_id": "Admin","vote_option_id": 29,"vote_option_text": "2018-02-15 11:30:00","vote_answer": "no"}, 
	{"id": 83,"poll_id": 4,"user_id": "Admin","vote_option_id": 30,"vote_option_text": "2018-02-15 14:15:00","vote_answer": "no"}, 
	{"id": 84,"poll_id": 4,"user_id": "Admin","vote_option_id": 31,"vote_option_text": "2018-02-16 06:30:00","vote_answer": "no"}, 
	{"id": 85,"poll_id": 4,"user_id": "Admin","vote_option_id": 32,"vote_option_text": "2018-02-16 07:15:00","vote_answer": "no"}, 
	{"id": 86,"poll_id": 4,"user_id": "Admin","vote_option_id": 33,"vote_option_text": "2018-02-16 08:45:00","vote_answer": "no"}, 
	{"id": 87,"poll_id": 4,"user_id": "Admin","vote_option_id": 34,"vote_option_text": "2018-02-16 11:30:00","vote_answer": "no"}, 
	{"id": 88,"poll_id": 4,"user_id": "Admin","vote_option_id": 35,"vote_option_text": "2018-02-16 14:15:00","vote_answer": "no"}, 
	{"id": 89,"poll_id": 4,"user_id": "Admin","vote_option_id": 36,"vote_option_text": "2018-02-17 06:30:00","vote_answer": "no"}, 
	{"id": 90,"poll_id": 4,"user_id": "Admin","vote_option_id": 37,"vote_option_text": "2018-02-17 07:15:00","vote_answer": "no"}, 
	{"id": 91,"poll_id": 4,"user_id": "Admin","vote_option_id": 38,"vote_option_text": "2018-02-17 08:45:00","vote_answer": "no"}, 
	{"id": 92,"poll_id": 4,"user_id": "Admin","vote_option_id": 39,"vote_option_text": "2018-02-17 11:30:00","vote_answer": "no"}, 
	{"id": 93,"poll_id": 4,"user_id": "Admin","vote_option_id": 40,"vote_option_text": "2018-02-17 14:15:00","vote_answer": "no"}, 
	{"id": 94,"poll_id": 7,"user_id": "Customer","vote_option_id": 45,"vote_option_text": "2017-10-27 06:30:00","vote_answer": "yes"}, 
	{"id": 95,"poll_id": 7,"user_id": "Customer","vote_option_id": 46,"vote_option_text": "2017-10-27 06:45:00","vote_answer": "yes"}, 
	{"id": 96,"poll_id": 8,"user_id": "Customer","vote_option_id": 47,"vote_option_text": "2017-10-20 06:15:00","vote_answer": "yes"}, 
	{"id": 97,"poll_id": 8,"user_id": "Customer","vote_option_id": 48,"vote_option_text": "2017-10-20 06:25:00","vote_answer": "yes"}, 
	{"id": 98,"poll_id": 8,"user_id": "Customer","vote_option_id": 49,"vote_option_text": "2017-10-27 06:15:00","vote_answer": "yes"}, 
	{"id": 99,"poll_id": 8,"user_id": "Customer","vote_option_id": 50,"vote_option_text": "2017-10-27 06:25:00","vote_answer": "yes"}, 
	{"id": 100,"poll_id": 8,"user_id": "dartcafe","vote_option_id": 47,"vote_option_text": "2017-10-20 06:15:00","vote_answer": "maybe"}, 
	{"id": 101,"poll_id": 8,"user_id": "dartcafe","vote_option_id": 48,"vote_option_text": "2017-10-20 06:25:00","vote_answer": "maybe"}, 
	{"id": 102,"poll_id": 8,"user_id": "dartcafe","vote_option_id": 49,"vote_option_text": "2017-10-27 06:15:00","vote_answer": "maybe"}, 
	{"id": 103,"poll_id": 8,"user_id": "dartcafe","vote_option_id": 50,"vote_option_text": "2017-10-27 06:25:00","vote_answer": "maybe"}, 
	{"id": 104,"poll_id": 7,"user_id": "User","vote_option_id": 45,"vote_option_text": "2017-10-27 06:30:00","vote_answer": "yes"}, 
	{"id": 105,"poll_id": 7,"user_id": "User","vote_option_id": 46,"vote_option_text": "2017-10-27 06:45:00","vote_answer": "yes"}, 
	{"id": 106,"poll_id": 8,"user_id": "User","vote_option_id": 47,"vote_option_text": "2017-10-20 06:15:00","vote_answer": "no"}, 
	{"id": 107,"poll_id": 8,"user_id": "User","vote_option_id": 48,"vote_option_text": "2017-10-20 06:25:00","vote_answer": "yes"}, 
	{"id": 108,"poll_id": 8,"user_id": "User","vote_option_id": 49,"vote_option_text": "2017-10-27 06:15:00","vote_answer": "yes"}, 
	{"id": 109,"poll_id": 8,"user_id": "User","vote_option_id": 50,"vote_option_text": "2017-10-27 06:25:00","vote_answer": "no"}, 
	{"id": 110,"poll_id": 7,"user_id": "Public Anon","vote_option_id": 45,"vote_option_text": "2017-10-27 06:30:00","vote_answer": "yes"}, 
	{"id": 111,"poll_id": 7,"user_id": "Public Anon","vote_option_id": 46,"vote_option_text": "2017-10-27 06:45:00","vote_answer": "yes"}, 
	{"id": 112,"poll_id": 8,"user_id": "Hidden Anon","vote_option_id": 47,"vote_option_text": "2017-10-20 06:15:00","vote_answer": "yes"}, 
	{"id": 113,"poll_id": 8,"user_id": "Hidden Anon","vote_option_id": 48,"vote_option_text": "2017-10-20 06:25:00","vote_answer": "no"}, 
	{"id": 114,"poll_id": 8,"user_id": "Hidden Anon","vote_option_id": 49,"vote_option_text": "2017-10-27 06:15:00","vote_answer": "no"}, 
	{"id": 115,"poll_id": 8,"user_id": "Hidden Anon","vote_option_id": 50,"vote_option_text": "2017-10-27 06:25:00","vote_answer": "no"}, 
	{"id": 116,"poll_id": 6,"user_id": "Public User","vote_option_id": 41,"vote_option_text": "2017-10-26 06:15:00","vote_answer": "yes"}, 
	{"id": 117,"poll_id": 6,"user_id": "Public User","vote_option_id": 42,"vote_option_text": "2017-10-26 06:30:00","vote_answer": "yes"}, 
	{"id": 118,"poll_id": 6,"user_id": "Public User","vote_option_id": 43,"vote_option_text": "2017-10-27 06:15:00","vote_answer": "yes"}, 
	{"id": 119,"poll_id": 6,"user_id": "Public User","vote_option_id": 44,"vote_option_text": "2017-10-27 06:30:00","vote_answer": "yes"}, 
	{"id": 120,"poll_id": 2,"user_id": "User","vote_option_id": 51,"vote_option_text": "Optinion 1","vote_answer": "no"}, 
	{"id": 121,"poll_id": 2,"user_id": "User","vote_option_id": 52,"vote_option_text": "Opinion 2","vote_answer": "no"}, 
	{"id": 122,"poll_id": 2,"user_id": "User","vote_option_id": 53,"vote_option_text": "Opinion, which everybody should follow.","vote_answer": "yes"}, 
	{"id": 123,"poll_id": 5,"user_id": "Customer","vote_option_id": 54,"vote_option_text": "This is a very very long option, which you should respect. Please vote for this, because it is the best option. ","vote_answer": "yes"}, 
	{"id": 124,"poll_id": 5,"user_id": "Customer","vote_option_id": 55,"vote_option_text": "No, no, no, no. Vote for this option, as this is my oppinion.","vote_answer": "no"}, 
	{"id": 125,"poll_id": 5,"user_id": "Customer","vote_option_id": 56,"vote_option_text": "No!","vote_answer": "yes"}, 
	{"id": 126,"poll_id": 2,"user_id": "Customer","vote_option_id": 51,"vote_option_text": "Optinion 1","vote_answer": "no"}, 
	{"id": 127,"poll_id": 2,"user_id": "Customer","vote_option_id": 52,"vote_option_text": "Opinion 2","vote_answer": "yes"}, 
	{"id": 128,"poll_id": 2,"user_id": "Customer","vote_option_id": 53,"vote_option_text": "Opinion, which everybody should follow.","vote_answer": "no"}, 
	{"id": 129,"poll_id": 1,"user_id": "dartcafe","vote_option_id": 0,"vote_option_text": "2018-02-13 17:30:00","vote_answer": "yes"}, 
	{"id": 130,"poll_id": 1,"user_id": "dartcafe","vote_option_id": 0,"vote_option_text": "2018-02-13 18:30:00","vote_answer": "no"}, 
	{"id": 131,"poll_id": 1,"user_id": "dartcafe","vote_option_id": 0,"vote_option_text": "2018-02-20 17:30:00","vote_answer": "no"}, 
	{"id": 132,"poll_id": 1,"user_id": "dartcafe","vote_option_id": 0,"vote_option_text": "2018-02-20 18:30:00","vote_answer": "no"}
]

import axios from 'axios'
const _mock = false
const _route = 'apps/polls/get/events/'

function transformEvents(events) {
	for (i = 0; i < events.length; i++) {
		if (('|public|hidden|registered').indexOf(events[i].access) < 0) {
			events[i].access = 'shared'
		}
		events[i].expiration = (events[i].expire !== null)
		events[i].expirationDate = events[i].expire
		events[i].type = (events[i].type === 0 ? 'datePoll' : 'textPoll')
	}
	return events
}

function transformShares(shares) {
	console.log('shares: ' + shares)
	for (i = 0; i < shares.length; i++) {
		console.log('shares.length: ' + shares.length)
		console.log('shares[' + i + '].access: ' + shares[i].access)
		if (('|public|hidden|registered').indexOf(shares[i].access) < 0) {
			shares[i] = shares[i].access.split(";").filter(access => access !== '').map(element => {
				return {
					'id' : i,
					'type' : element.split("_")[0],
					'uid' : element.split("_")[1],
					'displayName' : '',
					'avatarUrl' : '',
					'hash' : ''
				}
			})
		}
	}
	return shares
}


function loadAllEventsWrapper(route) {
	if (_mock) {
		return transformEvents(_mock_events)
		console.log('Loading events with mock data')
	} else {
		console.log('Loading events with db data')
		return axios
			.get(route)
			.then((response) => {
				return transformEvents(response.data)
			}, (error) => {
				console.log(error.response)
			})	
	}
	
}

function loadAllSharesWrapper() {
	if (!_mock) {
		console.log('Loading shares with mock data')
		return transformShares(_mock_events)
	} else {
		return axios.get(OC.generateUrl('apps/polls/get/shares/'))
		.then((response) => {
			return transformShares(response.data)
		}, (error) => {
		})	
	}
}

function loadAllOptionsWrapper() {
	if (_mock) {
		var loadedOptions = _mock_options
		console.log('Loading options with mock data')
		console.log(_mock_options)
	} else {
		// db call here
	}
	return loadedOptions
}

function loadAllCommentsWrapper() {
	if (_mock) {
		var loadedComments = _mock_comments
		console.log('Loading Comments with mock data')
	} else {
		// db call here
	}
	return loadedComments
}

function loadAllVotesWrapper() {
	if (_mock) {
		var loadedVotes = _mock_votes
		console.log('Loading votes with mock data')
	} else {
		// db call here
	}
	return loadedVotes
}

export default {
	getEvents (events) {
		loadAllEventsWrapper(OC.generateUrl('apps/polls/get/events/'))
		.then((loaded) => {
			events(loaded)
		})
	},

	getEvents (cb, query) {
		loadAllEventsWrapper(OC.generateUrl('apps/polls/get/events/'))
		.then((events) => {
			if (!query) {
				events(loaded)
			} else {
				cb(events.find(poll => poll.hash === query))
			}
		})
	},
	
	getShares (cb, query) {
		if (!query) {
			cb(loadAllSharesWrapper())
		} else {
			cb(loadAllSharesWrapper().filter(Shares => Shares.poll_id === query))
		}
	},
  
	getOptions (cb, query) {
		if (!query) {
			cb(loadAllOptionsWrapper())
		} else {
			cb(loadAllOptionsWrapper().filter(option => option.poll_id === query))
		}
	},

	getComments (cb, query) {
		if (!query) {
			cb(loadAllCommentsWrapper())
		} else {
			cb(loadAllCommentsWrapper().filter(comment => comment.poll_id === query))
		}
	},
	
	getVotes (cb, query) {
		if (!query) {
			cb(loadAllVotesWrapper())
		} else {
			cb(loadAllVotesWrapper().filter(vote => vote.poll_id === query))
		}
	}
}
