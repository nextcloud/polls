<template>
  <div class="option-container">
    <!-- Menu déroulant pour sélectionner une option -->
    <select v-model="selectedOption">
      <option v-for="(option, index) in internalChoosenRank" :key="index" :value="option">
        {{ option }}
      </option>
    </select>

    <!-- Champ de texte pour ajouter une nouvelle option -->
    <NcTextField
      v-model="newOption"
      :placeholder="t('polls', 'Enter a new option')"
      :label="t('polls', 'New option')"
      class="nc-text-field"
    />

    <!-- Bouton pour ajouter une nouvelle option -->
    <NcButton @click="addOption">
      <template #icon>
        <PlusIcon />
      </template>
      {{ t('polls', 'Add Option') }}
    </NcButton>

    <!-- Bouton pour supprimer l'option sélectionnée -->
    <NcButton @click="removeOption" :disabled="!selectedOption">
      <template #icon>
        <CloseIcon />
      </template>
      {{ t('polls', 'Remove Selected Option') }}
    </NcButton>
  </div>
</template>

<script>
import { mapState } from 'vuex';
import { t } from '@nextcloud/l10n';
import { NcButton, NcTextField } from '@nextcloud/vue';
import PlusIcon from 'vue-material-design-icons/Plus.vue';
import CloseIcon from 'vue-material-design-icons/Close.vue';
import { writePoll } from '../../mixins/writePoll.js'; // Importez le mixin

export default {
  name: 'ConfigRankOptions',
  mixins: [writePoll], // Ajoutez le mixin ici

  components: {
    NcButton,
    NcTextField,
    PlusIcon,
    CloseIcon,
  },

  props: {
    choosenRank: {
      type: String, // ou Array selon votre implémentation
      required: true,
    },
  },

  data() {
    return {
      selectedOption: null,
      newOption: '',
      internalChoosenRank: [], // Stocke la valeur parsée de choosenRank
    };
  },

  computed: {
    // Getter pour choosenRank
    parsedChoosenRank() {
      try {
        const parsed = JSON.parse(this.choosenRank); // Transforme en tableau
        return parsed;
      } catch (error) {
        console.error("Failed to parse choosenRank:", error);
        return []; // Retourne un tableau vide en cas d'erreur
      }
    },
  },

  watch: {
    // Synchronise internalChoosenRank avec parsedChoosenRank
    parsedChoosenRank: {
      immediate: true,
      handler(newValue) {
        this.internalChoosenRank = newValue;
      },
    },
  },

  methods: {
    async addOption() {
      const trimmedOption = this.newOption.trim();
      if (trimmedOption && !this.internalChoosenRank.includes(trimmedOption)) {
        const updatedChoosenRank = [...this.internalChoosenRank, trimmedOption];
        this.internalChoosenRank = updatedChoosenRank.sort(); // Met à jour localement
        this.newOption = '';
        await this.updateChoosenRank(updatedChoosenRank); // Met à jour dans le store et appelle writePoll()
      }
    },

    async removeOption() {
      if (this.selectedOption) {
        const updatedChoosenRank = this.internalChoosenRank.filter(option => option !== this.selectedOption);
        this.internalChoosenRank = updatedChoosenRank.sort(); // Met à jour localement
        if (updatedChoosenRank.length > 0) {
          this.selectedOption = updatedChoosenRank[0];
        } else {
          this.selectedOption = null;
        }
        await this.updateChoosenRank(updatedChoosenRank); // Met à jour dans le store et appelle writePoll()
      }
    },

    async updateChoosenRank(updatedChoosenRank) {
      try {
        // Met à jour choosenRank dans le store
        await this.$store.dispatch('poll/updateChoosenRank', JSON.stringify(updatedChoosenRank));
        // Appelle writePoll() du mixin pour enregistrer les modifications
        await this.writePoll();
      } catch (error) {
        console.error("Failed to update choosenRank:", error);
        showError(t('polls', 'Failed to update options')); // Affiche un message d'erreur
      }
    },
  },

  mounted() {
    if (this.parsedChoosenRank.length > 0) {
      this.selectedOption = this.parsedChoosenRank[0]; // Sélectionne la première option par défaut
    }
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
	margin-right: 8px;
	margin-bottom: 8px;margin-right: 8px;
}
.option-container {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}		/* Style pour le champ de texte */
.nc-text-field {
	flex-grow: 1;
	margin-right: 8px;
	margin-bottom: 8px;
	width: 100px; /* Ajustez la largeur selon vos besoins */

}
</style>
