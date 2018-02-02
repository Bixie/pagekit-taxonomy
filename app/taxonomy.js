import TermsSingle from './components/terms-single.vue';
import TermsHierarchical from './components/terms-hierarchical.vue';
import InputTermsMany from './components/input-terms-many.vue';
import InputTermsOne from './components/input-terms-one.vue';

if (window.Vue) {

    window.Vue.component('terms-single', TermsSingle);
    window.Vue.component('terms-hierarchical', TermsHierarchical);
    window.Vue.component('input-terms-many', InputTermsMany);
    window.Vue.component('input-terms-one', InputTermsOne);

}