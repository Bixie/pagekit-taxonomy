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

    <div v-if="taxonomy" class="uk-overflow-container uk-form">
        <table class="uk-table uk-table-hover uk-table-middle">
            <thead>
            <tr>
                <th class="pk-table-width-minimum"><input type="checkbox" v-check-all:selected.literal="input[name=id]" number></th>
                <th class="pk-table-width-100 uk-text-center">
                    <input-filter :title="$trans('Status')" :value.sync="config.filter.status" :options="statusOptions"></input-filter>
                </th>
                <th class="pk-table-min-width-200" v-order:title="config.filter.order">{{ 'Title' | trans }}</th>
                <th class="pk-table-min-width-200" v-order:slug="config.filter.order">{{ 'Slug' | trans }}</th>
                <th class="pk-table-width-200">{{ '# Items' | trans }}</th>
                <th class="pk-table-width-200">{{ 'URL' | trans }}</th>
            </tr>
            </thead>
            <tbody>
            <tr class="check-item" v-for="term in terms" :class="{'uk-active': active(term)}">
                <td><input type="checkbox" name="id" :value="term.id" number></td>
                <td class="uk-text-center">
                    <a title="{{ getStatusText(term) }}" :class="{
                                'pk-icon-circle-danger': term.status == 0,
                                'pk-icon-circle-success': term.status == 1
                            }" @click="toggleStatus(term)"></a>
                </td>
                <td>
                    <a @click="editTerm(term)">{{ term.title }}</a>
                </td>
                <td>
                    {{ term.slug }}
                </td>
                <td>
                    #
                </td>
                <td class="pk-table-text-break">
                    <a v-if="term.url" :href="$url.route(term.url)" target="_blank">{{ term.url }}</a>
                    <span v-else>{{ term.link }}</span>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <h3 class="uk-h1 uk-text-muted uk-text-center" v-show="terms && !terms.length">
        {{ 'No terms found.' | trans }}</h3>

    <v-pagination :page.sync="config.page" :pages="pages" v-show="pages > 1"></v-pagination>

    <v-modal v-ref:form large>
        <partial :name="taxonomy.options.term_type"></partial>
    </v-modal>


</template>

<script>

    module.exports = {

        name: 'terms-single',

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
                },
                pages: 0,
                count: '',
                selected: [],
                form: {},
            }
        },

        created() {
            this.resource = this.$resource('api/taxonomy{/id}');
            this.$watch('config.page', this.load, {immediate: true});
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
                this.save(term).then(() => this.$refs.form.close());
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

            getSelected() {
                return this.terms.filter(term => this.selected.indexOf(term.id) !== -1);
            },

            toggleStatus(term) {
                term.status = !!term.status ? 0 : 1;
                this.save(term).then(() => this.$notify(this.$trans('Term saved')));
            },

            status(status) {

                var terms = this.getSelected();

                terms.forEach(term => term.status = status);

                this.resource.save({id: 'bulk'}, {terms}).then(() => {
                    this.load();
                    this.$notify('Terms saved.');
                }, res => {
                    this.load();
                    this.$notify(res.data, 'danger');
                });
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
        },
    };


</script>