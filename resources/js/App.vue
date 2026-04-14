<template>
  <main class="container">
    <h1>Eventy z CSV</h1>

    <section class="panel filters">
      <h2>Filtrowanie</h2>
      <div class="filters-grid">
        <label>
          Miasto
          <input v-model="filters.city" type="text" placeholder="np. Warszawa" />
        </label>

        <label>
          Kategoria
          <select v-model="filters.category">
            <option value="">Wszystkie</option>
            <option value="kids">kids</option>
            <option value="adults">adults</option>
          </select>
        </label>

        <label>
          Data od
          <input v-model="filters.date_from" type="date" />
        </label>

        <label>
          Data do
          <input v-model="filters.date_to" type="date" />
        </label>
      </div>

      <button @click="loadEvents" :disabled="loadingEvents">
        {{ loadingEvents ? 'Ładowanie...' : 'Zastosuj filtry' }}
      </button>
    </section>

    <section class="panel">
      <h2>Lista eventów (status = confirmed)</h2>
      <table>
        <thead>
          <tr>
            <th>Data wydarzenia</th>
            <th>Miasto</th>
            <th>Kategoria</th>
            <th>Suma sprzedanych biletów</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="events.length === 0">
            <td colspan="4">Brak danych dla wybranych filtrów.</td>
          </tr>
          <tr v-for="(event, idx) in events" :key="idx">
            <td>{{ event.event_date }}</td>
            <td>{{ event.city }}</td>
            <td>{{ event.category }}</td>
            <td>{{ event.confirmed_tickets_sum }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="panel">
      <h2>Top 10 utm_campaign</h2>
      <ol>
        <li v-for="item in utmRanking" :key="item.utm_campaign">
          <strong>{{ item.utm_campaign }}</strong> - {{ item.confirmed_tickets_sum }}
        </li>
      </ol>
    </section>
  </main>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';

const events = ref([]);
const utmRanking = ref([]);
const loadingEvents = ref(false);

const filters = reactive({
  city: '',
  category: '',
  date_from: '',
  date_to: '',
});

async function loadEvents() {
  loadingEvents.value = true;

  try {
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
      if (value) {
        params.append(key, value);
      }
    });

    const query = params.toString();
    const response = await fetch(`/api/events${query ? `?${query}` : ''}`);
    const payload = await response.json();
    events.value = payload.data ?? [];
  } finally {
    loadingEvents.value = false;
  }
}

async function loadUtmRanking() {
  const response = await fetch('/api/utm-ranking');
  const payload = await response.json();
  utmRanking.value = payload.data ?? [];
}

onMounted(async () => {
  await Promise.all([loadEvents(), loadUtmRanking()]);
});
</script>
