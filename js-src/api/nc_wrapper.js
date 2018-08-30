const _OC_getLocale = 'de_DE'
const _OC_generateUrl = ''
const _OC_getCurrentUser = {'uid': 'dartcafe'}
const _OC_imagePath = '/'
export default {
	getLocale(cb) {
		cb(_OC_getLocale)
	}
	
	generateUrl(cb, path) {
		cb(_OC_generateUrl + path)
	}
	
	getCurrentUser (cb) {
		cb(_OC_getCurrentUser)
	},
	imagePath(cb, app, path) {
		cb(_OC_imagePath + path)
}
