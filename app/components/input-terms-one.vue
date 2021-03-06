<template>

    <div v-if="taxonomy">
        <div v-if="selected" class="uk-badge uk-flex uk-flex-middle">
            <span class="uk-flex-item-1 uk-text-left">{{ selected.title }} </span>
            <small class="uk-margin-small-left">({{ selected.slug }})</small>
            <a @click="remove" class="uk-close uk-margin-small-left"></a>
        </div>

        <p>
            <button type="button" class="uk-button uk-button-small" @click="pick">
            {{ 'Select' | trans }} {{ taxonomy.label_single | trans }}</button>
        </p>

        <v-modal v-ref:modal>

            <div :is="'terms-list-' + taxonomy.type" v-ref:terms-list
                 :taxonomy="taxonomy"
                 :excluded="excluded"
                 :allow-add="allowAdd"></div>

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

    name: 'InputTermsOne',

    components: {
        'terms-list-single': TermsListSingle,
        'terms-list-hierarchical': TermsListHierarchical,
    },

    props: {
        'taxonomyName': String,
        'item_id': Number,
        'allowAdd': {type: Boolean, default: true,},
        'onSelect': {type: Function, default: _.noop,},
        'onRemove': {type: Function, default: _.noop,},
    },

    data: () => ({
        taxonomy: false,
        selected: false,
    }),

    computed: {
        excluded() {
            return this.selected ? [this.selected.id,] : [];
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
                this.$set('selected', res.data.terms[0]);
            }, () => this.$notify('Loading failed.', 'danger'));
        },

        save() {
            return this.resource.save({id: 'item',}, {
                taxonomyName: this.taxonomyName,
                item_id: this.item_id,
                terms: [this.selected,],
            }).then(res => {
                this.$set('selected', res.data.terms[0]);
                this.$notify('Terms saved')
            }, () => this.$notify('Loading failed.', 'danger'));
        },

        pick() {
            this.$refs.modal.open();
        },

        select() {
            this.selected = _.first(this.$refs.termsList.getSelected());
            this.save().then(() => {
                this.$refs.modal.close();
                this.onSelect(this.selected);
            });
        },

        remove() {
            this.selected = false;
            this.save().then(() => {
                this.onRemove();
            });
        },

        hasSelection() {
            return this.$refs.termsList.nrSelected() === 1;
        },

        isSelected(term) {
            return this.selected === term;
        },

    },

};

</script>
