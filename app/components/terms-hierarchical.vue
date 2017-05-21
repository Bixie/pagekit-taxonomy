<template>

    <div v-if="taxonomy" class="uk-margin uk-flex uk-flex-space-between uk-flex-wrap" data-uk-margin>
        <div class="uk-flex uk-flex-middle uk-flex-wrap" data-uk-margin>

            <h2 class="uk-margin-remove">{{ taxonomy.label_plural }}</h2>

            <div class="uk-margin-left" v-show="selected.length">
                <ul class="uk-subnav pk-subnav-icon">
                    <li><a class="pk-icon-check pk-icon-hover" :title="$trans('Publish')"
                           data-uk-tooltip="{delay: 500}" @click="status(1)"></a></li>
                    <li><a class="pk-icon-block pk-icon-hover" :title="$trans('Unpublish')"
                           data-uk-tooltip="{delay: 500}" @click="status(0)"></a></li>
                    <li><a class="pk-icon-delete pk-icon-hover" :title="'Delete' | trans"
                           data-uk-tooltip="{delay: 500}" @click.prevent="remove"
                           v-confirm="'Delete terms?' | trans"></a>
                    </li>
                </ul>
            </div>

            <div class="pk-search">
                <div class="uk-search">
                    <input class="uk-search-field" type="text" v-model="config.filter.search" debounce="300">
                </div>
            </div>

        </div>
        <div class="uk-position-relative" data-uk-margin>

            <div>
                <button type="button" class="uk-button uk-button-primary" @click="editTerm()">
                    {{ 'Add' | trans }} {{ taxonomy.label_single }}</button>
            </div>

        </div>
    </div>

    <div class="uk-overflow-container uk-form">

        <div class="pk-table-fake pk-table-fake-header" :class="{'pk-table-fake-border': !tree[0]}">
            <div class="pk-table-width-minimum pk-table-fake-nestable-padding"><input type="checkbox" v-check-all:selected.literal="input[name=id]" number></div>
            <div class="pk-table-min-width-100">{{ 'Title' | trans }}</div>
            <div class="pk-table-width-100 uk-text-center">{{ 'Status' | trans }}</div>
            <div class="pk-table-width-100">{{ '# Items' | trans }}</div>
            <div class="pk-table-width-150">{{ 'URL' | trans }}</div>
        </div>

        <ul class="uk-nestable uk-margin-remove" v-el:nestable v-show="tree[0]">
            <term-row v-for="term in tree[0]" :tree="tree" :term="term"></term-row>
        </ul>

    </div>

    <h3 class="uk-h1 uk-text-muted uk-text-center" v-show="tree && !tree[0]">{{ 'No terms found.' | trans }}</h3>

    <v-modal v-ref:form large>
        <partial :name="taxonomy.options.term_type"></partial>
    </v-modal>

    <script id="term" type="text/template">

        <li class="uk-nestable-item check-item" :class="{'uk-parent': tree[term.id], 'uk-active': rootVm.isSelected(term)}" :data-id="term.id">
            <div class="uk-nestable-panel pk-table-fake uk-visible-hover">
                <div class="pk-table-width-minimum pk-table-collapse">
                    <div class="uk-nestable-toggle" data-nestable-action="toggle"></div>
                </div>
                <div class="pk-table-width-minimum"><input type="checkbox" name="id" :value="term.id" number></div>
                <div class="pk-table-min-width-200">
                    <a @click="rootVm.editTerm(term)">{{ term.title }}</a><br/>
                    <small class="uk-text-muted">{{ term.slug }}</small>
                </div>
                <div class="pk-table-width-100 uk-text-center">
                    <td class="uk-text-center">
                        <a :class="{'pk-icon-circle-danger': !term.status, 'pk-icon-circle-success': term.status}" @click="toggleStatus"></a>
                    </td>
                </div>
                <div class="pk-table-width-100 uk-text-center">#</div>
                <div class="pk-table-width-200 pk-table-max-width-150 uk-text-truncate">
                    <a :title="term.url" target="_blank" :href="$url.route(term.url.substr(1))"
                       v-if="term.url">{{ decodeURI(term.url) }}</a>
                    <span v-else>{{ term.path }}</span>
                </div>
            </div>

            <ul class="uk-nestable-list" v-show="tree[term.id]">
                <term-row v-for="term in tree[term.id]" :tree="tree" :term="term"></term-row>
            </ul>

        </li>

    </script>

</template>

