/* global Vue, oc_userconfig */
<template>
	<div class="user-row" :class="type">
		<div v-show="description" class="description">{{description}}</div>
		<div class="avatar"><img :src="avatarURL" :width="size" :height="size" :title="computedDisplayName"></div>
		<div v-show="!onlyAvatar" class="user-name">{{ computedDisplayName }}</div>
	</div>
</template>

<script>
	export default {
		props: {
			onlyAvatar: {
				default: false
			},
			userId: {
				type: String,
				default: OC.getCurrentUser().uid
			},
			displayName: {
				type: String
			},
			size: {
				type: Number,
				default: 32
			},
			type: {
				type: String,
				default: 'user'
			},
			description: String
		},

		data: function () {
			return {
				nothidden: false,
			}
		},

		computed: {
			computedDisplayName: function () {
				var value = this.displayName;
				
				if (this.userId === OC.getCurrentUser().uid) {
					value = OC.getCurrentUser().displayName;
				} else {
					if (!this.displayName) {
						value = this.userId;
					}
				}
				if (this.type === 'group') {
					value = value + ' (' + t('polls','Group') +')';
				}
				return value;
			},

			avatarURL: function() {
				if (this.userId === OC.getCurrentUser().uid) {
					return OC.generateUrl(
						'/avatar/{user}/{size}?v={version}',
						{
							user: OC.getCurrentUser().uid,
							size: Math.ceil(this.size * window.devicePixelRatio),
							version: oc_userconfig.avatar.version
					})
				} else {
					return OC.generateUrl(
						'/avatar/{user}/{size}',
						{
							user: this.userId,
							size: Math.ceil(this.size * window.devicePixelRatio),
					})
					
				}
			}
		}	
	}
</script>

<style lang="scss">
	.user-row {
		display: flex;
		flex-grow: 0;
		align-items: center;
		margin-left: 0;
		margin-top: 0;
		
		> div {
			margin: 2px 4px 2px 4px;
		}
		
		.description {
			opacity: 0.7;
			flex-grow: 0;
		}
		
		.avatar {
			height: 32px;
			width: 32px;
			flex-grow: 0;
		}
		
		.user-name {
			opacity: 0.5;
			flex-grow: 1;
		}
	}
</style>
