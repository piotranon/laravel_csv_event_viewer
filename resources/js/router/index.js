import { createRouter, createWebHashHistory } from 'vue-router';
import EventsView from '../views/EventsView.vue';
import UtmRankingView from '../views/UtmRankingView.vue';

const routes = [
  {
    path: '/',
    name: 'events',
    component: EventsView,
  },
  {
    path: '/utm-ranking',
    name: 'utm-ranking',
    component: UtmRankingView,
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
