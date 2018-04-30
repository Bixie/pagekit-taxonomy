<template>
    <div>

        <div class="uk-margin uk-flex uk-flex-space-between uk-flex-wrap" data-uk-margin>
            <div class="uk-flex uk-flex-middle uk-flex-wrap" data-uk-margin>

                <h2 class="uk-margin-remove">{{ taxonomy.label_plural | trans }}</h2>

                <div class="pk-search">
                    <div class="uk-search">
                        <input class="uk-search-field" type="text" v-model="search">
                    </div>
                </div>

            </div>
            <div v-if="allowAdd">

                <button type="button" class="uk-button uk-button-primary" @click="add">
                {{ 'Add' | trans }} {{ taxonomy.label_single | trans }}</button>

            </div>
        </div>

        <div class="uk-overflow-container uk-form" :style="overflowStyle">

            <div class="pk-table-fake pk-table-fake-header" :class="{'pk-table-fake-border': !tree[0]}">
                <div class="pk-table-width-minimum pk-table-fake-nestable-padding">
                    <input type="checkbox" v-check-all:selected.literal="input[name=id]" number>
                </div>
                <div class="pk-table-min-width-200">{{ 'Title' | trans }}</div>
                <div class="pk-table-min-width-100">{{ 'Slug' | trans }}</div>
            </div>

            <ul class="uk-nestable uk-margin-remove" v-el:nestable v-show="tree[0]">
                <term-row v-for="term in tree[0]" :tree="tree" :term="term"></term-row>
            </ul>

        </div>

        <h3 v-show="terms && !terms.length" class="uk-text-muted uk-text-center">{{ 'No terms found.' | trans }}</h3>

        <v-modal v-ref:form large>
            <partial :name="taxonomy.options.term_type"></partial>
        </v-modal>

         <script id="term" type="text/template">

            <li class="uk-nestable-item" :class="{
                                    'uk-parent': tree[term.id],
                                    'uk-active': rootVm.active(term),
                                    'uk-hidden': !rootVm.visible(term),
                                }" :data-id="term.id">
                <div class="uk-nestable-panel pk-table-fake uk-visible-hover uk-form" @click="rootVm.toggle(term)">
                    <div class="pk-table-width-minimum pk-table-collapse">
                        <div class="uk-nestable-toggle" data-nestable-action="toggle"></div>
                    </div>
                    <div class="pk-table-width-minimum">
                        <input type="checkbox" name="id" :value="term.id" :disabled="rootVm.disabled(term)" number>
                    </div>
                    <div class="pk-table-min-width-200">
                            <span v-if="rootVm.disabled(term)" class="uk-text-muted">{{ term.title }}</span>
                            <a v-else>{{ term.title }}</a>
                    </div>
                    <div class="pk-table-width-100">{{ term.slug }}</div>
                </div>

                <ul class="uk-nestable-list" v-show="tree[term.id]">
                    <term-row v-for="term in tree[term.id]" :tree="tree" :term="term"></term-row>
                </ul>

            </li>

        </script>
   </div>
</template>

<script>
import TermRawTemplate from '../templates/term-raw.html';
import TermContentTemplate from '../templates/term-content.html';

