/**
 * Mocking client-server processing
 */
const _siteGroups = [
  {"uid": "Poll users"},
  {"uid": "admin"}
]

export default {
  getGroups (cb) {
    setTimeout(() => cb(_siteGroups), 100)
  }
}