<script>

    module.exports = {

        name: 'terms-hierarchical',

        props: {
            'taxonomyName': String,
        },

        replace: false,

        data() {
            return {
                edit: null,
                taxonomy: false,
                terms: false,
                config: {
                    statuses: {},
                    taxonomyName: this.taxonomyName,
                    filter: this.$session.get(`taxonomy.filter.${this.taxonomyName}`, {search: '', order: 'title asc'}),
                    page: 0,
                    limit: 1000,
                },
                pages: 0,
                count: '',
                selected: [],
                tree: [],
                form: {},
            }
        },

        created() {
            this.resource = this.$resource('api/taxonomy{/id}');
            this.$watch('config.page', this.load, {immediate: true});
        },

        ready() {

            var vm = this;

            UIkit.nestable(this.$els.nestable, {
                maxDepth: 20,
                group: 'taxonomy.terms'
            }).on('change.uk.nestable', function (e, nestable, el, type) {

                if (type && type !== 'removed') {

                    vm.resource.save({id: 'updateOrder'}, {
                        taxonomyName: vm.taxonomyName,
                        terms: nestable.list()
                    }).then(vm.load, () => vm.$notify('Reorder failed.', 'danger'));
                }
            });

        },

        watch: {

            'config.filter': {
                handler: function (filter) {
                    if (this.config.page) {
                        this.config.page = 0;
                    } else {
                        this.load();
                    }

                    this.$session.set(`taxonomy.filter.${this.taxonomyName}`, filter);
                },
                deep: true
            },

            'terms': {
                handler: function () {
                    this.$set('tree', _(this.terms).sortBy('priority').groupBy('parent_id').value());
                },
                deep: true
            }

        },


        computed: {

            statusOptions() {

                var options = _.map(this.config.statuses, (status, id) => {
                    return {text: status, value: id};
                });

                return [{value: '', text: this.$trans('Show all')}, {label: this.$trans('Filter by'), options}];
            },

        },

        methods: {
            editTerm(term) {

                if (!term) {
                    term = {
                        id: 0,
                        title: '',
                        slug: '',
                        status: 1,
                        type: this.taxonomy.type,
                        link: '@todo',
                        taxonomy: this.taxonomy.name,
                    };
                }

                this.$set('edit', _.merge({}, term));

                this.$refs.form.open();
            },

            saveTerm(term) {
                this.save(term).then(() => {
                    this.$notify('Term saved.');
                    this.$refs.form.close();
                });
            },

            active(term) {
                return this.selected.indexOf(term.id) !== -1;
            },

            load() {
                return this.resource.query(this.config).then(res => {
                    var data = res.data;

                    this.$set('taxonomy', data.taxonomy);
                    this.$set('terms', data.terms);
                    this.$set('pages', data.pages);
                    this.$set('count', data.count);
                    this.$set('config.statuses', data.statuses);
                    this.$set('selected', []);
                }, () => this.$notify('Loading failed.', 'danger'));
            },

            save(term) {
                return this.resource.save({id: term.id}, {term})
                    .then(res => this.load(), res => this.$notify(res.data, 'danger'))
            },

            status: function (status) {

                var terms = this.getSelected();

                terms.forEach(function (term) {
                    term.status = status;
                });

                this.resource.save({id: 'bulk'}, {terms}).then(function () {
                    this.load();
                    this.$notify('Term(s) saved.');
                });
            },

            getSelected: function () {
                return this.terms.filter(function (term) {
                    return this.isSelected(term);
                }, this);
            },

            isSelected: function (term, children) {

                if (_.isArray(term)) {
                    return _.every(term, function (term) {
                        return this.isSelected(term, children);
                    }, this);
                }

                return this.selected.indexOf(term.id) !== -1 && (!children || !this.tree[term.id] || this.isSelected(this.tree[term.id], true));
            },

            toggleSelect: function (term) {

                var index = this.selected.indexOf(term.id);

                if (index == -1) {
                    this.selected.push(term.id);
                } else {
                    this.selected.splice(index, 1);
                }
            },

            getStatusText(term) {
                return this.config.statuses[term.status];
            },

            remove() {
                this.resource.delete({id: 'bulk'}, {ids: this.selected}).then(() => {
                    this.load();
                    this.$notify('Terms deleted.');
                }, res => {
                    this.load();
                    this.$notify(res.data, 'danger');
                });
            },
        },

        partials: {
            'term-raw': require('../templates/term-raw.html'),
            'term-content': require('../templates/term-content.html'),
        },

        components: {

            'term-row': {

                name: 'term-row',
                props: ['term', 'tree'],
                template: '#term',

                computed: {

                    rootVm() {
                        var root = false, vm = this;
                        do {
                            if (vm.$options.name === 'terms-hierarchical') {
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
                    }
                }
            }

        }

    };


</script>