export class Option {

	constructor(option) {
		this.id = option.id
		this.confirmed = option.confirmed
		this.duration = option.duration
		this.order = option.order
		this.pollId = option.pollId
		this.text = option.text
		this.timestamp = option.timestamp
		this.owner = option.owner
		this.computed = option.computed
	}

	get start() {
		return new Date(this.timestamp)
	}

	get end() {
		return new Date(this.timestamp + this.duration)
	}

	get isMoment() {
		return this.end() === this.start()
	}

	get type() {
		return 'date'
	}

}
