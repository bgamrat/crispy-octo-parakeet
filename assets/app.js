// assets/app.js

// Thanks to: https://github.com/igeligel/vuex-simple-structure

import Vue from 'vue';
import App from './App';
import router from './router';
import store from './store';

import Example from './components/User/Example'
import BootstrapVue from 'bootstrap-vue'
import { Button, Navbar, Form, Layout } from 'bootstrap-vue/es/components'

Vue.use(Button);
Vue.use(Layout);
Vue.use(BootstrapVue)
Vue.use(Navbar)
Vue.use(Form);

Vue.config.productionTip = false;

//import './components/globals'

/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  store,
  template: '<App />',
  components: { App },
});