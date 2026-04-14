<template>
  <main class="container">
    <h1>Eventy z CSV</h1>

    <nav class="top-nav" aria-label="Nawigacja widokow">
      <RouterLink to="/" class="top-nav-link">Lista eventow</RouterLink>
      <RouterLink to="/utm-ranking" class="top-nav-link">Ranking UTM</RouterLink>
    </nav>

    <RouterView />
  </main>
</template>
