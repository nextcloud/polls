<template>
  <div class="option-container">
    <!-- Menu to choose the rank for this poll -->
    <select v-model="selectedOption">
      <option v-for="(option, index) in internalChosenRank" :key="index" :value="option">
        {{ option }}
      </option>
    </select>

    <!-- text field to add a new value to the rank -->
    <NcTextField v-model="newOption"
      :placeholder="t('polls', 'Enter a new option')"
      :label="t('polls', 'New option')"
      class="nc-text-field"
    />

    <!-- and the boutton to add it -->
    <NcButton @click="addOption">
      <template #icon>
        <PlusIcon />
      </template>
      {{ t('polls', 'Add Option') }}
    </NcButton>

    <!-- Delete selected rank from the select -->
    <NcButton :disabled="!selectedOption" @click="removeOption">
      <template #icon>
        <CloseIcon />
      </template>
      {{ t('polls', 'Remove Selected Option') }}
    </NcButton>
  </div>
</template>

<script>
import { t } from '@nextcloud/l10n';
import { NcButton, NcTextField } from '@nextcloud/vue';
import PlusIcon from 'vue-material-design-icons/Plus.vue';
import CloseIcon from 'vue-material-design-icons/Close.vue';
import { writePoll } from '../../mixins/writePoll.js'; // Import the mixin
import { showError } from '@nextcloud/dialogs'
import { mapGetters } from 'vuex'

export default {
  name: 'ConfigRankOptions', // Add mixins here

  components: {
    NcButton,
    NcTextField,
    PlusIcon,
    CloseIcon,
  },
  mixins: [writePoll],

  props: {
    chosenRank: {
      type: Array, 
      required: true,
    },
  },

  data() {
    return {
      selectedOption: null,
      newOption: '',
    };
  },

  computed: {
       ...mapGetters({
	internalChosenRank: 'poll/getChosenRank',
	}),

    // Getter for chosenRank
    parsedChosenRank() {
      try {
        const parsed = JSON.parse(this.chosenRank); // Transform in array
        return parsed;
      } catch (error) {
        console.error("Failed to parse chosenRank:", error);
        return []; // Return blank array in case of error
      }
    },
  },
  watch: {
    // Synchronize internalChosenRank with parsedChosenRank
    parsedChosenRank: {
      immediate: true,
      handler(newValue) {
        this.internalChosenRank = newValue;
      },
    },
  },

  mounted() {
    if (this.parsedChosenRank.length > 0) {
      this.selectedOption = this.parsedChosenRank[0]; // Sélect first choice by default
    }
  },

  methods: {
    async addOption() {
      const trimmedOption = this.newOption.trim();
      if (trimmedOption && !this.internalChosenRank.includes(trimmedOption)) {
        const updatedChosenRank = [...this.internalChosenRank, trimmedOption];
        this.internalChosenRank = updatedChosenRank.sort(); // Update locally
        this.newOption = '';
        await this.updateChosenRank(updatedChosenRank); // Update the store and save poll
      }
    },

    async removeOption() {
      if (this.selectedOption) {
        const updatedChosenRank = this.internalChosenRank.filter(option => option !== this.selectedOption);
        this.internalChosenRank = updatedChosenRank.sort(); // Update locally
        if (updatedChosenRank.length > 0) {
          this.selectedOption = updatedChosenRank[0];
        } else {
          this.selectedOption = null;
        }
        await this.updateChosenRank(updatedChosenRank); // Update the store
      }
    },

    async updateChosenRank(updatedChosenRank) {
      try {
        // Update chosenRank into the store
        await this.$store.dispatch('poll/updateChosenRank', JSON.stringify(updatedChosenRank));
        // call writePoll() for save pool
        await this.writePoll();
      } catch (error) {
        console.error("Failed to update chosenRank:", error);
	showError(t('polls', 'Failed to update options')); 
      }
    },
  },
};
</script>

<style scoped>

select {
	width: 130px; 
	padding: 2px;
	border-radius: 4px;
	border: 1px solid var(--color-border);
	background-color: var(--color-main-background);
	color: var(--color-main-text);
	margin-right: 8px;
}

select:focus {
	outline: none;
	border-color: var(--color-primary);
}

button {
	margin-bottom: 8px;
	margin-right: 8px;
}

.option-container {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.nc-text-field {
	flex-grow: 1;
	margin-right: 8px;
	margin-bottom: 8px;
	width: 100px; 

}
</style>