export default {

    name: 'TermsListHierarchical',

    partials: {
        'term-raw': TermRawTemplate,
        'term-content': TermContentTemplate,
    },

    components: {
        // @vue/component
        'term-row': {
            name: 'TermRow',
            props: {'term': Object, 'tree': Object,},
            computed: {
                rootVm() {
                    let root = false, vm = this;
                    do {
                        if (vm.$options.name === 'TermsListHierarchical') {
                            root = vm;
                        }
                        vm = vm.$parent;
                    } while (vm && !root);
                    return root;
                },
            },
            methods: {
                toggleStatus() {
                    this.term.status = this.term.status === 1 ? 0 : 1;
                    this.rootVm.save(this.term)
                },
            },
            template: '#term',
        },
    },

    props: {
        'taxonomy': Object,
        'excluded': {type: Array, default: () => ([]),},
        'limit': {type: Number, default: 1000,},
        'allowAdd': {type: Boolean, default: true,},
        'maxHeight': {type: Number, default: 0,},
    },

    data() {
        return {
            edit: null,
            terms: false,
            search: '',
            config: {
                statuses: {},
                taxonomyName: this.taxonomy.name,
                filter: {status: 1, order: 'priority asc',},
                limit: this.limit,
            },
            selected: [],
            index: {},
            tree: {},
            form: {},
        }
    },

    computed: {
        visibleIds() {
            if (this.search) {
                const search = this.search.toLowerCase();
                let ids = [];
                this.terms.forEach(term => {
                    if (term.title.toLowerCase().indexOf(search) > -1 ||
                        term.slug.toLowerCase().indexOf(search) > -1) {
                        ids.push(term.id);
                        if (term.parent_id && ids.indexOf(term.parent_id) === -1 && this.index[term.parent_id] !== undefined) {
                            let parent = this.index[term.parent_id];
                            ids.push(parent.id);
                            while (parent.parent_id && ids.indexOf(parent.parent_id) === -1 && this.index[parent.parent_id] !== undefined) {
                                ids.push(parent.parent_id);
                                parent = this.index[parent.parent_id];
                            }
                        }
                    }
                });
                return ids;
            }
            return Object.keys(this.index).map(id => Number(id));
        },
        overflowStyle() {
            if (this.maxHeight > 0) {
                return `max-height: ${this.maxHeight}px;"`;
            }
            return '';
        }
    },

    watch: {
        'terms': {
            handler: function () {
                this.tree = _(this.terms).sortBy('priority').groupBy('parent_id').value();
                this.index = {};
                this.terms.forEach(term => this.index[term.id] = term);
            },
            deep: true,
        },
    },

    created() {
        this.resource = this.$resource('api/taxonomy{/id}');
        this.load();
    },

    ready() {
        UIkit.nestable(this.$els.nestable, {
            maxDepth: 20,
            group: 'taxonomy.terms.' + this.taxonomy.name,
        }).on('change.uk.nestable', (e, nestable, el, type) => {
            if (type && type !== 'removed') {
                this.resource.save({id: 'updateOrder',}, {
                    taxonomyName: this.taxonomy.name,
                    terms: nestable.list(),
                }).then(this.load, () => this.$notify('Reorder failed.', 'danger'));
            }
        });
    },

    methods: {
        active(term) {
            return this.selected.indexOf(term.id) !== -1;
        },

        visible(term) {
            return this.visibleIds.indexOf(term.id) !== -1;
        },

        disabled(term) {
            return this.excluded.indexOf(term.id) !== -1;
        },

        load() {
            this.resource.query(this.config).then(res => {
                let data = res.data;

                this.$set('terms', data.terms);
                this.$set('selected', []);
                this.$set('config.statuses', data.statuses);
            }, () => this.$notify('Loading failed.', 'danger'));
        },

        add() {
            this.$set('edit', {
                id: 0,
                title: '',
                slug: '',
                status: 1,
                type: this.taxonomy.type,
                link: '@todo',
                taxonomy: this.taxonomy.name,
            });

            this.$refs.form.open();
        },

        saveTerm(term) {
            this.resource.save({id: term.id,}, {term,})
                .then(() => this.load(), res => this.$notify(res.data, 'danger'))
                .then(() => this.$refs.form.close());
        },

        toggle(term) {
            if (!this.disabled(term)) {
                this[this.active(term) ? 'deselect' : 'select'](term)
            }
        },

        select(term) {
            if (this.selected.indexOf(term.id) === -1) {
                this.selected.push(term.id)
            }
        },

        deselect(term) {
            const idx = this.selected.indexOf(term.id);
            if (idx > -1) {
                this.selected.splice(idx, 1);
            }
        },

        nrSelected() {
            return this.selected.length;
        },

        getSelected() {
            return this.terms.filter(term => this.selected.indexOf(term.id) !== -1);
        },

    },

};

</script>
