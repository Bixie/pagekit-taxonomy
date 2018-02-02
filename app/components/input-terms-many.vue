<template>

    <div>
        <div v-if="selected.length" class="uk-grid">
            <div v-for="term in selected" class="uk-width-1-1">
                <div class="uk-badge uk-flex uk-flex-middle uk-margin-small-bottom"
                     track-by="$index">
                    <span class="uk-flex-item-1 uk-text-left">{{ term.title }} </span>
                    <small class="uk-margin-small-left">({{ term.slug }})</small>
                    <a @click="remove(term)" class="uk-close uk-margin-small-left"></a>
                </div>
            </div>
        </div>

        <p>
            <button type="button" class="uk-button uk-button-small" @click="pick">
            {{ 'Select' | trans }} {{ taxonomy.label_plural | trans }}</button>
        </p>

        <v-modal v-ref:modal>

            <div :is="'terms-list-' + taxonomy.type" v-ref:terms-list
                 :taxonomy="taxonomy"
                 :excluded="excluded"></div>

            <div class="uk-modal-footer uk-text-right">
                <button class="uk-button uk-button-link uk-modal-close" type="button">{{ 'Cancel' | trans }}</button>
                <button class="uk-button uk-button-primary" type="button" :disabled="!hasSelection()" @click="select()">
                    {{ 'Select' | trans }}
                </button>
            </div>

        </v-modal>

    </div>

</template>

<script>
/*global _*/
import TermsListSingle from './terms-list-single.vue';
import TermsListHierarchical from './terms-list-hierarchical.vue';

export default {

    name: 'InputTermsMany',

    components: {
        'terms-list-single': TermsListSingle,
        'terms-list-hierarchical': TermsListHierarchical,
    },

    props: {
        'taxonomyName': String,
        'item_id': Number,
        'onSelect': {type: Function, default: _.noop,},
        'onRemove': {type: Function, default: _.noop,},
    },

    data: () => ({
        taxonomy: false,
        selected: [],
    }),

    computed: {
        excluded() {
            return this.selected.map(term => term.id);
        },
    },

    created() {
        this.resource = this.$resource('api/taxonomy{/id}');
        this.load();
    },

    methods: {

        load() {
            return this.resource.query({id: 'item',}, {
                taxonomyName: this.taxonomyName,
                item_id: this.item_id,
            }).then(res => {
                this.$set('taxonomy', res.data.taxonomy);
                this.$set('selected', res.data.terms);
            }, () => this.$notify('Loading failed.', 'danger'));
        },

        save() {
            return this.resource.save({id: 'item',}, {
                taxonomyName: this.taxonomyName,
                item_id: this.item_id,
                terms: this.selected,
            }).then(res => {
                this.$set('selected', res.data.terms);
                this.$notify('Terms saved')
            }, () => this.$notify('Loading failed.', 'danger'));
        },

        pick() {
            this.$refs.modal.open();
        },

        select() {
            let selected = _.filter(this.$refs.termsList.getSelected(),
                term => _.find(this.selected, {id: term.id,}) === undefined);
            this.selected = this.selected.concat(selected);
            this.save().then(() => {
                this.$refs.modal.close();
                this.onSelect(this.selected);
            });
        },

        remove(item) {
            this.selected.$remove(item);
            this.save().then(() => {
                this.onRemove(item);
            });
        },

        hasSelection() {
            return this.$refs.termsList.nrSelected() > 0;
        },

        isSelected(term) {
            return this.selected === term;
        },

    },

};

</script>