# Konserwatorium Muzyczne - SPA Laravel + Vue (CSV only)

## Demo online

Aplikacja zostala zhostowana na Laravel Cloud i jest dostepna pod adresem:

https://laravel-csv-event-viewer-main-9v5oy8.free.laravel.cloud/#/

Prosta aplikacja SPA z backendem w Laravel i frontendem w Vue.
Źródłem danych jest wyłącznie plik CSV. Brak logowania, autoryzacji i brak klasycznej bazy danych dla danych biznesowych.

## Cel funkcjonalny

1. Lista eventów:
- data wydarzenia
- miasto
- kategoria
- suma sprzedanych biletów
- liczone wyłącznie dla status = confirmed

2. Ranking UTM:
- Top 10 kampanii utm_campaign
- sortowanie malejąco po łącznej liczbie sprzedanych biletów (confirmed)

3. Filtrowanie listy eventów:
- city
- zakres dat event_date (from - to)
- category (kids/adults)

## Stack

- PHP + Laravel 12
- Vue 3 + Vite
- calebporzio/sushi (model Eloquent oparty o CSV)

## Uruchomienie projektu

1. Instalacja zależności PHP:

```bash
composer install
```

2. Instalacja zależności frontend:

```bash
npm install
```

3. Start backendu:

```bash
php artisan serve
```

4. Start frontendu (drugi terminal):

```bash
npm run dev
```

5. Aplikacja:
- http://127.0.0.1:8000

## Widoki Vue

Frontend jest podzielony na 2 osobne widoki (SPA z vue-router):

1. Lista eventów + filtrowanie
- URL: http://127.0.0.1:8000/#/
- plik widoku: resources/js/views/EventsView.vue
- zawiera formularz filtrów:
	- city
	- category
	- date_from
	- date_to
- po kliknięciu "Zastosuj filtry" pobiera dane z `/api/events`
- wyświetla tabelę eventów z polami:
	- event_id
	- event_date
	- city
	- category
	- confirmed_tickets_sum

2. Ranking UTM
- URL: http://127.0.0.1:8000/#/utm-ranking
- plik widoku: resources/js/views/UtmRankingView.vue
- pobiera dane z `/api/utm-ranking`
- wyświetla ranking Top 10 kampanii utm_campaign z wartością confirmed_tickets_sum

Nawigacja między widokami:
- layout i menu: resources/js/App.vue
- konfiguracja tras: resources/js/router/index.js

## Dane wejściowe CSV

Plik danych:
- resources/data/events.csv

Oczekiwane kolumny:
- event_id
- event_date
- city
- category
- order_id
- ticket_qty
- status
- utm_source
- utm_campaign
- utm_content
- sold_out

## Endpointy API

1. GET /api/events
- zwraca listę eventów zagregowanych po event_id
- suma ticket_qty tylko dla status=confirmed

Parametry (opcjonalne):
- city
- category
- date_from
- date_to

Przykład:

```http
GET /api/events?city=Warsaw&category=kids&date_from=2026-04-01&date_to=2026-04-30
```

2. GET /api/utm-ranking
- zwraca Top 10 kampanii utm_campaign
- sortowanie malejąco po confirmed_tickets_sum

## Struktura projektu (najważniejsze pliki)

- app/Models/CsvEvent.php
	Model Eloquent oparty o Sushi. Odczytuje i mapuje rekordy CSV do obiektów modelu.

- app/Services/CsvEventAnalyticsService.php
	Serwis analityczny: agregacje eventów, ranking UTM i nakładanie filtrów.

- app/Http/Controllers/EventAnalyticsController.php
	Kontroler API zwracający dane dla endpointów /api/events i /api/utm-ranking.

- routes/api.php
	Definicje tras API.

- resources/js/App.vue
	Główny layout SPA i nawigacja między widokami.

- resources/js/router/index.js
	Definicja tras frontendu (`/#/` i `/#/utm-ranking`).

- resources/js/views/EventsView.vue
	Widok listy eventów i panel filtrowania.

- resources/js/views/UtmRankingView.vue
	Widok rankingu kampanii UTM.

- tests/Feature/EventAnalyticsApiTest.php
	Testy endpointów zgodności z wymaganiami zadania.

## Jak działa serwis analityczny

Plik: app/Services/CsvEventAnalyticsService.php

1. `getEventSummaries(array $filters = [])`
- buduje zapytanie na modelu `CsvEvent`
- wymusza `status = confirmed`
- nakłada filtry (`city`, `category`, `date_from`, `date_to`)
- pobiera rekordy i grupuje je po `event_id`
- dla każdej grupy zwraca:
	- `event_id`
	- `event_date`
	- `city`
	- `category`
	- `confirmed_tickets_sum` = suma `ticket_qty`
- sortuje wynik po `event_date`

2. `getTopUtmCampaigns(int $limit = 10)`
- pobiera rekordy `status = confirmed`
- grupuje po `utm_campaign` (puste wartości mapuje na `unknown`)
- sumuje `ticket_qty`
- sortuje malejąco po sumie
- zwraca pierwsze 10 pozycji

3. `applyFilters(Builder $query, array $filters)`
- `city` -> `where city = ...`
- `category` -> `where category = ...`
- `date_from` -> `where event_date >= ...`
- `date_to` -> `where event_date <= ...`

## Jak działa model CSV (Sushi)

Plik: app/Models/CsvEvent.php

- model korzysta z traita `Sushi`
- metoda `getRows()`:
	- otwiera `resources/data/events.csv`
	- odczytuje nagłówek
	- mapuje kolejne linie na tablice asocjacyjne
	- dodaje techniczne pole `id` (wymagane jako klucz modelu)
- dzięki temu rekordy CSV są dostępne przez API Eloquent (`query`, `where`, `get`, `groupBy`)

## Testy

Uruchom wszystkie testy:

```bash
php artisan test
```

Uruchom tylko testy endpointów analytics:

```bash
php artisan test --filter=EventAnalyticsApiTest
```

Zakres testów w tests/Feature/EventAnalyticsApiTest.php:
- lista eventów: confirmed-only + poprawna agregacja biletów
- filtrowanie: city + category + date range
- ranking UTM: Top 10 + sortowanie malejące
