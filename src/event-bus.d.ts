declare module '@nextcloud/event-bus' {
	interface NextcloudEvents {
		'polls:transitions:off': number
		'polls:transitions:on': null
		'polls:poll:update': { store: string, message: string }
		'polls:poll:load': null
		'polls:sidebar:changeTab': { activeTab: string }
		'polls:sidebar:toggle': { open: boolean }
		'polls:change:shares': null
		'polls:options:update': null
		'polls:comments:update': null
		'polls:activity:update': null
	}
}

export {}