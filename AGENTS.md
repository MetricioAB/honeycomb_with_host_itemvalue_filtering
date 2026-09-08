# Instruktioner för agenter

## Projektet

Detta är en Zabbix frontendmodul: en Honeycomb-widget med möjlighet att dölja alla celler från en host baserat på ett annat items senaste värde. Läs `README.md` och berörda filer innan ändringar görs.

Modulen körs i Zabbix frontend och är ingen fristående PHP-applikation. Fastställ målversionen av Zabbix innan versionsberoende API:er eller widgetkomponenter ändras. Anta inte att den senaste dokumentationen motsvarar installationen.

## Struktur

- `manifest.json`: modulidentitet, namespace, actions och JavaScript-resurser.
- `Widget.php`: widgetklass, standardnamn och översättningssträngar.
- `includes/WidgetForm.php`: inställningsfält, standardvärden och validering.
- `actions/WidgetView.php`: datahämtning, hostfilter, celler och visningskonfiguration.
- `views/widget.edit.php`: inställningsformulärets layout.
- `views/widget.edit.js.php`: formulärets dynamiska beteende.
- `views/widget.view.php`: widgetens svarsvy.
- `assets/js/`: widgetlogik och SVG-rendering.
- `tests/host_filter.php`: isolerade PHP-regressionstester med simulerade Zabbix-beroenden.

## Modulidentitet

Behåll följande namn konsekventa:

- Modul-ID: `honeycomb_with_filter`.
- Manifestets namespace: `Honeycomb_with_filter`.
- Widgetklass: `Modules\Honeycomb_with_filter\Widget`.
- Formklass: `Modules\Honeycomb_with_filter\Includes\WidgetForm`.
- Controller: `Modules\Honeycomb_with_filter\Actions\WidgetView`.
- View-action: `widget.honeycomb_with_filter.view`.

Katalognamnet `honeycomb_with_host_item_filter` skiljer sig avsiktligt från modul-ID:t. Härled inte PHP-namespace eller actionnamn från katalognamnet. Importera inte klasser från originalwidgetens `Widgets\Honeycomb` när modulens egna klasser avses. Ändra inte modul-ID eller sparade fältnamn utan att hantera befintliga widgetinställningar.

## Beteende som ska bevaras

Om uppgiften inte uttryckligen ändrar filterbeteendet gäller:

- `host_filter_items` är itemnamn/mönster som matchas separat på varje host, inte ett gemensamt item-ID.
- Tomt eller saknat filterval stänger av filtreringen och ska inte orsaka extra API-anrop.
- `host_filter_value` har standardvärdet `0`.
- Om något matchande filteritem har det angivna värdet döljs alla celler från den hosten.
- Jämför senaste råvärdet före value mapping och enhetsformatering. Numeriska items jämförs numeriskt; text/logg jämförs exakt och skiftlägeskänsligt.
- Hostar utan matchande aktivt filteritem eller aktuell historik behålls.
- Filteritems behöver inte matcha visade items eller deras taggar.
- Begränsa filterhämtningen till hostar som har kandidatceller och använd Zabbix API så att behörigheter respekteras.
- Filtrera före sortering och cellbegränsning. Hämta historik i batchar, inte med ett anrop per host.
- Behåll stöd för vanliga dashboards, template dashboards och hostval från andra widgetar.

## Kodändringar

Följ befintlig PHP- och JavaScript-stil, inklusive tabbindrag och Zabbix fält- och vyklasser. Behåll licenshuvuden. Använd engelska UI-strängar med Zabbix översättningsfunktioner (`_()`/`_s()`) i PHP. Skriv användardokumentation och återrapportering på svenska.

När ett inställningsfält läggs till eller ändras, kontrollera hela kedjan: formdefinition, standardvärde, validering, vy, eventuell JavaScript-logik och controller. Befintliga sparade widgetar måste fungera även om nya fält saknas. Uppdatera `README.md` när användarbeteendet ändras. Håll ändringar fokuserade på uppgiften.

## Verifiering

Kör relevanta kontroller när miljön stödjer dem:

```sh
php tests/host_filter.php
find . -name '*.php' -type f -exec php -l {} \;
python3 -m json.tool manifest.json > /dev/null
```

Utöka regressionstester vid ändrad filterlogik. Täck särskilt två hostar med värdena `0` och `1`, att hela rätt host försvinner, avstängt filter, saknat item/historik, numeriska respektive textvärden och template dashboards.

De isolerade testerna verifierar inte Zabbix API, formulärrendering eller dashboardintegration. Vid tillgång till en testinstallation, kontrollera att inställningar kan sparas och öppnas igen, att itemvalet begränsas av hostvalet och att en host försvinner/återkommer när filtervärdet ändras och widgeten uppdateras.

Om PHP, Docker eller en Zabbix-testinstallation saknas, redovisa vilka kontroller som inte kunde köras. Beskriv aldrig statisk granskning som ett godkänt körnings- eller integrationstest. Skilj lokala filändringar från installation på Zabbix-servern.
