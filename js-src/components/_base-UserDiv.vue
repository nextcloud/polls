/* global Vue, oc_userconfig */
<template>
	<div class="userRow">
		<div class="description" v-show="description">{{description}}</div>
		<img class="avatar" :src="avatarURL" :width="size" :height="size">
		<div class="avatar imageplaceholderseed" v-show="nothidden" :data-username="userId" :data-displayname="computedDisplayName" data-seed="Poll users 1">
			{{ computedDisplayName.toUpperCase().substr(0,1) }}
		</div>
		<div class="description">{{ computedDisplayName }}</div>
	</div>

	<div class="avatar imageplaceholderseed"</div>
</template>

<script>
	export default {
		props: {
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

<style lang="scss" scoped>
	
	.userRow {
		display: flex;
		align-items: center;

		.avatar {
			margin: 0 8px;

			.imageplaceholderseed {
				height: 32px; 
				width: 32px; 
				background-color: rgb(185, 185, 185); 
				color: rgb(255, 255, 255); 
				font-weight: normal; 
				text-align: center; 
				line-height: 32px; 
				font-size: 17.6px;
			}
		}
	}
</style>
