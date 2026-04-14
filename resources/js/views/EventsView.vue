<template>
  <section class="panel">
    <h2>Zarzadzanie plikiem CSV</h2>
    <div class="file-actions">
      <input type="file" accept=".csv,text/csv" @change="onFileSelected" />

      <button @click="uploadCsv" :disabled="uploading || !selectedFile">
        {{ uploading ? 'Wgrywanie...' : 'Wgraj wlasny CSV (max 5 MB)' }}
      </button>

      <button @click="resetCsv" :disabled="resetting" class="button-secondary">
        {{ resetting ? 'Resetowanie...' : 'Reset do domyslnego CSV' }}
      </button>
    </div>

    <p v-if="actionMessage" class="status-ok">{{ actionMessage }}</p>
    <p v-if="actionError" class="status-error">{{ actionError }}</p>
  </section>

  <section class="panel filters">
    <h2>Filtrowanie</h2>
    <div class="filters-grid">
      <label>
        Miasto
        <input v-model="filters.city" type="text" placeholder="np. Warsaw" />
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
      {{ loadingEvents ? 'Ladowanie...' : 'Zastosuj filtry' }}
    </button>
  </section>

  <section class="panel">
    <h2>Lista eventow (status = confirmed)</h2>
    <table>
      <thead>
        <tr>
          <th>
            <button class="sort-button" @click="setSort('event_id')">
              ID eventu {{ sortLabel('event_id') }}
            </button>
          </th>
          <th>
            <button class="sort-button" @click="setSort('event_date')">
              Data wydarzenia {{ sortLabel('event_date') }}
            </button>
          </th>
          <th>
            <button class="sort-button" @click="setSort('city')">
              Miasto {{ sortLabel('city') }}
            </button>
          </th>
          <th>
            <button class="sort-button" @click="setSort('category')">
              Kategoria {{ sortLabel('category') }}
            </button>
          </th>
          <th>
            <button class="sort-button" @click="setSort('confirmed_tickets_sum')">
              Suma sprzedanych biletow {{ sortLabel('confirmed_tickets_sum') }}
            </button>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="events.length === 0">
          <td colspan="5">Brak danych dla wybranych filtrow.</td>
        </tr>
        <tr v-for="event in sortedEvents" :key="event.event_id">
          <td>{{ event.event_id }}</td>
          <td>{{ event.event_date }}</td>
          <td>{{ event.city }}</td>
          <td>{{ event.category }}</td>
          <td>{{ event.confirmed_tickets_sum }}</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

const events = ref([]);
const loadingEvents = ref(false);
const uploading = ref(false);
const resetting = ref(false);
const selectedFile = ref(null);
const actionMessage = ref('');
const actionError = ref('');

const sortState = reactive({
  key: 'event_date',
  direction: 'asc',
});

const filters = reactive({
  city: '',
  category: '',
  date_from: '',
  date_to: '',
});

const sortedEvents = computed(() => {
  const items = [...events.value];

  items.sort((a, b) => {
    const dir = sortState.direction === 'asc' ? 1 : -1;

    if (sortState.key === 'confirmed_tickets_sum') {
      return ((Number(a[sortState.key]) || 0) - (Number(b[sortState.key]) || 0)) * dir;
    }

    const left = String(a[sortState.key] ?? '').toLowerCase();
    const right = String(b[sortState.key] ?? '').toLowerCase();

    if (left < right) {
      return -1 * dir;
    }

    if (left > right) {
      return 1 * dir;
    }

    return 0;
  });

  return items;
});

function setSort(key) {
  if (sortState.key === key) {
    sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
    return;
  }

  sortState.key = key;
  sortState.direction = 'asc';
}

function sortLabel(key) {
  if (sortState.key !== key) {
    return '↕';
  }

  return sortState.direction === 'asc' ? '↑' : '↓';
}

function onFileSelected(event) {
  const file = event.target.files?.[0] ?? null;
  selectedFile.value = file;
  actionMessage.value = '';
  actionError.value = '';
}

async function uploadCsv() {
  if (!selectedFile.value) {
    return;
  }

  actionMessage.value = '';
  actionError.value = '';
  uploading.value = true;

  try {
    const formData = new FormData();
    formData.append('file', selectedFile.value);

    const response = await fetch('/api/events/upload-csv', {
      method: 'POST',
      body: formData,
    });

    const payload = await response.json();

    if (!response.ok) {
      throw new Error(payload.message ?? 'Nie udalo sie wgrac pliku CSV.');
    }

    actionMessage.value = payload.message ?? 'Plik CSV zostal wgrany.';
    await loadEvents();
  } catch (error) {
    actionError.value = error.message;
  } finally {
    uploading.value = false;
  }
}

async function resetCsv() {
  actionMessage.value = '';
  actionError.value = '';
  resetting.value = true;

  try {
    const response = await fetch('/api/events/reset-csv', {
      method: 'POST',
    });

    const payload = await response.json();

    if (!response.ok) {
      throw new Error(payload.message ?? 'Nie udalo sie przywrocic domyslnego pliku.');
    }

    actionMessage.value = payload.message ?? 'Przywrocono domyslny plik CSV.';
    selectedFile.value = null;
    await loadEvents();
  } catch (error) {
    actionError.value = error.message;
  } finally {
    resetting.value = false;
  }
}

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

onMounted(loadEvents);
</script>
