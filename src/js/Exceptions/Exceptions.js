class Exception extends Error {

	constructor(message) {
		super(message)
		this.name = 'Exception'
	}

}

class NotReady extends Error {

	constructor(message) {
		super(message)
		this.name = 'NotReady'
	}

}

export { Exception, NotReady }
