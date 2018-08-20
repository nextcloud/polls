<template id="config-tab">
	
	<div id="configurationsTabView" class="tab">
	
		<div class="configBox" v-if="mode =='edit'">
			<label class="title">{{ t('polls', 'Poll type') }}</label>
			<input id="datePoll" v-model="configuration.type" value="datePoll" type="radio" class="radio" :disabled="protect"/>
			<label for="datePoll">{{ t('polls', 'Event schedule') }}</label>
			<input id="textPoll" v-model="configuration.type" value="textPoll" type="radio" class="radio" :disabled="protect"/>
			<label for="textPoll">{{ t('polls', 'Text based') }}</label>
		</div>

		<div class="configBox flex-column">
			<label class="title">{{ t('polls', 'Poll configurations') }}</label>
			<input :disabled="protect" id="disallowMaybe" v-model="configuration.disallowMaybe"type="checkbox" class="checkbox" />
			<label for="disallowMaybe">{{ t('polls', 'Disallow maybe vote') }}</label>

			<input :disabled="protect" id="anonymous" v-model="configuration.isAnonymous"type="checkbox" class="checkbox" />
			<label for="anonymous">{{ t('polls', 'Anonymous poll') }}</label>

			<input :disabled="protect" id="trueAnonymous" v-model="configuration.fullAnonymous" v-show="configuration.isAnonymous" type="checkbox" class="checkbox"/>
			<label for="trueAnonymous" v-show="configuration.isAnonymous">{{ t('polls', 'Hide user names for admin') }} </label>

			<input :disabled="protect" id="expiration" v-model="configuration.expiration" type="checkbox" class="checkbox" />
			<label for="expiration">{{ t('polls', 'Expires') }}</label>
			<date-picker-input :disabled="protect" :placeholder="t('polls', 'Expiration date')" v-model="configuration.expire" v-show="configuration.expiration"></date-picker-input>
		</div>

		<div class="configBox flex-column">
			<label class="title">{{ t('polls', 'Access') }}</label>
			<input :disabled="protect" type="radio" v-model="configuration.access" value="registered" id="private" class="radio"/>
			<label for="private">{{ t('polls', 'Registered users only') }}</label>
			<input :disabled="protect" type="radio" v-model="configuration.access" value="hidden" id="hidden" class="radio"/>
			<label for="hidden">{{ t('polls', 'hidden') }}</label>
			<input :disabled="protect" type="radio" v-model="configuration.access" value="public" id="public" class="radio"/>
			<label for="public">{{ t('polls', 'Public access') }}</label>
			<input :disabled="protect" type="radio" v-model="configuration.access" value="select" id="select" class="radio"/>
			<label for="select">{{ t('polls', 'Only shared') }}</label>
		</div>
	</div>
</template>

<script>
	export default {
		template: "#config-tab",
		props: ['value', 'mode', 'protect'],
		data: function() {
			return {
				configuration: this.value.event
			}
		},
		watch: {
			value () {
				this.configuration = this.value.event;
			}
		}
	}
	
</script>
<style lang="scss" scoped>

.tab {
	display: flex;
	flex-grow: 1;
	flex-wrap: wrap;

	.configBox > .title {
		font-weight: bold;
		margin-bottom: 4px;
	}

}

.configBox {
	display: flex;
	flex-direction: column;
	flex-grow: 0;
	flex-shrink: 0;
}



</style>
