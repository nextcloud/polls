<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
		<div v-if="pollType === 'textRankPoll'">
			<span v-if="disabled" class="selected-value">
      			{{ selectedRank }}
    			</span>
			<select v-else v-model="selectedRank" class="vote-ranking"  @change="handleRankSelected">
				<option disabled value=""></option>
				<option v-for="rank in resolvedChosenRank" :key="rank" :value="rank">
					{{ rank }}
				</option>
			</select>
		</div>
		<div v-else :class="['vote-indicator', active]" @click="onClick()">
		<MaybeIcon v-if="answer==='maybe'" :size="iconSize" />
		<CheckIcon v-if="answer==='yes'" :fill-color="foregroundColor" :size="iconSize" />
		<CloseIcon v-if="answer==='no'" :fill-color="foregroundColor" :size="iconSize" />
	</div>
</template>

<script>

import CheckIcon from 'vue-material-design-icons/Check.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import { MaybeIcon } from '../AppIcons/index.js'
import { mapGetters, mapState } from 'vuex'

export default {
	name: 'VoteIndicator',
	components: {
		CloseIcon,
		CheckIcon,
		MaybeIcon,
	},

	props: {
		answer: {
			type: String,
			default: '',
		},
		active: {
			type: Boolean,
			default: false,
		},
		 disabled: { // Ajouter la prop disabled
      		 	type: Boolean,
      			default: false,
    		},
	},

	data() {
		return {
			selectedRank: this.answer,
			iconSize: 31,
			colorCodeNo: getComputedStyle(document.documentElement).getPropertyValue('--color-error'),
			colorCodeYes: getComputedStyle(document.documentElement).getPropertyValue('--color-success'),
			colorCodeMaybe: getComputedStyle(document.documentElement).getPropertyValue('--color-warning'),
		}
	},
	computed: {
		...mapState({
			pollType: (state) => state.poll.type,
		}),

		...mapGetters({
			validRanks: 'poll/getChosenRank',
		}),


		resolvedChosenRank() {
         	   return this.validRanks || [];
        	},

		foregroundColor() {
			if (this.answer === 'yes') {
				return this.colorCodeYes
			}
			if (this.answer === 'maybe') {
				return this.colorCodeMaybe
			}
			return this.colorCodeNo
		},
	},
	watch: {
  	answer(newValue) {
   	 this.selectedRank = newValue; // Mettre à jour selectedRank lorsque answer change
  		},
	},
	methods: {
		handleRankSelected(event) {
	        const rank =event.target.value;
    		this.$emit('select-change', rank);
    		},
		onClick() {
			if (this.active) {
				this.$emit('click')
			}
		},
	},
}
</script>

<style lang="scss">
.disabled-select {
  background-color: #f0f0f0; /* Couleur de fond pour le désactivé */
  cursor: not-allowed; /* Curseur "non autorisé" */
}

.vote-indicator {
	&, * {
		transition: all 0.4s ease-in-out;
		.active & {
			cursor: pointer;
		}
	}
	display: flex;
	justify-content: center;
	align-content: end;
	color: var(--color-polls-foreground-no);
	width: 30px;
	height: 30px;

	.active & {
		border: 2px solid;
		border-radius: var(--border-radius);
		.material-design-icon {
			width: 26px;
			height: 26px;
		}
	}
	.yes & {
		color: var(--color-polls-foreground-yes);
	}

	.maybe & {
		color: var(--color-polls-foreground-maybe);
	}

	.active:hover & {
		width: 35px;
		height: 35px;
		.material-design-icon {
			width: 31px;
			height: 31px;
		}

	}
}
.vote-ranking {
	justify-content: center;
	width: 52px;
	height: 45px;
	margin: 0 auto;

	.active & {
		border: 1px solid;
		border-radius: var(--border-radius);
		color: var(--color-polls-foreground-no);

		.material-design-icon {
			width: 52px;
			height: 45px;
		}
	}
}
.selected-value {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 15px; /* Ajustez le padding pour plus d'espace */
  border: 1px solid #ccc;
  border-radius: var(--border-radius);
  min-width: 52px; /* Largeur minimale */
  min-height: 45px; /* Hauteur minimale */
  width: fit-content; /* S'adapte à la largeur du contenu */
  margin: 0 auto;
  color: white;
  background-color: #333;
  white-space: nowrap; /* Empêche le texte de passer à la ligne */
  font-size: 16px; /* Ajustez la taille de la police si nécessaire */
  transition: all 0.3s ease-in-out; /* Transition fluide */
}
.error-message {
	color: var(--color-error);
}

</style>
