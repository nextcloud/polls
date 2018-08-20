/**
 * Mocking client-server processing
 */
const _siteUsers = [
  {"uid": "Admin","displayname": "Admin from Hell", "avatarURL": ""},
  {"uid": "After the sunset","displayname": null, "avatarURL": ""},
  {"uid": "Allemeine","displayname": null, "avatarURL": ""},
  {"uid": "Customer","displayname": "Kim Customer", "avatarURL": ""},
  {"uid": "Granata","displayname": null, "avatarURL": ""},
  {"uid": "Hallowach","displayname": null, "avatarURL": ""},
  {"uid": "Kasalla","displayname": null, "avatarURL": ""},
  {"uid": "Master","displayname": null, "avatarURL": ""},
  {"uid": "User","displayname": "Angelo Mertel", "avatarURL": ""},
  {"uid": "dartcafe","displayname": "Dart Cafe", "avatarURL": ""}
]

export default {
  getUsers (cb) {
    setTimeout(() => cb(_siteUsers), 100)
  }
}
