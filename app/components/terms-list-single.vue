<template>

    <div>

        <div class="uk-margin uk-flex uk-flex-space-between uk-flex-wrap" data-uk-margin>
            <div>

                <h2 class="uk-margin-small-bottom">{{ taxonomy.label_plural }}</h2>

                <div>
                    <div class="uk-search">
                        <input class="uk-search-field" type="text" v-model="config.filter.search" debounce="300">
                    </div>
                </div>

            </div>
            <div>

                <button type="button" class="uk-button uk-button-primary" @click="add">
                {{ 'Add' | trans }} {{ taxonomy.label_single | trans }}</button>

            </div>
        </div>

        <div class="uk-margin uk-overflow-container">
            <table class="uk-table uk-table-hover uk-table-middle uk-form">
                <thead>
                    <tr>
                        <th class="pk-table-width-minimum"><input type="checkbox" v-check-all:selected.literal="input[name=id]" number></th>
                        <th class="pk-table-min-width-200">{{ 'Name' | trans }}</th>
                        <th class="pk-table-min-width-100">{{ 'Slug' | trans }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="check-item" v-for="term in terms" :class="{'uk-active': active(term)}">
                        <td><input type="checkbox" name="id" :value="term.id" :disabled="disabled(term)"number></td>
                        <td>
                            <span v-if="disabled(term)" class="uk-text-muted">{{ term.title }}</span>
                            <a v-else @click="select(term)">{{ term.title }}</a>
                        </td>
                        <td>{{ term.slug }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h3 v-show="terms && !terms.length" class="uk-text-muted uk-text-center">{{ 'No terms found.' | trans }}</h3>

        <v-pagination :page.sync="config.page" :pages="pages" v-show="pages > 1" :replace-history="false"></v-pagination>

        <v-modal v-ref:form large>
            <partial :name="taxonomy.options.term_type"></partial>
        </v-modal>

    </div>
</template>

<script>
import TermRawTemplate from '../templates/term-raw.html';
import TermContentTemplate from '../templates/term-content.html';

export default {

    name: 'TermsListSingle',

    replace: false,

    partials: {
        'term-raw': TermRawTemplate,
        'term-content': TermContentTemplate,
    },

    props: {
        'taxonomy': Object,
        'excluded': {type: Array, default: () => ([]),},
        'limit': {type: Number, default: 10,},
    },

    data() {
        return {
            edit: null,
            terms: false,
            config: {
                statuses: {},
                taxonomyName: this.taxonomy.name,
                filter: {search: '', status: 1, order: 'title asc',},
                limit: this.limit,
                page: 0,
            },
            pages: 0,
            count: '',
            selected: [],
            form: {},
        }
    },

    watch: {

        'config.filter': {
            handler: function () {
                if (this.config.page) {
                    this.config.page = 0;
                } else {
                    this.load();
                }
            },
            deep: true,
        },

    },

    created() {
        this.resource = this.$resource('api/taxonomy{/id}');
        this.$watch('config.page', this.load, {immediate: true,});
    },

    methods: {
        active(term) {
            return this.selected.indexOf(term.id) !== -1;
        },

        disabled(term) {
            return this.excluded.indexOf(term.id) !== -1;
        },

        load() {
            this.resource.query(this.config).then(res => {
                let data = res.data;

                this.$set('terms', data.terms);
                this.$set('pages', data.pages);
                this.$set('count', data.count);
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

        select(term) {
            if (this.selected.indexOf(term.id) === -1) {
                this.selected.push(term.id)
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
