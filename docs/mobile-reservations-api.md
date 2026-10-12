# Uputa za mobilnu aplikaciju: rezervacije opreme

Ovaj dokument opisuje REST API za rezervacije opreme koji koristi mobilna
aplikacija. Izvor istine je kod u `web/app/plugins/rkg-plugin/lib/Api/`
(`EquipmentReservation.php`, `Inventory.php`, `ApiEndpoint.php`). Ako se
dokument i kod razlikuju, vrijedi kod.

## Cilj

Mobilna aplikacija treba stranicu **Rezervacije** s:

1. listom svih rezervacija koje **nisu arhivirane** (bez obrisanih),
2. uređivanjem pojedinačne rezervacije (oprema, komentar, razduživanje, donacija),
3. arhiviranjem rezervacije.

## Pristup i autentifikacija

- Base URL: `https://<domena>/wp-json/rkg/v1/` (lokalno:
  `http://localhost:8080/wp-json/rkg/v1/`).
- Autentifikacija: WordPress **Application Password** za korisnika s
  ulogom koja ima capability `manage_equipment`. Šalje se kao
  `Authorization: Basic <base64(korisnik:application-password)>`.
- Bez ovlasti API vraća `403 rest_forbidden`.
- Ne spremaj lozinke ni tokene u repo. Koristi sigurno spremište
  mobilne aplikacije.

## Endpointi

| Metoda | Ruta | Namjena |
|---|---|---|
| GET | `/users` | Popis članova i polaznika aktivnog R1 tečaja za odabir korisnika |
| GET | `/reservations` | Lista nearhiviranih, nebrisanih rezervacija |
| POST | `/reservations` | Nova rezervacija bez izleta |
| POST | `/reservations/{id}` | Uređivanje i razduživanje (spremanje rezervacije) |
| DELETE | `/reservations/{id}` | Brisanje (soft delete, vraća opremu na stanje) |
| POST | `/reservations/{id}/archive` | Arhiviranje ili vraćanje iz arhive |
| GET | `/inventory` | Popis opreme (šifre, tip, veličina, status) |
| POST | `/inventory` | Dodavanje opreme |
| POST | `/inventory/{id}` | Uređivanje opreme |

Za parametre `/inventory` vidi `Api/Inventory.php`.

### GET `/reservations`

Parametri (query string):

- `state` (opcionalno): `0` Na čekanju, `1` Aktivno, `2` Vraćeno.
  **Bez `state` vraća sve statuse**, što je način za stranicu Rezervacije.
- `per_page` (zadano 100, najviše 200)
- `page` (zadano 1)

Odgovor je niz rezervacija. Ukupan broj je u headeru `X-WP-Total`,
a broj stranica u `X-WP-TotalPages`.

Lista uvijek isključuje arhivirane (`archived = true`) i obrisane (`state = 3`).
Sortira se od najnovije (`id` silazno).

### POST `/reservations/{id}/archive`

Tijelo: `{"archived": true}` ili `{"archived": false}`.

- Radi u bilo kojem statusu, uključujući rezervaciju s izdanom opremom.
- Obrisana rezervacija vraća `404`.
- Vraćanje iz arhive (`false`) je dopušteno.

### POST `/reservations/{id}` (uređivanje i razduživanje)

Tijelo je JSON. Sva polja su opcionalna.

- `other` ili `comment`: komentar.
- Oprema po tipu. Tipovi su `mask`, `regulator`, `suit`, `boots`,
  `gloves`, `fins`, `bcd`, `lead`. Za svaki tip:
  - `equipment.{tip}.code`: šifra komada (npr. `MD12`), ili prazno za uklanjanje.
  - `equipment.{tip}.returned`: status povrata, vidi dolje.
  - Kratki oblik `{tip}` i `{tip}_returned` također radi.
- `donation_amount`: iznos donacije u EUR, broj ili `null`.
  - Prihvaća `12.5` i `"12,5"`. Vrijednost `null` ili prazan string briše donaciju.
  - Negativna ili nenumerička vrijednost daje `400 rkg_invalid_donation`.
  - Ako polje nije u tijelu, postojeća vrijednost ostaje.
  - Donacija se uređuje na razduživanju, ne pri kreiranju.

**Status povrata** (`returned`):

- `0`: vraćeno (na stanju)
- `1`: još izdano
- `3`: izgubljeno

Ako pošalješ isti status koji već piše u rezervaciji, nema promjene.

Arhivirana rezervacija vraća `404` i ne može se uređivati. Prvo je treba
vratiti iz arhive.

### POST `/reservations` (nova rezervacija)

Tijelo: `user_id` (obavezno), oprema i komentar kao u uređivanju.
Donacija se ovdje ne prima.

### DELETE `/reservations/{id}`

Briše rezervaciju i vraća izdanu opremu na stanje. Vraća
`{"deleted": true, "id": ...}`.

## Oblik rezervacije (odgovor)

```json
{
  "id": 36,
  "user_id": 2205,
  "user_name": "Ime Prezime",
  "excursion_id": null,
  "excursion": null,
  "state": 1,
  "status": "Aktivno",
  "archived": false,
  "donation_amount": 12.5,
  "comment": "",
  "equipment": {
    "mask": {
      "label": "Maska i disalica",
      "size": null,
      "code": "MD12",
      "needed": true,
      "returned": 1
    }
  }
}
```

- `state`: `0` Na čekanju, `1` Aktivno, `2` Vraćeno. Vrijednost `3` (Obrisano)
  API ne vraća.
- `equipment` ima sve tipove iz popisa gore.
- `needed` je `false` ako član ima trajno odbijenu opremu tog tipa.
- `donation_amount` je broj ili `null`.

## Greške

| HTTP | `code` | Značenje |
|---|---|---|
| 400 | `rkg_invalid` | Neispravan korisnik |
| 400 | `rkg_invalid_return` | Neispravan status povrata |
| 400 | `rkg_invalid_donation` | Neispravan iznos donacije |
| 400 | `rkg_unavailable` | Oprema nije dostupna. Odgovor sadrži `equipment` s popisom tipova |
| 403 | `rest_forbidden` | Korisnik nema ovlast |
| 404 | `rkg_not_found` | Rezervacija ne postoji, arhivirana je ili je obrisana |
| 500 | `rkg_update_failed` | Greška na bazi |

## Preduvjeti na serveru

- Pokrenute migracije: `php database/migrate.php migrate`. Bez njih
  rezervacije nemaju `archived` i `donation_amount`.
- Grana `dev` (ili nakon spajanja `main`).

## Što ne raditi

- Ne filtriraj listu po datumu. Lista koristi samo `archived` i `state`.
- Ne prikazuj arhivirane rezervacije u glavnoj listi. Ako treba, dodaj
  zasebnu stranicu za arhivu (nije u trenutnom API-ju).
- Ne šalji `state = 3` niti očekuj obrisane rezervacije.
- Ne spremaj donaciju pri kreiranju rezervacije, samo pri razduživanju.

## Lokalno testiranje

```bash
# bez autentifikacije: očekuj 403 (ruta postoji)
curl -i http://localhost:8080/wp-json/rkg/v1/reservations

# s Application Password
curl -u "<korisnik>:<application-password>" \
  "http://localhost:8080/wp-json/rkg/v1/reservations?per_page=20"
```

Za lokalne podatke pogledaj `DOCKER.md`.
