/**
 * Mocking client-server processing
 */
const _poll_notif = [
	{"id": 1,"poll_id": 4,"user_id": "User"}, 
	{"id": 2,"poll_id": 5,"user_id": "Customer"}
]

export default {
	getNotifications (cb) {
		setTimeout(() => cb(_poll_notif), 100)
	}

}
