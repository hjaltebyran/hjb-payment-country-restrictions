# HJB Payment Country Restrictions

**Utvecklat av [Hjältebyrån](https://hjaltebyran.se) 

---

Styr vilka betalningsmetoder som visas i kassan baserat på kundens land. Perfekt för butiker som säljer till flera marknader och behöver anpassa betalningsflödet per land eller region.

## Funktioner

- Whitelist eller blacklist valfria betalningsmetoder per land
- Välj enskilda länder eller fördefinierade regioner (Norden, EU, EEA m.fl.)
- Landväljare med modal visas automatiskt i kassan vid första besök
- Svea Checkout-integration: kassan neutraliseras automatiskt för länder där Svea inte är aktiverat — baserat på dina egna inställningar, ingen hårdkodad lista
- Stöder WooCommerce HPOS (High-Performance Order Storage)

---

## Krav

| Krav | Version |
|---|---|
| WordPress | 5.8 eller senare |
| WooCommerce | 6.0 eller senare |
| PHP | 7.4 eller senare |

---

## Installation

1. Ladda ned den senaste versionen som `.zip`-fil från [Releases](../../releases/latest)
2. Gå till **WordPress-admin → Plugins → Lägg till nytt → Ladda upp plugin**
3. Välj `.zip`-filen och klicka **Installera nu**
4. Klicka **Aktivera plugin**
5. Gå till **WooCommerce → Betalningsrestriktioner** för att konfigurera

---

## Konfiguration

Under **WooCommerce → Betalningsrestriktioner** listas alla aktiva betalningsmetoder. För varje metod väljer du:

| Läge | Beskrivning |
|---|---|
| Ingen begränsning | Visas för alla länder (standard) |
| Whitelist | Visas **endast** för valda länder/regioner |
| Blacklist | **Döljs** för valda länder/regioner |

Länder kan väljas individuellt eller via en av de inbyggda regionerna.

---

## Changelog

### 1.2.0
- Svea Checkout-suppression baseras nu dynamiskt på dina whitelist/blacklist-inställningar — inte längre en hårdkodad landslista

### 1.1.0
- Första release

---

## Licens

MIT — fri att använda, modifiera och distribuera. Se [LICENSE](LICENSE) för fullständiga villkor.

---

## Om Hjältebyrån

Vi är en svensk digitalbyrå med fokus på WooCommerce, WordPress och skräddarsydda webblösningar. Vi bygger det som inte finns färdigt — och ser till att det håller.

| | |
|---|---|
| **Webb** | [hjaltebyran.se](https://hjaltebyran.se) |
| **Mail** | [info@hjaltebyran.se](mailto:info@hjaltebyran.se) |
