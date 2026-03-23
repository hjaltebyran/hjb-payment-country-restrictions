# HJB Payment Country Restrictions

**Developed by [Hjältebyrån](https://hjaltebyran.se)**

Ett WooCommerce-plugin som låter dig styra vilka betalningsmetoder som visas baserat på kundens land — med stöd för whitelist, blacklist och regioner.

---

## Vad gör pluginet?

- Visa eller dölj valfria betalningsmetoder per land eller region
- Stöd för whitelist (endast tillåtna länder) och blacklist (blockerade länder)
- Inbyggda regioner: Norden, EU, EEA med flera
- Landväljare i kassan med modal vid första besök
- Svea Checkout-integration: kassan neutraliseras automatiskt för länder där Svea inte är tillgängligt, baserat på dina inställningar

---

## Krav

- WordPress 5.8 eller senare
- WooCommerce 6.0 eller senare
- PHP 7.4 eller senare

---

## Installation

1. Ladda ned pluginet via er kundportal
2. Gå till **WordPress-admin → Plugins → Lägg till nytt → Ladda upp plugin**
3. Välj den nedladdade `.zip`-filen och klicka **Installera nu**
4. Aktivera pluginet
5. Gå till **WooCommerce → Betalningsrestriktioner** för att konfigurera regler per betalningsmetod

---

## Konfiguration

Under **WooCommerce → Betalningsrestriktioner** visas alla aktiva betalningsmetoder. För varje metod kan du:

| Inställning | Beskrivning |
|---|---|
| Ingen begränsning | Betalningsmetoden visas för alla länder |
| Whitelist | Visas **bara** för valda länder/regioner |
| Blacklist | Döljs för valda länder/regioner |

Länder kan väljas individuellt eller via fördefinierade regioner (Norden, EU, EEA osv.).

---

## Changelog

### 1.2.0
- Svea Checkout-suppression baseras nu dynamiskt på dina whitelist/blacklist-inställningar istället för en hårdkodad landslista

### 1.1.0
- Första release

---

## Support

Detta plugin utvecklas och underhålls av **Hjältebyrån**.

Webbplats: [hjaltebyran.se](https://hjaltebyran.se)

---

*&copy; Hjältebyrån AB*
