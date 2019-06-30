<template>
	<div>

		<div class="configBox">
			<label class="title icon-checkmark">
				{{ t('polls', 'Poll type') }}
			</label>
			<input id="datePoll" v-model="eventType" value="datePoll" :disabled="protect" type="radio" class="radio" >
			<label for="datePoll">
				{{ t('polls', 'Event schedule') }}
			</label>
			<input id="textPoll" v-model="eventType" value="textPoll" :disabled="protect" type="radio" class="radio" >
			<label for="textPoll">
				{{ t('polls', 'Text based') }}
			</label>
		</div>

		<div class="configBox">
			<label class="title icon-category-customization">
				{{ t('polls', 'Poll configurations') }}
			</label>

			<input id="allowMaybe" v-model="eventAllowMaybe" :disabled="protect"
				type="checkbox" class="checkbox"
			>
			<label for="allowMaybe" class="title">
				{{ t('polls', 'Allow "maybe" vote') }}
			</label>

			<input id="anonymous" v-model="eventIsAnonymous" :disabled="protect"
				type="checkbox" class="checkbox"
			>
			<label for="anonymous" class="title">
				{{ t('polls', 'Anonymous poll') }}
			</label>

			<input v-show="event.isAnonymous" id="trueAnonymous" v-model="eventFullAnonymous"
				:disabled="protect" type="checkbox" class="checkbox"
			>
			<label v-show="event.isAnonymous" class="title" for="trueAnonymous">
				{{ t('polls', 'Hide user names for admin') }}
			</label>

			<input id="expiration" v-model="eventExpiration" :disabled="protect"
				type="checkbox" class="checkbox"
			>
			<label class="title" for="expiration">
				{{ t('polls', 'Expires') }}
			</label>

			<DatePicker v-show="event.expiration" v-model="eventExpirationDate" v-bind="expirationDatePicker" :disabled="protect" :time-picker-options="{ start: '00:00', step: '00:05', end: '23:55' }" style="width:170px" />
		</div>

		<div class="configBox">
			<label class="title icon-category-auth">
				{{ t('polls', 'Access') }}
			</label>
			<input id="private" v-model="eventAccess" :disabled="protect"
				type="radio" value="registered" class="radio"
			>
			<label for="private" class="title">
				<div class="title icon-group" />
				<span>{{ t('polls', 'Registered users only') }}</span>
			</label>
			<input id="hidden" v-model="eventAccess" :disabled="protect" type="radio" value="hidden" class="radio" >
			<label for="hidden" class="title">
				<div class="title icon-category-security" />
				<span>{{ t('polls', 'hidden') }}</span>
			</label>
			<input id="public" v-model="eventAccess" :disabled="protect" type="radio" value="public" class="radio" >
			<label for="public" class="title">
				<div class="title icon-link" />
				<span>{{ t('polls', 'Public access') }}</span>
			</label>
			<input id="select" v-model="eventAccess" :disabled="protect" type="radio" value="select" class="radio" >
			<label for="select" class="title">
				<div class="title icon-shared" />
				<span>{{ t('polls', 'Only shared') }}</span>
			</label>
		</div>
	</div>

</template>

<script>
import { mapState, mapMutations } from 'vuex'

export default {
	name: 'InformationTab',
	data() {
		return {
			// protect: false,
			nextPollDateId: 1,
			nextPollTextId: 1,
			writingPoll: false,
			sidebar: false,
			titleEmpty: false
		}
	},

	computed:	{
		...mapState({
			poll: state => state.poll,
			event: state => state.poll.event
		}),

		// Add bindings
		eventType: {
			get() {
				return this.event.type
			},
			set(value) {
				this.$store.commit('setEventProperty', { 'property': 'type', 'value': value })
			}
		},

		eventAccess: {
			get() {
				return this.event.access
			},
			set(value) {
				this.$store.commit('setEventProperty', { 'property': 'access', 'value': value })
			}
		},

		eventExpiration: {
			get() {
				return this.event.expiration
			},
			set(value) {
				this.$store.commit('setEventProperty', { 'property': 'expiration', 'value': value })
			}
		},

		eventFullAnonymous: {
			get() {
				return this.event.fullAnonymous
			},
			set(value) {
				this.$store.commit('setEventProperty', { 'property': 'fullAnonymous', 'value': value })
			}
		},

		eventIsAnonymous: {
			get() {
				return this.event.isAnonymous
			},
			set(value) {
				this.$store.commit('setEventProperty', { 'property': 'isAnonymous', 'value': value })
			}
		},

		eventAllowMaybe: {
			get() {
				return this.event.allowMaybe
			},
			set(value) {
				this.$store.commit('setEventProperty', { 'property': 'allowMaybe', 'value': value })
			}
		},

		eventExpirationDate: {
			get() {
				return this.$store.state.poll.event.expirationDate
			},
			set(value) {
				this.$store.commit('setEventProperty', { 'property': 'expirationDate', 'value': value })
			}
		},

		expirationDatePicker() {
			return {
				editable: true,
				minuteStep: 1,
				type: 'datetime',
				format: this.dateTimeFormat,
				lang: this.langShort,
				placeholder: t('polls', 'Expiration date'),
				timePickerOptions: {
					start: '00:00',
					step: '00:30',
					end: '23:30'
				}
			}
		},

		protect: function() {
			return (this.poll.mode === 'vote')
		},

		saveButtonTitle: function() {
			if (this.writingPoll) {
				return t('polls', 'Writing poll')
			} else if (this.poll.mode === 'edit') {
				return t('polls', 'Update poll')
			} else {
				return t('polls', 'Create new poll')
			}
		}

	},
	methods: {
		...mapMutations([
			'setEventProperty',
			'setPollProperty',
		]),


	}
}

</script>

<style lang="scss">

.configBox {
    display: flex;
    flex-direction: column;
    padding: 8px;
    & > * {
        padding-left: 21px;
    }
    & > .title {
		display: flex;
        background-position: 0 2px;
        padding-left: 24px;
        opacity: 0.7;
        font-weight: bold;
        margin-bottom: 4px;
		& > span {
			padding-left: 4px;
		}
    }
}

</style>
