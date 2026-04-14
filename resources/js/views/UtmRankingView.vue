<template>
  <section class="panel">
    <h2>Top 10 utm_campaign</h2>

    <ol class="ranking-list">
      <li v-for="item in utmRanking" :key="item.utm_campaign">
        <strong>{{ item.utm_campaign }}</strong>
        <span>{{ item.confirmed_tickets_sum }}</span>
      </li>
    </ol>

    <p v-if="loading">Ladowanie rankingu...</p>
    <p v-else-if="utmRanking.length === 0">Brak danych rankingowych.</p>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';

const utmRanking = ref([]);
const loading = ref(false);

async function loadUtmRanking() {
  loading.value = true;

  try {
    const response = await fetch('/api/utm-ranking');
    const payload = await response.json();
    utmRanking.value = payload.data ?? [];
  } finally {
    loading.value = false;
  }
}

onMounted(loadUtmRanking);
</script>
